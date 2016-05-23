<?php

namespace Pawon\Http;

use Countable;
use Traversable;
use RuntimeException;
use IteratorAggregate;
use Zend\Stratigility\Http\Response as BaseResponse;

/**
 * To bypass stratigility from decorated our response
 */
class Response extends BaseResponse implements IteratorAggregate
{
    /**
     *
     */
    public function getIterator()
    {
        $body = $this->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        if (!$this->isStreaming()) {
            yield $body->getContents();
        } else {
            foreach ($body as $chunk) {
                yield $chunk;
            }
        }
    }

    /**
     *
     */
    public function isStreaming()
    {
        $body = $this->getBody();

        return $body instanceof Traversable && (! $body instanceof Countable
            || $body->getSize() === null);
    }

    /**
     *
     */
    public function close()
    {
        $body = $this->getBody();
        $body->close();
        // To Do: release an event here
    }
}
