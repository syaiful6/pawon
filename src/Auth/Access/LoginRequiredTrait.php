<?php

namespace Pawon\Auth\Access;

use Pawon\Http\Middleware\FrameInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

trait LoginRequiredTrait
{
    use AccessTrait;

    /**
     *
     */
    public function handle(Request $request, FrameInterface $frame)
    {
        $user = $request->getAttribute('user');
        assert(method_exists($user, 'isAuthenticate'));
        if (!$user->isAuthenticate()) {
            return $this->handleNoPermission($request, $frame);
        }

        return $this->handlePermissionPassed($request, $frame);
    }
}
