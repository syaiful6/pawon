<?php

namespace Pawon\Http;

use Traversable;
use Zend\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     *
     */
    public function make($body = '', $status = 200, array $headers = [])
    {
        $body = $this->makeStream($body);

        if ($body instanceof Traversable) {
            return new StreamingResponse($body, $status, $headers);
        }

        return new Response($body, $status, $headers);
    }

    /**
     *
     */
    public function makeStream($body = null)
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if ($body === null || is_string($body)) {
            $stream = new Stream('php://memory', 'wb+');
            $body = (string) $body;
            $stream->write($body);
            $stream->rewind();
            return $stream;
        }

        if ($body instanceof Traversable) {
            return new IterableStream($body);
        }

        throw new \InvalidArgumentException('invalid body parameter');
    }
}
