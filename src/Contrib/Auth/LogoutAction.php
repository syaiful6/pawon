<?php

namespace Pawon\Contrib\Auth;

use Pawon\Auth\Authenticator;
use Pawon\Auth\Access\LoginRequiredTrait;
use Pawon\Http\Middleware\MiddlewareInterface;
use Pawon\Http\Middleware\FrameInterface as Frame;
use Psr\Http\Message\ServerRequestInterface as Request;
use function Pawon\trans;

class LogoutAction implements MiddlewareInterface
{
    use LoginRequiredTrait;

    /**
     * @var Pawon\Auth\Authenticator
     */
    protected $authenticator;

    /**
     *
     */
    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    /**
     *
     */
    protected function handlePermissionPassed(Request $request, Frame $frame)
    {
        $request = $this->authenticator->logout($request);
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'success'])) {
            $flash->success($this->getLogoutSuccessMessage());
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location' => '/'
        ]);
    }

    /**
     * Give the user an helpfull message here
     *
     * @return string
     */
    protected function getPermissionDeniedMessage()
    {
        return trans()->has('auth.logout.error')
        ? trans()->get('auth.logout.error')
        : 'You can\'t logout while still not logged in';
    }

    /**
     *
     */
    protected function getLogoutSuccessMessage()
    {
        return trans()->has('auth.logout.success')
        ? trans()->get('auth.logout.success')
        : 'Come back later.';
    }
}
