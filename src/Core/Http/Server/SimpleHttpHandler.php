<?php

namespace Pawon\Core\Http\Server;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Zend\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface as Request;

class SimpleHttpHandler extends BaseHttpHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $stdout;

    protected $stderr;

    protected $httpVersion = '1.0';

    protected $obLevel;

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
    protected function setupRequest()
    {
        $request = ServerRequestFactory::fromGlobals();
        $this->request = $request->withAttribute('pawon.file_wrapper', FileWrapper::class);
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
            $date = \DateTime::createFromFormat('U', time());
            $date->setTimezone(new \DateTimeZone('UTC'));
            header(sprintf('%s: %s', 'Date', $date->format('D, d M Y H:i:s').' GMT'));
        }
    }

    /**
     * Loops through the output buffer.
     *
     * @param int|null $maxBufferLevel Flush up to this buffer level.
     */
    protected function flush()
    {
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
        echo $data;
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
        if ($this->logger) {
            $this->logger->critical($e);
        }
    }
}
