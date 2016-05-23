<?php

namespace Pawon\Auth;

use Illuminate\Database\Eloquent\ModelNotFoundException;

class ModelBackend implements AuthBackend
{
    /**
     * @var model
     */
    protected $model;

    /**
     *
     */
    public function __construct($model = null)
    {
        $this->model = $model ?: User::class;
    }

    /**
     *
     */
    public function authenticate(array $params)
    {
        if (!isset($params['password'])) {
            return;
        }
        // pull the password from this params, so it not querying by getByCredentials
        // we will check it after we get the user
        $pass = $params['password'];
        unset($params['password']);

        $user = $this->getByCredentials($params);

        if (!$user) {
            // Run the password hasher once to reduce the timing
            // difference between an existing and a non-existing user
            $this->createModel()->setPassword($pass);
        } else {
            if ($user->checkPassword($pass) && $this->userCanAuthenticate($user)) {
                return $user;
            }
        }
    }

    /**
     *
     */
    public function getUser($id)
    {
        $query = $this->createModel()->newQuery();
        try {
            $user = $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return;
        }
        return $this->userCanAuthenticate($user) ? $user : null;
    }

    /**
     * Reject user with is_active=false
     */
    protected function userCanAuthenticate($user)
    {
        return $user->is_active || $user->is_active === null;
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

        return new $class;
    }
}
