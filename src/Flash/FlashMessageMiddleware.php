<?php

namespace Pawon\Flash;

use Pawon\Http\Middleware\FrameInterface;
use Pawon\Http\Middleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlashMessageMiddleware implements MiddlewareInterface
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
    public function handle(Request $request, FrameInterface $frame)
    {
        $request = $request->withAttribute('_messages', $this->flash);

        $response = $frame->next($request);

        $unstored = $this->flash->update($response);
        if ($unstored && $this->debug) {
            throw new FlashUpdateException(
                'Not all temporary messages could be stored.'
            );
        }

        return $response;
    }
}
