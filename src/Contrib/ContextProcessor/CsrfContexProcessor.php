<?php

namespace Pawon\Contrib\ContextProcessor;

use Pawon\Functional\LazyString;
use Psr\Http\Message\ServerRequestInterface;

class CsrfContexProcessor
{
    /**
     *
     */
    public function __invoke(ServerRequestInterface $request)
    {
        $token = function () use ($request) {
            $getToken = $request->getAttribute('CSRF_TOKEN_GET');
            $token = $getToken($request);
            if (!$token) {
                $token = 'NOTPROVIDED'; // for debugging
            }

            return $token;
        };

        return ['csrftoken' => new LazyString($token)];
    }
}
