<?php

namespace Pawon\Flash\Storage;

use Pawon\Session\Store;
use Headbanger\ArrayList;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface as Response;
use function Itertools\to_array;

class Session extends BaseStorage
{
    const SESSION_KEY = '__messages__';
    /**
     *
     */
    protected $session;

    /**
     *
     */
    public function __construct(Store $store)
    {
        $this->session = $store;
        parent::__construct();
    }

    protected function storeMessages($messages, Response $response)
    {
        $len = count($messages);
        if ($len) {
            $this->session[static::SESSION_KEY] = $this->serializeMessages($messages);
        } else {
            try {
                $this->session->pop(static::SESSION_KEY, null);
            } catch (\OutOfBoundsException $e) {
                // pass, no item in session
            }
        }
        return [];
    }

    protected function getMessages()
    {
        $data = $this->session->get(static::SESSION_KEY, new ArrayList());
        if ($data) {
            $data = $this->unserializeMessages($data);
        }
        return [$data, true];
    }

    /**
     *
     */
    protected function serializeMessages($messages)
    {
        $message = to_array($messages);

        return $message;
    }

    /**
     *
     */
    protected function unserializeMessages($messages)
    {
        return new ArrayList($messages);
    }
}
