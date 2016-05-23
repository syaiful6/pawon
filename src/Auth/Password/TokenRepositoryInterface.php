<?php

namespace Pawon\Auth\Password;

interface TokenRepositoryInterface
{
    /**
     * Create a new token.
     *
     * @param  object  $user
     * @return string
     */
    public function create($user);

    /**
     * Determine if a token record exists and is valid.
     *
     * @param  object  $user The user model
     * @param  string  $token
     * @return bool
     */
    public function exists($user, $token);

    /**
     * Delete a token record.
     *
     * @param  string  $token
     * @return void
     */
    public function delete($token);

    /**
     * Delete expired tokens.
     *
     * @return void
     */
    public function deleteExpired();
}
