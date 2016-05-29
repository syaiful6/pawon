<?php

namespace Pawon\Auth\Password;

use Pawon\DateTime\DateTime;
use Pawon\DateTime\TimeDelta;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionInterface;

class DatabaseTokenRepository implements TokenRepositoryInterface
{
    /**
     * @var Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The token database table.
     *
     * @var string
     */
    protected $table;

    /**
     * The hashing key.
     *
     * @var string
     */
    protected $secret;

    /**
     * The number of seconds a token should last.
     *
     * @var int
     */
    protected $expires;

    /**
     * Create a new token repository instance.
     *
     * @param \Illuminate\Database\ConnectionInterface $connection
     * @param string                                   $table
     * @param string                                   $secret
     * @param int                                      $expires
     */
    public function __construct(ConnectionInterface $connection, $table, $secret, $expires = 60)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->secret = $secret;
        $this->expires = $expires * 60;
    }

    /**
     *
     */
    public function create($user)
    {
        $this->sanityCheck($user);
        // delete previous token for this email
        $email = $user->getEmail();
        $this->deleteExisting($email);

        $token = $this->createNewToken();
        $this->getTable()->insert($this->getPayload($email, $token));

        return $token;
    }

    /**
     * Delete all existing reset tokens from the database.
     *
     * @param \Illuminate\Contracts\Auth\CanResetPassword $user
     *
     * @return int
     */
    protected function deleteExisting($email)
    {
        return $this->getTable()->where('email', $email)->delete();
    }

    /**
     * Determine if a token record exists and is valid.
     *
     * @param object $user
     * @param string $token
     *
     * @return bool
     */
    public function exists($user, $token)
    {
        $this->sanityCheck($user);

        $email = $user->getEmail();
        $token = (array) $this->getTable()->where('email', $email)->where('token', $token)->first();

        return $token && !$this->tokenExpired($token);
    }

    /**
     * Build the record payload for the table.
     *
     * @param string $email
     * @param string $token
     *
     * @return array
     */
    protected function getPayload($email, $token)
    {
        return ['email' => $email, 'token' => $token, 'created_at' => new DateTime()];
    }

    /**
     * Determine if the token has expired.
     *
     * @param array $token
     *
     * @return bool
     */
    protected function tokenExpired($token)
    {
        $expirationTime = strtotime($token['created_at']) + $this->expires;

        return $expirationTime < $this->getCurrentTime();
    }

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    protected function getCurrentTime()
    {
        return time();
    }

    /**
     * Delete a token record by token.
     *
     * @param string $token
     */
    public function delete($token)
    {
        $this->getTable()->where('token', $token)->delete();
    }

    /**
     * Delete expired tokens.
     */
    public function deleteExpired()
    {
        $delta = TimeDelta::seconds($this->expires);
        $expiredAt = DateTime::now()->sub($delta);

        $this->getTable()->where('created_at', '<', $expiredAt)->delete();
    }

    /**
     * Create a new token for the user.
     *
     * @return string
     */
    public function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->secret);
    }

    /**
     * Begin a new database query against the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     *
     */
    private function sanityCheck($user)
    {
        if (!method_exists($user, 'getEmail') || !is_callable([$user, 'getEmail'])) {
            throw new \UnexpectedValueException(
                'user should at least have getEmail method and its callable'
            );
        }
    }
}
