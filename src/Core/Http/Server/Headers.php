<?php

namespace Pawon\Core\Http\Server;

use Countable;
use ArrayAccess;

class Headers implements Countable, ArrayAccess
{
    protected $headers;

    /**
     *
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     *
     */
    public function count()
    {
        return count($this->headers);
    }

    /**
     * Get a header value by the name. When the header is not found, then it
     * return whatever you passed to argument 2.
     *
     * @param string           $name    The header name
     * @param callable|scallar $default The default value to return when missing
     */
    public function get($name, $default = null)
    {
        $name = strtolower($name);
        foreach ($this->headers as list($k, $v)) {
            if (strtolower($k) === $name) {
                return $v;
            }
        }

        return $default;
    }

    /**
     *
     */
    public function getAll($name)
    {
        $newheader = [];
        $name = strtolower($name);
        foreach ($this->headers as list($k, $v)) {
            if (strtolower($k) === $name) {
                $newheader[] = [$k, $v];
            }
        }

        return $newheader;
    }

    /**
     * Return an array of all the message's header field names including
     * duplicates.
     *
     * @return array
     */
    public function keys()
    {
        return array_map(function ($elem) {
            return $elem[0];
        }, $this->headers);
    }

    /**
     * @return array The array of values fields header in the current message
     */
    public function values()
    {
        return array_map(function ($elem) {
            return $elem[1];
        }, $this->headers);
    }

    /**
     * @return array The array of header names and values with format element
     *               0 is the key and element 1 is the value.
     */
    public function items()
    {
        return array_map(null, $this->headers);
    }

    /**
     *
     */
    public function offsetSet($name, $value)
    {
        $this->headers[] = [(string) $name, (string) $value];
    }

    /**
     *
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * [offsetExists description].
     *
     * @param [type] $name [description]
     *
     * @return [type] [description]
     */
    public function offsetExists($name)
    {
        return (bool) $this->get($name, false);
    }

    /**
     * [offsetUnset description].
     *
     * @param [type] $name [description]
     *
     * @return [type] [description]
     */
    public function offsetUnset($name)
    {
        $newheader = [];
        $name = strtolower($name);
        foreach ($this->headers as list($k, $v)) {
            if (strtolower($k) !== $name) {
                $newheader[] = [$k, $v];
            }
        }
        $this->headers = $newheader;
    }

    /**
     * Add new header.
     */
    public function addHeader($name, $value, array $params = [])
    {
        $parts = [];
        foreach ($params as $k => $v) {
            $rem = str_replace('_', '-', $k);
            if (!$v) {
                $parts[] = $rem;
            } else {
                $parts[] = $this->formatParam($rem, $v);
            }
        }
        if ($value !== null) {
            array_unshift($parts, $value);
        }
        $this[$name] = implode('; ', $parts);
    }

    /**
     *
     */
    public function setDefault($name, $value)
    {
        $res = $this->get($name, false);
        if ($res === false) {
            $this[$name] = $value;

            return $value;
        } else {
            return $res;
        }
    }

    /**
     *
     */
    private function formatParam($param, $value = null, $quote = 1)
    {
        if ($value !== null && strlen($value) > 0) {
            if ($quote || preg_match('#[ \(\)<>@,;:\\"/\[\]\?=]#', $value)) {
                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\\"', $value);

                return sprintf('%s="%s"', $param, $value);
            } else {
                return sprintf('%s=%s', $param, $value);
            }
        }

        return $param;
    }
}
