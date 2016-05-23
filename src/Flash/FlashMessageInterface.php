<?php

namespace Pawon\Flash;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface FlashMessageInterface
{
    const DEBUG = 10;
    const INFO = 20;
    const SUCCESS = 25;
    const WARNING = 30;
    const ERROR = 40;

    /**
     * Attempts to add a message
     */
    public function add($level, $message, $extraTag = '');

    /**
     *
     */
    public function debug($message, $extraTag = '');

    /**
     *
     */
    public function info($message, $extraTag = '');

    /**
     *
     */
    public function success($message, $extraTag = '');

    /**
     *
     */
    public function warning($message, $extraTag = '');

    /**
     *
     */
    public function error($message, $extraTag = '');

    /**
     *
     */
    public function update(Response $response);

    /**
     * Returns the message storage
     */
    public function get(Request $request);

    /**
     * set level
     */
    public function setLevel($level);

    /**
     *
     */
    public function getLevel();
}
