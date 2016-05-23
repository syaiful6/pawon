<?php

namespace Pawon\Flash\Storage;

use Countable;
use IteratorAggregate;
use Headbanger\ArrayList;
use Pawon\Flash\Message;
use Pawon\Flash\FlashMessageInterface as FlashMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class BaseStorage implements Countable, IteratorAggregate
{
    /**
     *
     */
    protected $used = false;

    /**
     *
     */
    protected $addedNew = false;

    /**
     *
     */
    protected $queued = [];

    /**
     *
     */
    protected $loaded = [];

    /**
     *
     */
    protected $level;

    /**
     *
     */
    public function __construct()
    {
        $this->loadedMessage();
        $this->queued = new ArrayList();
    }

    /**
     *
     */
    public function count()
    {
        return count($this->loaded) + count($this->queued);
    }

    /**
     *
     */
    public function getIterator()
    {
        $this->used = true;
        if (count($this->queued)) {
            $this->loaded->extend($this->queued);
            $this->queued->clear();
        }
        foreach ($this->loaded as $item) {
            yield $item;
        }
    }

    /**
     *
     */
    public function contains($item)
    {
        return $this->loaded->contains($item) || $this->queued->contains($item);
    }

    /**
     *
     */
    public function update(Response $response)
    {
        if ($this->used) {
            return $this->storeMessages($this->queued, $response);
        } elseif ($this->addedNew) {
            $this->loaded->extend($this->queued);
            $message = $this->loaded;
            return $this->storeMessages($message, $response);
        }
    }

    /**
     *
     */
    public function add($level, $message, $extraTags = '')
    {
        if (!$message) {
            return;
        }
        $level = (int) $level;
        if ($level < $this->getLevel()) {
            return;
        }

        $this->addedNew = true;
        $message = new Message($level, $message, $extraTags);
        $this->queued->push($message);
    }

    /**
     *
     */
    public function setLevel($level)
    {
        if (filter_var($level, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException(sprintf(
                'level must be integer, %s given',
                gettype($level)
            ));
        }
        $this->level = $level;
    }

    /**
     *
     */
    public function getLevel()
    {
        return $this->level ? $this->level : FlashMessage::INFO;
    }

    /**
     *
     */
    protected function loadedMessage()
    {
        list($message, $retrieved) = $this->getMessages();
        $this->loaded = $message;
    }

    /**
     *
     */
    abstract protected function getMessages();

    /**
     *
     */
    abstract protected function storeMessages($message, Response $response);
}
