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
     * @return
     */
    public function make($body = '', $status = 200, array $headers = []);

    /**
     * Make a stream to be used as body on request. The stream should readable
     *
     * @param mixed $data
     * @return Psr\Http\Message\StreamInterface
     */
    public function makeStream($data = null);
}
