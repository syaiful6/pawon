<?php

namespace Pawon\Cookie;

use Headbanger\HashMap;
use function Itertools\any;
use function Itertools\sort;

class Morshel extends HashMap
{
    const LEGALCHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%&\'*+-.^_`|~:';

    private $reserved = [
        'expires'  => 'expires',
        'path'     => 'Path',
        'comment'  => 'Comment',
        'domain'   => 'Domain',
        'max-age'  => 'Max-Age',
        'secure'   => 'Secure',
        'httponly' => 'HttpOnly',
        'version'  => 'Version',
    ];

    private $flags = [
        'secure', 'httponly',
    ];

    protected $key;

    protected $value;

    protected $codedValue;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        foreach ($this->reserved as $key => $value) {
            $this[$key] = '';
        }
    }

    /**
     *
     */
    public function offsetSet($key, $value)
    {
        $k = strtolower($key);
        if (! array_key_exists($k, $this->reserved)) {
            throw new CookieException(sprintf(
                'Invalid Attribute %s',
                $key
            ));
        }
        parent::offsetSet($key, $value);
    }

    /**
     *
     */
    public function isReserved($k)
    {
        return array_key_exists(strtolower($k), $this->reserved);
    }

    /**
     *
     */
    public function set($key, $val, $codedValue, $legalChars = self::LEGALCHARS)
    {
        if ($this->isReserved($key)) {
            throw new CookieException(sprintf(
                'Attempt to set a reserved key: %s',
                $key
            ));
        }
        $nonvalid = any(function ($a) use ($legalChars) {
            return false === strstr($legalChars, $a);
        }, str_split($key));
        if ($nonvalid) {
            throw new CookieException(sprintf(
                'Illegal key value: %s',
                $key
            ));
        }

        $this->key = $key;
        $this->value = $val;
        $this->codedValue = $codedValue;
    }

    /**
     *
     */
    public function __toString()
    {
        return $this->getOutput();
    }

    /**
     *
     */
    public function getOutput($attrs = null, $header = 'Set-Cookie:')
    {
        return sprintf('%s %s', $header, $this->getOutputString($attrs));
    }

    /**
     *
     */
    public function getOutputString($attrs = null)
    {
        $result = [];
        // First, the key=value pair
        array_push($result, sprintf('%s=%s', $this->key, $this->value));
        if ($attrs === null) {
            $attrs = $this->reserved;
        }

        $items = sort($this->items(), function ($a, $b) {
            $res = strcmp($a[0], $b[0]);
            if ($res === 0) {
                $res = strcmp($a[1], $b[1]);
            }

            return $res;
        });
        foreach ($items as list($key, $value)) {
            if ($value === '') {
                continue;
            }
            if (! array_key_exists($key, $attrs)) {
                continue;
            }
            if ($key === 'expires') {
                $exp = false;
                if ($value instanceof \DateTime
                    || $value instanceof \DateTimeInterface) {
                    $exp = gmdate('D, d-M-Y H:i:s T', $value->format('U'));
                } elseif (! is_numeric($value)) {
                    $totime = strtotime($value);
                    if (false !== $totime && -1 !== $totime) {
                        $exp = gmdate('D, d-M-Y H:i:s T', $totime);
                    }
                } elseif (is_numeric($value)) {
                    $exp = gmdate('D, d-M-Y H:i:s T', $value);
                }
                if ($exp) {
                    array_push($result, sprintf(
                        '%s=%s',
                        $this->reserved[$key],
                        $exp
                    ));
                } else {
                    throw new CookieException(sprintf(
                        'Invalid Attribute cookie expires: %s',
                        $value
                    ));
                }
            } elseif ($key === 'max-age' && is_integer($value)) {
                array_push($result, sprintf('%s=%d', $this->reserved[$key], $value));
            } elseif ($key === 'secure') {
                array_push($result, $this->reserved[$key]);
            } elseif ($key === 'httponly') {
                array_push($result, $this->reserved[$key]);
            } else {
                array_push($result, sprintf('%s=%s', $this->reserved[$key], $value));
            }
        }

        return implode('; ', $result);
    }
}
