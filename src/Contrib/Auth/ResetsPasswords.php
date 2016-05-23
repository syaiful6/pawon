<?php

namespace Pawon\Contrib\Auth;

use Zend\Diactoros\Stream;
use Illuminate\Support\MessageBag;
use Illuminate\Contracts\Auth\PasswordBroker;
use Pawon\Auth\Access\UserPassesTestTrait;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function Pawon\trans;

class ResetsPasswords extends BaseActionMiddleware
{
    use UserPassesTestTrait {
        __invoke as userPassedTest;
    }

    /**
     *
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
    public function __invoke(Request $request, Response $response, callable $next)
    {
        return $this->userPassedTest($request, $response, $next);
    }

    /**
     * Give the user an helpfull message here
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
    protected function handlePermissionPassed(
        Request $request,
        Response $response,
        callable $next
    ) {
        return parent::__invoke($request, $response, $next);
    }

    /**
     *
     */
    public function get(Request $request, Response $response, callable $next)
    {
        $html = $this->renderer->render('app::auth/passwords/email', [
            'error' => new MessageBag()
        ]);
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($html);
        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html');
    }

    /**
     *
     */
    public function post(Request $request, Response $response, callable $next)
    {
        $valid =  $this->isValid($request, ['email' => 'required|email']);

        if ($valid) {
            return $this->sendResetLinkEmail($request, $response);
        }

        return $this->formInvalid($request, $response, $next);
    }

    /**
     * render with errors
     */
    protected function formInvalid(Request $request, Response $response, callable $next)
    {
        $html = $this->renderer->render('app::auth/passwords/email', [
            'error' => $this->validator->errors()
        ]);
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($html);
        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/html');
    }

    /**
     *
     */
    protected function sendResetLinkEmail($request, $response)
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
                    $response,
                    $out
                );

            case PasswordBroker::INVALID_USER:
            default:
                return $this->getSendResetLinkEmailFailureResponse(
                    $request,
                    $response,
                    $out
                );
        }
    }

    /**
     *
     */
    protected function getSendResetLinkEmailSuccessResponse($request, $response, $out)
    {
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'success'])) {
            $flash->success(trans($out));
        }

        return $response
            ->withHeader('location', $request->getUri()->getPath())
            ->withStatus(302);
    }

     /**
     *
     */
    protected function getSendResetLinkEmailFailureResponse($request, $response, $out)
    {
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'warning'])) {
            $flash->warning(trans($out));
        }

        return $response
            ->withHeader('location', $request->getUri()->getPath())
            ->withStatus(302);
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
