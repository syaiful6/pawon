<?php

namespace Pawon\Core\Http\Server;

use Zend\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

class SimpleHttpHandler extends BaseHttpHandler
{
    protected $stdout;

    protected $stderr;

    protected $httpVersion = '1.0';

    protected $obLevel;
    /**
     *
     */
    public function __construct(
        StreamInterface $stdout = null,
        StreamInterface $stderr = null
    ) {

        $this->stdout = $stdout ?: new Stream('php://output', 'wb');
        $this->stderr = $this->stderr ?: new Stream('php://stderr', 'wb');
    }

    /**
     *
     */
    public function serve($app)
    {
        ob_start();

        $this->obLevel = ob_get_level();

        parent::serve($app);
    }

    /**
     *
     */
    protected function close()
    {
        try {
            if (method_exists($this->result, 'close')) {
                $this->result->close();
            }
        } finally {
            $this->headers = $this->request = $this->status = $this->result = null;

            $this->isHeaderSent = false;
            $this->obLevel = null;
            $this->sentLen = 0;
        }
    }

    /**
     *
     */
    protected function sendHeaders()
    {
        $this->cleanUpHeaders();
        $this->isHeaderSent = true; // mark the header
        $this->sendPreamble();
        foreach ($this->headers->items() as list($name, $value)) {
            $name = $this->filterHeader($name);
            $first = true;
            // psr use format ['key' => ['v1', v2]]
            foreach ((array) $value as $v) {
                header(sprintf('%s: %s', $name, $v), $first);
                $first = false;
            }
        }
    }

    /**
     *
     */
    protected function sendPreamble()
    {
        header(sprintf(
            'HTTP/%s %s',
            $this->httpVersion,
            $this->status
        ));

        if (!isset($this->headers['Date'])) {
            header(sprintf('%s: %s', 'Date', \DateTime::createFromFormat('U', time()));
        }
    }

    /**
     * Loops through the output buffer, flushing each, before emitting
     * the response.
     *
     * @param int|null $maxBufferLevel Flush up to this buffer level.
     */
    protected function flush()
    {
        if (method_exists($this->stdout, 'flush')) {
            $this->stdout->flush();

            return;
        }
        $maxBufferLevel = $this->obLevel;

        while (ob_get_level() > $maxBufferLevel) {
            ob_end_flush();
            flush();
        }
    }

    /**
     *
     */
    protected function doWrite($data)
    {
        $this->stdout->write($data);
    }

    /**
     *
     */
    protected function getStdin()
    {
        return $this->request->getBody();
    }

    /**
     *
     */
    protected function getStdout()
    {
        return $this->stdout;
    }

    /**
     *
     */
    protected function getStdErr()
    {
        return $this->stderr;
    }

    /**
     * Filter a header name to wordcase.
     *
     * @param string $header
     *
     * @return string
     */
    private function filterHeader($header)
    {
        $filtered = str_replace('-', ' ', $header);
        $filtered = ucwords($filtered);

        return str_replace(' ', '-', $filtered);
    }

    /**
     *
     */
    protected function logException($e)
    {
        $stderr = $this->getStdErr();
        $stderr->write($e->getTraceAsString());
    }
}
