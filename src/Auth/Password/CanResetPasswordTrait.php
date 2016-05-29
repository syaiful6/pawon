<?php

namespace Pawon\Auth\Password;

trait CanResetPasswordTrait
{
    /**
     * The only required method defined on user when want to provide reset password.
     *
     * @return string The user email
     */
    public function getEmail()
    {
        return $this->email;
    }
}
