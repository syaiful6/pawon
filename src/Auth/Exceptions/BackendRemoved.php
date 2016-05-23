<?php

namespace Pawon\Auth\Exceptions;

/**
 * Throwing when the user session still valid, but the backend that let the user
 * logged in removed from authenticator
 */
class BackendRemoved extends \Exception
{
}
