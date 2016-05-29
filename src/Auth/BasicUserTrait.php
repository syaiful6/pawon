<?php

namespace Pawon\Auth;

use Illuminate\Hashing\BcryptHasher;
use Illuminate\Contracts\Hashing\Hasher;

trait BasicUserTrait
{
    /**
     * @var Illuminate\Contracts\Hashing\Hasher
     */
    protected $hasher;

    /**
     * @var string which backend allow this user logged in
     */
    public $authBackend;

    /**
     * Always return False. This is a way of comparing User objects to
     * anonymous users.
     *
     * @return bool
     */
    public function isAnonymous()
    {
        return false;
    }

    /**
     * Always return True. This is a way to tell if the user has been
     * authenticated in templates.
     *
     * @return bool
     */
    public function isAuthenticate()
    {
        return true;
    }

    /**
     * Set the password for this user and hash it.
     *
     * @param string $rawPassword
     */
    public function setPassword($rawPassword)
    {
        $this->password = $this->getHasher()->make($rawPassword);
    }

    /**
     * Return boolean of whether the rawPassword was correct. Also handles if it
     * needs rehashing.
     *
     * @param string $rawPassword
     *
     * @return bool True if correct, false otherwise
     */
    public function checkPassword($rawPassword)
    {
        $hasher = $this->getHasher();
        if ($hasher->check($rawPassword, $this->password)) {
            if ($hasher->needsRehash($this->password)) {
                $this->setPasswordAttribute($rawPassword);
            }

            return true;
        }

        return false;
    }

    /**
     *
     */
    public function getHasher()
    {
        if (!$this->hasher) {
            return $this->hasher = new BcryptHasher();
        }

        return $this->hasher;
    }

    /**
     *
     */
    public function setHasher(Hasher $hasher)
    {
        $this->hasher = $hasher;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }
}
