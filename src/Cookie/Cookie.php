<?php

namespace Pawon\Cookie;

use Headbanger\HashMap;
use function Itertools\zip_longest;

class Cookie extends HashMap
{
    const LEGAL_CHAR_PATTERN = '[\w\d!#%&\'~_`><@,:/\$\*\+\-\.\^\|\)\(\?\}\{\=]';

    const COOKIE_PATTERN = <<<'REGEX'
(?x)                           # This is a verbose pattern
    \s*                            # Optional whitespace at start of cookie
    (?P<key>                       # Start of group 'key'
    [\w\d!#%&'~_`><@,:\/\$\*\+\-\.\^\|\)\(\?\}\{\=]+?   # Any word of at least one letter
    )                              # End of group 'key'
    (                              # Optional group: there may not be a value.
    \s*=\s*                          # Equal Sign
    (?P<val>                         # Start of group 'val'
    "(?:[^\\\"]|\\.)*"                  # Any doublequoted string
    |                                  # or
    \w{3},\s[\w\d\s-]{9,11}\s[\d:]{8}\sGMT  # Special case for "expires" attr
    |                                  # or
    [\w\d!#%&'~_`><@,:\/\$\*\+\-\.\^\|\)\(\?\}\{\=]*      # Any word or empty string
    )                                # End of group 'val'
    )?                             # End of optional value group
    \s*                            # Any number of spaces.
(\s+|;|$)                      # Ending either at space, semicolon, or EOS.
REGEX;

    /**
     *
     */
    public function __construct($load = null)
    {
        parent::__construct();
        if ($load !== null) {
            $this->load($load);
        }
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
    protected function internalSet($key, $realValue, $codedValue)
    {
        $morshel = $this->get($key, new Morshel());
        $morshel->set($key, $realValue, $codedValue);
        $this[$key] = $morshel;
    }

    /**
     *
     */
    public function offsetSet($key, $value)
    {
        if ($value instanceof Morshel) {
            parent::offsetSet($key, $value);
        } else {
            list($rval, $cval) = $this->valueEncode($value);
            $this->internalSet($key, $rval, $cval);
        }
    }

    /**
     *
     */
    public function getOutput(
        $attrs = null,
        $header = 'Set-Cookie:',
        $sep = "\r\n"
    ) {
        $result = [];
        foreach ($this->values() as $value) {
            array_push($result, $value->getOutput($attrs, $header));
        }

        return implode($sep, $result);
    }

    /**
     *
     */
    public function load($raw)
    {
        if (is_string($raw)) {
            return $this->loadString($raw);
        }

        $this->update($raw);
    }

    /**
     *
     */
    protected function loadString($str)
    {
        $i = 0;
        $n = strlen($str);
        $morshel = null;

        $reserverd = explode(' ', 'expires path comment domain max-age secure httponly version');
        $secure = explode(' ', 'secure httponly');

        if (preg_match_all('/'.self::COOKIE_PATTERN.'/m', $str, $matches)) {
            foreach (zip_longest($matches['key'], $matches['val']) as list($key, $val)) {
                if ($key[0] === '$') {
                    if ($morshel) {
                        $morshel[substr($key, 1)] = $val;
                    }
                } elseif (in_array(strtolower($key), $reserverd)) {
                    if ($morshel) {
                        if (! $val) {
                            if (in_array(strtolower($key), $flags)) {
                                $morshel[$key] = true;
                            }
                        } else {
                            $morshel[$key] = urldecode($val);
                        }
                    }
                } elseif ($val !== '' || $val !== null) {
                    list($rval, $cval) = $this->valueDecode($val);
                    $this->internalSet($key, $rval, $cval);
                    $morshel = $this[$key];
                }
            }
        }
    }

    /**
     *
     */
    protected function valueDecode($value)
    {
        return [$value, $value];
    }

    /**
     *
     */
    protected function valueEncode($value)
    {
        return [$value, $value];
    }
}
