<?php

namespace Pawon\Auth\Access;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait LoginRequiredTrait
{
    use AccessTrait;

    /**
     *
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $user = $request->getAttribute('user');
        assert(method_exists($user, 'isAuthenticate'));
        if (! $user->isAuthenticate()) {
            return $this->handleNoPermission($request, $response, $next);
        }

        return $this->handlePermissionPassed($request, $response, $next);
    }
}
