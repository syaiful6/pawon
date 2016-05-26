<?php

namespace Pawon\Http;

use Traversable;
use Psr\Http\Message\StreamInterface;

class StreamingResponse extends Response
{
    /**
     *
     */
    public function __construct(
        $body,
        $status = 200,
        array $headers = []
    ) {

        parent::__construct(
            $this->createBody($body),
            $status,
            $headers
        );
    }

    /**
     *
     */
    public function isStreaming()
    {
        return true;
    }

    /**
     *
     */
    public function getIterator()
    {
        $body = $this->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        foreach ($body as $chunk) {
            yield $chunk;
        }
    }

    /**
     * Create the message body.
     *
     * @param Traversable|StreamInterface $html
     * @return StreamInterface
     * @throws InvalidArgumentException if $html is neither a string or stream.
     */
    private function createBody($body)
    {
        if ($body instanceof StreamInterface && $body instanceof Traversable) {
            return $body
        }

        if (!$body instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s. The body should Traversable',
                (is_object($html) ? get_class($html) : gettype($html)),
                __CLASS__
            ));
        }

        return new IterableStream($body);
    }
}
