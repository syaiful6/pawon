<?php

namespace Pawon\Contrib\ContextProcessor;

use Psr\Http\Message\ServerRequestInterface;

class UserContext
{
    /**
     *
     */
    public function __invoke(ServerRequestInterface $request)
    {
        return ['user' => $request->getAttribute('user')];
    }
}
