<?php

namespace Pawon\Auth\Password;

use Closure;
use UnexpectedValueException;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use function Pawon\urlsafe_base64_encode;

class PasswordBroker implements PasswordBrokerContract
{
    /**
     * @var Pawon\Auth\Passwords\TokenRepositoryInterface
     */
    protected $repository;

    /**
     * @var Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * @var email template
     */
    protected $emailTemplate;

    /**
     * @var password validator
     */
    protected $passwordValidator;

    /**
     * The eloquent model, only need to define getEmail.
     */
    protected $model;

    /**
     *
     */
    public function __construct(
        TokenRepositoryInterface $repository,
        MailerContract $mailer,
        $model,
        $emailTemplate
    ) {
        $this->repository = $repository;
        $this->model = $model;
        $this->mailer = $mailer;
        $this->emailTemplate = $emailTemplate;
    }

    /**
     * Send a password reset link to a user.
     *
     * @param array         $credentials
     * @param \Closure|null $callback
     *
     * @return string
     */
    public function sendResetLink(array $credentials, Closure $callback = null)
    {
        $user = $this->getUser($credentials);

        if (!$user) {
            return PasswordBrokerContract::INVALID_USER;
        }

        $token = $this->repository->create($user);
        $this->emailResetLink($user, $token, $callback);

        return PasswordBrokerContract::RESET_LINK_SENT;
    }

    /**
     * Send the password reset link via e-mail.
     *
     * @param The model     $user
     * @param string        $token
     * @param \Closure|null $callback
     *
     * @return int
     */
    public function emailResetLink($user, $token, Closure $callback = null)
    {
        if (!method_exists($user, 'getEmail') || !is_callable([$user, 'getEmail'])) {
            throw new \RuntimeException('The user model need to define getEmail');
        }

        $template = $this->emailTemplate;
        $context = compact('token', 'user');
        $context['email'] = urlsafe_base64_encode($user->getEmail());
        $context['uid'] = urlsafe_base64_encode($user->getKey());

        return $this->mailer->send(
            $template,
            $context,
            function ($m) use ($user, $token, $callback) {
                $m->to($user->getEmail());

                if (is_callable($callback)) {
                    call_user_func($callback, $m, $user, $token);
                }
            }
        );
    }

    /**
     *
     */
    public function reset(array $credentials, Closure $callback)
    {
        $user = $this->validateReset($credentials);

        if (!is_object($user)) {
            return $user;
        }
        $password = $credentials['password'];

        $callback($user, $password);

        $this->repository->delete($credentials['token']);

        return PasswordBrokerContract::PASSWORD_RESET;
    }

    /**
     *
     */
    protected function validateReset(array $credentials)
    {
        if (($user = $this->getUser($credentials)) === null) {
            return PasswordBrokerContract::INVALID_USER;
        }
        if (!$this->validateNewPassword($credentials)) {
            return PasswordBrokerContract::INVALID_PASSWORD;
        }
        if (!$this->repository->exists($user, $credentials['token'])) {
            return PasswordBrokerContract::INVALID_TOKEN;
        }

        return $user;
    }

    /**
     * Set a custom password validator.
     *
     * @param \Closure $callback
     */
    public function validator(Closure $callback)
    {
        $this->passwordValidator = $callback;
    }

    /**
     * Determine if the passwords match for the request.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function validateNewPassword(array $credentials)
    {
        list($password, $confirm) = [
            $credentials['password'],
            $credentials['password_confirmation'],
        ];

        if (isset($this->passwordValidator)) {
            return call_user_func(
                $this->passwordValidator,
                $credentials
            ) && $password === $confirm;
        }

        return $this->validatePasswordWithDefaults($credentials);
    }

    /**
     * Determine if the passwords are valid for the request.
     *
     * @param array $credentials
     *
     * @return bool
     */
    protected function validatePasswordWithDefaults(array $credentials)
    {
        list($password, $confirm) = [
            $credentials['password'],
            $credentials['password_confirmation'],
        ];

        return $password === $confirm && mb_strlen($password) >= 6;
    }

    /**
     *
     */
    protected function getUser(array $credentials)
    {
        $user = $this->getByCredentials($credentials);

        if ($user && (!method_exists($user, 'getEmail') &&
            !is_callable([$user, 'getEmail']))
        ) {
            throw new UnexpectedValueException(
                'The model should at least have method getEmail'
            );
        }

        return $user;
    }

    /**
     *
     */
    protected function getByCredentials(array $credentials)
    {
        if (empty($credentials)) {
            return;
        }

        $query = $this->createModel()->newQuery();
        foreach ($credentials as $k => $v) {
            if ($k === 'token') {
                continue;
            }
            $query = $query->where($k, $v);
        }

        return $query->first();
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class();
    }
}
