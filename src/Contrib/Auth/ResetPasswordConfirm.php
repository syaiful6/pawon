<?php

namespace Pawon\Contrib\Auth;

use Illuminate\Support\MessageBag;
use Pawon\Auth\Authenticator;
use Pawon\Auth\ModelBackend;
use Pawon\Auth\Access\UserPassesTestTrait;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Pawon\Http\Middleware\FrameInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Psr\Http\Message\ServerRequestInterface as Request;
use function Pawon\trans;
use function Pawon\partial;

class ResetPasswordConfirm extends BaseActionMiddleware
{
    use UserPassesTestTrait;

    /**
     * @var Illuminate\Contracts\Auth\PasswordBroker
     */
    protected $broker;

    /**
     *
     */
    protected $authenticator;

    /**
     *
     */
    public function __construct(
        PasswordBroker $broker,
        Authenticator $authenticator
    ) {
        $this->broker = $broker;
        $this->authenticator = $authenticator;
    }

    /**
     *
     */
    protected function testCallback(Request $request)
    {
        return function () use ($request) {
            $token = $request->getAttribute('token', false);
            $email = $request->getAttribute('email', false);

            return $token && $email;
        };
    }

    /**
     * Give the user an helpfull message here
     *
     * @return string
     */
    protected function getPermissionDeniedMessage()
    {
        return trans()->has('passwords.missing_token')
        ? trans()->get('passwords.missing_token')
        : 'Invalid request for reset token';
    }

    /**
     *
     */
    public function get(Request $request, FrameInterface $frame)
    {
        $token = $request->getAttribute('token');
        $email = urlsafe_base64_decode($request->getAttribute('email'));

        $html = $this->renderer->render($this->getResetTemplate(), [
            'error' => new MessageBag(),
            'token' => $token,
            'email' => $email
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
        $valid =  $this->isValid($request, $this->getResetValidationRules());

        if ($valid) {
            return $this->reset($request, $frame);
        }

        $token = $request->getAttribute('token');
        $email = urlsafe_base64_decode($request->getAttribute('email'));

        $html = $this->renderer->render($this->getResetTemplate(), [
            'error' => $this->validator->errors(),
            'token' => $token,
            'email' => $email
        ]);

        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html'
        ]);
    }

    /**
     *
     */
    protected function reset(Request $request, FrameInterface $frame)
    {
        $credentials = $this->getUserInput($this->getAllRequestInput($request));

        $response = $this->broker->reset(
            $credentials,
            partial([$this, 'resetPassword'], $request)
        );

        switch ($response) {
            case PasswordBroker::PASSWORD_RESET:
                return $this->getResetSuccessResponse($request, $frame, $response);

            default:
                return $this->getResetFailureResponse($request, $frame, $response);
        }
    }

    /**
     *
     */
    protected function resetPassword($request, $user, $password)
    {
        $user->setPassword($password);
        $user->save();
        $user->authBackend = ModelBackend::class;

        return $this->authenticator->login($request, $user);
    }

    /**
     *
     */
    protected function getResetSuccessResponse(
        Request $request,
        FrameInterface $frame,
        $out
    ) {
        return $frame->getResponseFactory()->make('', 302, [
            'location' => '/'
        ]);
    }

    /**
     *
     */
    protected function getResetFailureResponse(
        Request $request,
        FrameInterface $frame,
        $out
    ) {
        $flash = $request->getAttribute('_messages');
        if (is_callable([$flash, 'warning'])) {
            $flash->warning(trans($out));
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location' => $request->getUri()->getPath()
        ]);
    }

    /**
     *
     */
    protected function getUserInput(array $input)
    {
        return array_filter($input, function ($key) {
            return in_array($key, [
                'email',
                'password',
                'password_confirmation',
                'token'
            ]);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     *
     */
    protected function getResetTemplate()
    {
        if (property_exists($this, 'resetTemplate')) {
            return $this->resetTemplate;
        }

        return $template = 'app::auth/passwords/reset';
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function getResetValidationRules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];
    }

    /**
     *
     */
    protected function handlePermissionPassed(Request $request, FrameInterface $frame)
    {
        return parent::handle($request, $frame);
    }
}
