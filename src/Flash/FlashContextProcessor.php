<?php

namespace Pawon\Flash;

use Psr\Http\Message\ServerRequestInterface as Request;

class FlashContextProcessor
{
    /**
     *
     */
    public function __invoke(Request $request)
    {
        $flash = $request->getAttribute('_messages');

        return [
            'messages' => $flash->get($request),
        ];
    }
}
