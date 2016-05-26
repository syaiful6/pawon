<?php

namespace Pawon\Contrib\Auth;

use Pawon\Auth\Authenticator;
use Pawon\Cache\RateLimiter;
use Illuminate\Support\MessageBag;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Pawon\Http\Middleware\FrameInterface;
use Pawon\Flash\FlashMessageInterface as FlashMessage;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use function Pawon\trans;

class LoginAction extends BaseActionMiddleware
{
    /**
     * @var App\Auth\Authenticator
     */
    protected $authenticator;

    /**
     * @var App\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * App\Flash\FlashMessageInterface
     */
    protected $flash;

    /**
     *
     */
    public function __construct(
        Authenticator $authenticator,
        RateLimiter $limiter,
        FlashMessage $flash
    ) {
        $this->authenticator = $authenticator;
        $this->limiter = $limiter;
        $this->flash = $flash;
    }

    /**
     * Display the login form
     */
    public function get(Request $request, FrameInterface $frame)
    {
        $user = $request->getAttribute('user');
        if ($user->isAuthenticate()) {
            return $frame->getResponseFactory()->make('', 302, [
                'location' => '/'
            ]);
        }
        $html = $this->renderer->render('app::auth/login', [
            'error' => new MessageBag
        ]);
        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html'
        ]);
    }

    /**
     *
     */
    public function post(Request $request, FrameInterface $frame)
    {
        $valid = $this->validateLogin($request);

        if ($valid) {
            return $this->formValid($request, $frame);
        }

        return $this->formInvalid($request, $frame);
    }

    /**
     *
     */
    protected function formValid(Request $request, FrameInterface $frame)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            return $this->sendResponseLockout($request, $frame);
        }

        $posted = $request->getParsedBody();
        $credential = [
            'email' => $posted['email'],
            'password' => $posted['password']
        ];
        $user = $this->authenticator->authenticate($credential);
        if ($user) {
            $this->authenticator->login($request, $user);
            $this->flash->info("Welcome {$user->name}.");
            $this->clearLoginAttempts($request);
            return $frame->getResponseFactory()->make('', 302, [
                'location' => '/'
            ]);
        }

        $this->flash->warning($this->getInvalidLoginMessage());

        $this->incrementLoginAttempts($request);

        return $frame->getResponseFactory()->make('', 302, [
            'location' => $request->getUri()->getPath()
        ]);
    }

    /**
     *
     */
    protected function getInvalidLoginMessage()
    {
        return trans()->has('auth.failed')
        ? trans()->get('auth.failed')
        : 'These credentials do not match our records.';
    }

    /**
     * render with errors
     */
    protected function formInvalid(Request $request, FrameInterface $frame)
    {
        $html = $this->renderer->render('app::auth/login', [
            'error' => $this->validator->errors()
        ]);
        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html'
        ]);
    }

    /**
     *
     */
    protected function sendResponseLockout(Request $request, FrameInterface $frame)
    {
        $minutes = floor($this->secondsRemainingOnLockout($request) / 60);
        $this->flash($this->getLockoutErrorMessage($minutes));

        return $frame->getResponseFactory()->make('', 302, [
            'location' => $request->getUri()->getPath()
        ]);
    }

    /**
     * Get the login lockout error message.
     *
     * @param  int  $seconds
     * @return string
     */
    protected function getLockoutErrorMessage($minutes)
    {
        return trans()->has('auth.throttle')
            ? trans()->get('auth.throttle', ['minutes' => $minutes])
            : 'Too many login attempts. Please try again in '.$minutes.' minutes.';
    }

    /**
     * Determine if the user has too many failed login attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request)
    {
        return $this->limiter->tooManyAttempts(
            $this->getThrottleKey($request),
            $this->maxLoginAttempts(),
            null,
            $this->lockoutTime()
        );
    }

    /**
     * Get the lockout seconds.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function secondsRemainingOnLockout(Request $request)
    {
        return $this->limiter->availableIn(
            $this->getThrottleKey($request)
        );
    }

    /**
     *
     */
    protected function clearLoginAttempts(Request $request)
    {
        $this->limiter->clear($this->getThrottleKey($request));
    }

    /**
     * Determine how many retries are left for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function retriesLeft(Request $request)
    {
        return $this->limiter->retriesLeft(
            $this->getThrottleKey($request),
            $this->maxLoginAttempts()
        );
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function incrementLoginAttempts(Request $request)
    {
        $this->limiter->hit(
            $this->getThrottleKey($request)
        );
    }

    /**
     *
     */
    protected function getThrottleKey($request)
    {
        $post = $request->getParsedBody();
        $ip = $this->extractClientIpFromRequest($request);
        return $post['email'].'|'.$ip;
    }

    /**
     *
     */
    protected function maxLoginAttempts()
    {
        return 5;
    }

    /**
     *
     */
    protected function lockoutTime()
    {
        return 600;
    }

    /**
     * I believe this should done on framework
     */
    protected function extractClientIpFromRequest($request)
    {
        $server = $request->getServerParams();
        if (isset($server['REMOTE_ADDR'])) {
            return (string) $server['REMOTE_ADDR'];
        }
        $proxies = $request->getHeader('X_FORWARDED_FOR');
        if (empty($proxies)) {
            return '';
        }
        $proxies = array_map('trim', explode(',', $proxies[0]));
        $ip = array_pop($proxies);
        return $ip;
    }

    /**
     *
     */
    protected function validateLogin(Request $request)
    {
        return $this->isValid($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
    }
}
