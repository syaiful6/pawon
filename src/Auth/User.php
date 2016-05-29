<?php

namespace Pawon\Auth;

use Illuminate\Database\Eloquent\Model;
use Pawon\Auth\Password\CanResetPasswordTrait;

class User extends Model
{
    use BasicUserTrait, CanResetPasswordTrait;

    /**
     *
     */
    protected $fillable = [
        'name',
        'email',
    ];

    /**
     *
     */
    public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * this should mutate to DateTime.
     */
    protected $dates = ['last_login', 'date_joined'];
}
