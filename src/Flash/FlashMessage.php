<?php

namespace Pawon\Flash;

use Pawon\Flash\Storage\BaseStorage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FlashMessage implements FlashMessageInterface
{
    use FlashMessageTrait;

    protected $storage;

    /**
     *
     */
    public function __construct(BaseStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     *
     */
    public function add($level, $message, $extraTag = '')
    {
        return $this->storage->add($level, $message, $extraTag);
    }

    /**
     *
     */
    public function update(Response $response)
    {
        return $this->storage->update($response);
    }

    /**
     *
     */
    public function get(Request $request)
    {
        return $this->storage;
    }

    /**
     *
     */
    public function setLevel($level)
    {
        $this->storage->setLevel($level);
    }

    /**
     *
     */
    public function getLevel()
    {
        return $this->storage->getLevel();
    }
}
