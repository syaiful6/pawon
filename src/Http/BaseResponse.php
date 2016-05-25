<?php

namespace Pawon\Http;

use Countable;
use Traversable;
use RuntimeException;
use IteratorAggregate;
use Zend\Diactoros\Response as DiactorosResponse;

abstract class BaseResponse extends DiactorosResponse implements IteratorAggregate
{

    private $closeable = [];

    /**
     *
     */
    public function getIterator()
    {
        $body = $this->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        yield $body->getContents();
    }

    /**
     *
     */
    public function isStreaming()
    {
        return false;
    }

    /**
     *
     */
    public function addCloseable($obj)
    {
        array_push($this->closeable, $obj);
    }

    /**
     *
     */
    public function removeCloseable($obj)
    {
        $closeable = array_filter($this->closeable, function ($item) use ($obj) {
            return $item !== $obj;
        });

        $this->closeable = $closeable;
    }

    /**
     *
     */
    public function close()
    {
        $body = $this->getBody();
        $body->close();
        foreach ($this->closeable as $cl) {
            try {
                if (is_callable($cl)) {
                    $cl();
                } else {
                    $cl->close();
                }
            } catch (\Exception $e) {
                // we can't do much about this
            }
        }
    }
}
