<?php

namespace Pawon\Contrib\Auth;

use Pawon\Auth\User;
use Pawon\Auth\ModelBackend;
use Pawon\Auth\Authenticator;
use Pawon\DateTime\DateTime;
use Illuminate\Support\MessageBag;
use Pawon\Auth\Access\UserPassesTestTrait;
use Pawon\Contrib\Http\BaseActionMiddleware;
use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class RegisterAction extends BaseActionMiddleware
{
    use UserPassesTestTrait {
        handle as userPassedTest;
    }

    /**
     * @var App\Auth\Authenticator
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
        $html = $this->renderer->render('app::auth/register', [
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
        $valid = $this->validateRegister($request);

        if ($valid) {
            return $this->formValid($request, $frame);
        }

        return $this->formInvalid($request, $frame);
    }

    /**
     * render with errors.
     */
    protected function formInvalid(Request $request, FrameInterface $frame)
    {
        $html = $this->renderer->render('app::auth/register', [
            'error' => $this->validator->errors(),
        ]);

        return $frame->getResponseFactory()->make($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     *
     */
    protected function formValid(Request $request, FrameInterface $frame)
    {
        $user = $this->create($this->getAllRequestInput($request));
        $this->authenticator->login($request, $user, ModelBackend::class);
        $flash = $request->getAttribute('_messages');
        if (method_exists($flash, 'info')) {
            $flash->info('Welcome, registration completed');
        }

        return $frame->getResponseFactory()->make('', 302, [
            'location' => '/',
        ]);
    }

    /**
     *
     */
    protected function create($input)
    {
        $user = new User();
        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->date_joined = DateTime::now();
        $user->setPassword($input['password']);
        $user->save();

        return $user;
    }

    /**
     *
     */
    protected function validateRegister(Request $request)
    {
        return $this->isValid($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }
}
