<?php

namespace Pawon\Auth;

use Pawon\Session\Store;
use Illuminate\Support\Str;
use Pawon\DateTime\DateTime;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerAwareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class Authenticator implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    const SESSION_KEY = '_AUTH_USER_ID';
    const BACKEND_SESSION_KEY = '_AUTH_USER_BACKEND';
    const HASH_SESSION_KEY = '_AUTH_USER_REMEMBER_TOKEN';

    /**
     * @var array AuthBackend[]
     */
    protected $backends;

    /**
     * @var App\Session\Store
     */
    protected $session;

    /**
     * Create new authenticator, the backends arguments is stack of App\Auth\AuthBackend
     * instance. It will execute until an backend give the permission.
     *
     * @param App\Auth\AuthBackend[] $backends
     * @param App\Session\Store      $session
     */
    public function __construct(array $backends, Store $session = null)
    {
        $this->backends = $backends;
        $this->session = $session;
    }

    /**
     * If the given credentials are valid, return a User object, otherwise return
     * null.
     *
     * @param array $credentials
     *
     * @return user model
     */
    public function authenticate(array $credentials)
    {
        foreach ($this->backends as $backend) {
            try {
                // trust the user, and just call the authenticate method.
                $user = $backend->authenticate($credentials);
            } catch (Exceptions\NotSupportedCredentials $e) {
                // this backend not supported this credentials so continue
                continue;
            } catch (Exceptions\PermissionDenied $e) {
                // this backend says to stop in our tracks - this user should
                // not be allowed in at all.
                break;
            }
            if ($user === null) {
                continue;
            }
            $user->authBackend = $backend;

            return $user;
        }

        $this->authenticateFailed($this->cleanUpCredentials($credentials));
    }

    /**
     * login the given user and persist it through session. It also set the user
     * attribute to the give request.
     */
    public function login(Request $request, $user, $backend = null)
    {
        $session = $this->session;

        if (!$session instanceof Store) {
            throw new \RuntimeException('logged in without session on authenticator');
        }

        $sessionAuthHash = '';
        if (!$user) {
            $user = $request->getAttribute('user', false);
        }
        if (method_exists($user, 'getRememberToken')) {
            $sessionAuthHash = $user->getRememberToken();
            if (!$sessionAuthHash) {
                $sessionAuthHash = $this->refreshRememberToken($user, true);
            }
        }

        if ($session->contains(static::SESSION_KEY)) {
            $sessionUserId = $session[static::SESSION_KEY];
            if ($sessionUserId !== $user->getKey() || (
                $sessionAuthHash &&
                    !hash_equals($sessionAuthHash, $session->get(static::HASH_SESSION_KEY))
            )) {
                $session->flush();
            }
        } else {
            $session->cycleId();
        }

        $backend = $backend ?: $user->authBackend;
        if (!$backend) {
            $backend = $this->validateSingleBackend();
        }
        $backend = is_object($backend) ? get_class($backend) : $backend;

        $session[static::SESSION_KEY] = $user->getKey();
        $session[static::BACKEND_SESSION_KEY] = $backend;
        $session[static::HASH_SESSION_KEY] = $sessionAuthHash;

        // rotate the csrf token, it provided by csrf token
        $rotateToken = $request->getAttribute('CSRF_TOKEN_ROTATE');
        if (is_callable($rotateToken)) {
            $rotateToken();
        }

        $request = $request->withAttribute('user', $user);
        $this->userLoggedIn($user, $request);
        // return request so it can be used by middleware
        return $request;
    }

    /**
     * Logout the current logged in user in request.
     */
    public function logout(Request $request)
    {
        $user = $request->getAttribute('user', false);
        if (method_exists($user, 'isAuthenticate') && !$user->isAuthenticate()) {
            $user = null;
        }
        if ($user) {
            $this->refreshRememberToken($user, true);
        }
        $this->userLoggedOut($user, $request);
        // flush the session
        if ($this->session instanceof Store) {
            $this->session->flush();
        }
        // set the request user attribute as anonymous user
        return $request->withAttribute('user', new AnonymousUser());
    }

    /**
     *
     */
    public function user()
    {
        $session = $this->session;
        if (!$session instanceof Store) {
            return new AnonymousUser();
        }
        $user = null;
        try {
            $id = $session[static::SESSION_KEY];
            $backendCls = $session[static::BACKEND_SESSION_KEY];
            $backend = $this->searchBackend($backendCls);
            $user = $backend->getUser($id);

            if (method_exists($user, 'getRememberToken')) {
                $token = $user->getRememberToken();
                $sessionToken = $session[static::HASH_SESSION_KEY];
                $verify = hash_equals($token, $sessionToken);
                if (!$verify) {
                    $session->flush();
                    $user = null;
                }
            }
        } catch (\OutOfBoundsException $e) {
            // pass, it's mean we dont have an active user in session
        } catch (Exceptions\BackendRemoved $e) {
            // pass the auth backend is removed, so log out it
        }

        return $user ?: new AnonymousUser();
    }

    /**
     *
     */
    public function getUser()
    {
        return $this->user();
    }

    /**
     *
     */
    public function addBackend($backend)
    {
        $this->backends[] = $backend;
    }

    /**
     *
     */
    public function setSession(Store $session)
    {
        $this->session = $session;
    }

    /**
     *
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     *
     */
    public function removeBackend($toRemove)
    {
        $isString = is_string($toRemove);
        $newBackend = [];
        foreach ($this->backends as $backend) {
            if ($is_string && $toRemove === get_class($backend) ||
                ($toRemove === $backend)) {
                continue;
            }
            $newBackend[] = $backend;
        }
        $this->backends = $newBackend;
    }

    /**
     *
     */
    public function updateRememberToken(Request $request, $user)
    {
        $userRequest = $request->getAttribute('user', false);
        if (method_exists($user, 'getRememberToken')
            && $userRequest->getKey() === $user->getKey()
        ) {
            $session = $request->getAttribute('session', false);
            if ($session) {
                $session[static::HASH_SESSION_KEY] = $this->refreshRememberToken($user, true);
            }
        }
    }

    /**
     * Lazy load Zend\EventManager\EventManagerInterface.
     *
     * @return Zend\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     *
     */
    public function authenticateFailed(array $params)
    {
        $this->getEventManager()->trigger(__FUNCTION__, $this, $params);
    }

    /**
     *
     */
    public function userLoggedIn($user, Request $request = null)
    {
        $params = compact('user', 'request');
        $this->getEventManager()->trigger(__FUNCTION__, $this, $params);
    }

    /**
     *
     */
    public function userLoggedOut($user, Request $request = null)
    {
        $params = compact('user', 'request');
        $this->getEventManager()->trigger(__FUNCTION__, $this, $params);
    }

    /**
     *
     */
    protected function attachDefaultListeners()
    {
        $manager = $this->getEventManager();
        $manager->attach('userLoggedIn', function ($event) {
            $user = $event->getParam('user', false);
            if ($user) {
                $user->last_login = DateTime::now();
                $user->save();
            }
        });
    }

    /**
     *
     */
    protected function refreshRememberToken($user, $save = true)
    {
        $user->setRememberToken($token = Str::random(60));
        if ($save) {
            $user->save();
        }

        return $token;
    }

    /**
     *
     */
    protected function validateSingleBackend()
    {
        if (count($this->backends) === 1) {
            return $this->backends[0];
        }
        throw new \RuntimeException(
            'You have multiple backends installed, therefore therefore must provide'
            .' the `backend` argument or set the `authBackend` attribute on the user.'
        );
    }

    /**
     *
     */
    protected function searchBackend($cls)
    {
        foreach ($this->backends as $backend) {
            if (get_class($backend) === $cls) {
                return $backend;
            }
        }
        throw new Exceptions\BackendRemoved(
            "No [$cls] backends in authenticator. It maybe removed while user's session"
            .'still valid'
        );
    }

    /**
     *
     */
    protected function cleanUpCredentials(array $credentials)
    {
        $pattern = '/api|token|key|secret|password|signature/i';
        $subtitute = '********************';
        foreach (array_keys($credentials) as $key) {
            if (preg_match($pattern, $key)) {
                $credentials[$key] = $subtitute;
            }
        }

        return $credentials;
    }
}
