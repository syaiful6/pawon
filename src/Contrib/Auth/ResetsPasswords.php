<?php

namespace Pawon\Contrib\Auth;

use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pawon\Auth\Access\UserPassesTestTrait;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use function Pawon\trans;

class ResetsPasswords extends BaseActionMiddleware
{
    use UserPassesTestTrait {
        handle as userPassedTest;
    }

    /**
     * @var Illuminate\Contracts\Auth\PasswordBroker
     */
    protected $broker;

    /**
     *
     */
    public function __construct(PasswordBroker $broker)
    {
        $this->broker = $broker;
    }

    /**
     *
     */
    protected function testCallback(Request $request)
    {
        return function () use ($request) {
            $user = $request->getAttribute('user');

            return $user && !$user->isAuthenticate();
        };
    }

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        return $this->userPassedTest($request, $frame);
    }

    /**
     * Give the user an helpfull message here.
     *
     * @return string
     */
    protected function getPermissionDeniedMessage()
    {
        return trans()->has('passwords.login')
        ? trans()->get('passwords.login')
        : 'You can\'t reset password while still logged in';
    }

    /**
     *
     */
    protected function handlePermissionPassed(Request $request, FrameInterface $frame)
    {
        return parent::handle($request, $frame);
    }

    /**
     *
     */
    public function get(Request $request, FrameInterface $frame)
    {
        $html = $this->renderer->render('app::auth/passwords/email', [
            'error' => new MessageBag(),
        ]);

        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     *
     */
    public function post(Request $request, FrameInterface $frame)
    {
        $valid = $this->isValid($request, ['email' => 'required|email']);

        if ($valid) {
            return $this->sendResetLinkEmail($request, $frame);
        }

        return $this->formInvalid($request, $frame);
    }

    /**
     * render with errors.
     */
    protected function formInvalid(Request $request, FrameInterface $frame)
    {
        $html = $this->renderer->render('app::auth/passwords/email', [
            'error' => $this->validator->errors(),
        ]);

        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     *
     */
    protected function sendResetLinkEmail(Request $request, FrameInterface $frame)
    {
        $input = $request->getParsedBody();

        $out = $this->broker->sendResetLink(
            ['email' => $input['email']],
            $this->resetEmailBuilder()
        );

        switch ($out) {
            case PasswordBroker::RESET_LINK_SENT:
                return $this->getSendResetLinkEmailSuccessResponse(
                    $request,
                    $frame,
                    $out
                );

            case PasswordBroker::INVALID_USER:
            default:
                return $this->getSendResetLinkEmailFailureResponse(
                    $request,
                    $frame,
                    $out
                );
        }
    }

    /**
     *
     */
    protected function getSendResetLinkEmailSuccessResponse($request, $frame, $out)
    {
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'success'])) {
            $flash->success(trans($out));
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location' => $request->getUri()->getPath(),
        ]);
    }

    /**
     *
     */
    protected function getSendResetLinkEmailFailureResponse($request, $frame, $out)
    {
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'warning'])) {
            $flash->warning(trans($out));
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location' => $request->getUri()->getPath(),
        ]);
    }

    /**
     * Get the Closure which is used to build the password reset email message.
     *
     * @return \Closure
     */
    protected function resetEmailBuilder()
    {
        return function ($message) {
            $message->from('dummy@gmail.com', 'replace me');
            $message->subject($this->getEmailSubject());
        };
    }

    /**
     *
     */
    protected function getEmailSubject()
    {
        return property_exists($this, 'subject')
        ? $this->subject
        : 'Your Password Reset Link';
    }
}
