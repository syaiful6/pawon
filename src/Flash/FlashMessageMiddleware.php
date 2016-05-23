<?php

namespace Pawon\Flash;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlashMessageMiddleware
{

    protected $flash;

    protected $debug;

    /**
     *
     */
    public function __construct(FlashMessageInterface $flash, $debug = false)
    {
        $this->flash = $flash;
        $this->debug = $debug;
    }

    /**
     *
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        $request = $request->withAttribute('_messages', $this->flash);

        $response = $next($request, $response);

        $unstored = $this->flash->update($response);
        if ($unstored && $this->debug) {
            throw new FlashUpdateException(
                'Not all temporary messages could be stored.'
            );
        }
        return $response;
    }
}
