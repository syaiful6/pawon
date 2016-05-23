<?php

namespace Pawon\Flash;

use Serializable;

class Message implements Serializable
{
    protected $level;

    protected $message;

    protected $extraTag;

    protected $tagMaps = [
        FlashMessageInterface::DEBUG => 'debug',
        FlashMessageInterface::INFO => 'info',
        FlashMessageInterface::SUCCESS => 'success',
        FlashMessageInterface::WARNING => 'warning',
        FlashMessageInterface::ERROR => 'error'
    ];

    /**
     *
     */
    public function __construct($level, $message, $extraTag = '')
    {
        $this->level = $level;
        $this->message = $message;
        $this->extraTag = $extraTag;
    }

    /**
     *
     */
    public function serialize()
    {
        $data = [$this->level, $this->message, $this->extraTag];

        return serialize($data);
    }

    /**
     *
     */
    public function unserialize($str)
    {
        list($this->level, $this->message, $this->extraTag) = unserialize($str);
    }

    /**
     *
     */
    public function tags()
    {
        $extraTag = $this->extraTag;
        if ($extraTag && $this->levelTag()) {
            return join(' ', [$extraTag, $this->levelTag()]);
        } elseif ($extraTag) {
            return $extraTag;
        } elseif ($this->levelTag) {
            return $this->levelTag();
        }
        return '';
    }

    /**
     *
     */
    public function levelTag()
    {
        return isset($this->tagMaps[$this->level]) ? $this->tagMaps[$this->level] : '';
    }

    /**
     *
     */
    public function __toString()
    {
        return $this->message;
    }

    /**
     *
     */
    public function __get($name)
    {
        if (in_array($name, ['levelTag', 'tags'])) {
            return $this->$name();
        }
        if (property_exists(get_class($this), $name)) {
            return $this->$name;
        }
    }
}
