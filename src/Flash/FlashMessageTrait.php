<?php

namespace Pawon\Flash;

trait FlashMessageTrait
{
    /**
     *
     */
    public function debug($message, $extraTags = '')
    {
        return $this->add(FlashMessageInterface::DEBUG, $message, $extraTags);
    }

    /**
     *
     */
    public function info($message, $extraTags = '')
    {
        return $this->add(FlashMessageInterface::INFO, $message, $extraTags);
    }

    /**
     *
     */
    public function success($message, $extraTags = '')
    {
        return $this->add(FlashMessageInterface::SUCCESS, $message, $extraTags);
    }

    /**
     *
     */
    public function warning($message, $extraTags = '')
    {
        return $this->add(FlashMessageInterface::WARNING, $message, $extraTags);
    }

    /**
     *
     */
    public function error($message, $extraTags = '')
    {
        return $this->add(FlashMessageInterface::ERROR, $message, $extraTags);
    }
}
