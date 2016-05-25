<?php

namespace Pawon\Http;

interface ResponseFactoryInterface
{
    /**
     * Make new response based provided arguments.
     *
     * @param string|StreamInterface $body
     * @param string $status
     * @param string $headers
     * @return Psr\Http\Message\StreamInterface
     */
    public function make($body = '', $status = 200, array $headers = []);
}
