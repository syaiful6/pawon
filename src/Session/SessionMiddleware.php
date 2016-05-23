<?php

namespace Pawon\Session;

use Headbanger\Set;
use Pawon\DateTime\DateTime;
use Pawon\Cookie\Cookie;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Pawon\Session\Exceptions\UpdateException;

class SessionMiddleware
{
    protected $store;

    protected $configs;

    /**
     *
     */
    public function __construct(Store $store, array $configs = [])
    {
        $this->store = $store;
        $this->configs = $configs;
    }

    /**
     *
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    ) {
        $session = $this->store;
        $cookies = $request->getCookieParams();
        $sessionId = isset($cookies[$session->getName()])
            ? $cookies[$session->getName()]
            : '';
        $session->setId($sessionId);
        $request = $request->withAttribute('session', $session);
        // process the next middleware
        $response = $next($request, $response);
        // see what happen there
        $session = $request->getAttribute('session', $session);
        $accessed = $session->isAccessed();
        $modified = $session->isModified();
        $empty = $session->isEmpty();
        // First check if we need to delete this cookie.
        // The session should be deleted only if the session is entirely empty
        if ($sessionId !== '' && $empty) {
            $response = $this->deleteCookieFromResponse($response, $session);
        } else {
            if ($accessed) {
                $response = $this->patchVaryHeader($response);
            }

            if ($modified) {
                $response = $this->addCookieToResponse($response, $session);

                if ((int) $response->getStatusCode() !== 500) {
                    try {
                        $session->save();
                    } catch (UpdateException $e) {
                        return new RedirectResponse($request->getUri()->getPath());
                    }
                }
            }
        }

        $this->collectGarbage($session);

        return $response;
    }

    protected function deleteCookieFromResponse($response, $session)
    {
        // delete the cookie
        $cookie = new Cookie();
        $cookie[$name = $session->getName()] = '';
        $cookie[$name]['expires'] = 'Thu, 01-Jan-1970 00:00:00 GMT';
        if ($this->configs['domain']) {
            $cookie[$name]['domain'] = $this->configs['domain'] ?: '/';
        }
        $cookie[$name]['max-age'] = 0;
        $cookie[$name]['path'] = $this->configs['path'] ?: '/';

        $out = $cookie->getOutput(null, '', '');
        return $response->withAddedHeader('Set-Cookie', $out);
    }

    /**
     *
     */
    protected function addCookieToResponse($response, $session)
    {
        $cookie = new Cookie();
        $cookie[$name = $session->getName()] = $session->getId();
        $cookie[$name]['expires'] = $this->getCookieExpirationDate();
        $cookie[$name]['path'] = $this->configs['path'] ?: '/';
        if (isset($this->configs['domain'])) {
            $cookie[$name]['domain'] = $this->configs['domain'];
        }
        if (isset($this->configs['secure']) && $this->configs['secure']) {
            $cookie[$name]['secure'] = true;
        }
        if (isset($this->configs['httponly']) && $this->configs['httponly']) {
            $cookie[$name]['httponly'] = true;
        }
        $out = $cookie->getOutput(null, '', '');
        return $response->withAddedHeader('Set-Cookie', $out);
    }

    /**
     *
     */
    protected function getCookieExpirationDate()
    {
        $configs = $this->configs;

        if ($configs['expire_on_close']) {
            return 0;
        }
        return DateTime::now()->modify((int) $configs['lifetime'] . ' second');
    }

    /**
     *
     */
    protected function patchVaryHeader($response)
    {
        if ($response->hasHeader('Vary')) {
            $vary = $response->getHeader('Vary');
        } else {
            $vary = [];
        }

        $set = new Set(array_map('strtolower', $vary));
        $newAdded = array_filter(['cookie', ], function ($item) use ($set) {
            return ! $set->contains($item);
        });
        $vary = join(', ', array_merge($vary, $newAdded));
        return $response->withHeader('Vary', $vary);
    }

    /**
     *
     */
    protected function collectGarbage($session)
    {
        $lottery = $this->configs['lottery'];
        if ($this->configHitsLottery($lottery)) {
            $session->gc($this->getSessionLifetimeInSeconds());
        }
    }

    /**
     *
     */
    protected function getSessionLifetimeInSeconds()
    {
        return $this->configs['lifetime'];
    }

    /**
     * Determine if the configuration odds hit the lottery.
     *
     * @param  array  $config
     * @return bool
     */
    protected function configHitsLottery(array $config)
    {
        return random_int(1, $config[1]) <= $config[0];
    }
}
