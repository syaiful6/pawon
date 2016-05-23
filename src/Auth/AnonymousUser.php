<?php

namespace Pawon\Auth;

class AnonymousUser
{
    public $name = '';

    public $email = '';

    public $is_active = false;

    public $is_superuser = false;

    public $is_staff = false;

    public $is_sitter = false;

    /**
     *
     */
    public function isAnonymous()
    {
        return true;
    }

    /**
     *
     */
    public function isAuthenticate()
    {
        return false;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPassword($rawPassword)
    {
        throw new RuntimeException(
            "We doesn't provide a DB representation for AnonymousUser."
        );
    }

    public function checkPassword($rawPassword)
    {
        throw new RuntimeException(
            "We doesn't provide a DB representation for AnonymousUser."
        );
    }

    /**
     *
     */
    public function getHasher()
    {
    }

    /**
     *
     */
    public function setHasher(Hasher $hasher)
    {
    }
}
