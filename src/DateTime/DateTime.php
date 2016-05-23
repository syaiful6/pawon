<?php

namespace Pawon\DateTime;

use DateTimeZone;
use DateTime as BaseDateTime;

/**
 * Wrap DateTime to allow sync with our timezone settings.
 */
class DateTime extends BaseDateTime
{
    /**
     *
     */
    public function __construct($time = null, DateTimeZone $tz = null)
    {
        parent::__construct($time, static::attemptCreateTimeZone($tz));
    }

    /**
     *
     */
    protected static function attemptCreateTimeZone($tz)
    {
        if ($tz === null) {
            return new DateTimeZone(date_default_timezone_get());
        }

        if ($tz instanceof DateTimeZone) {
            return $tz;
        }

        $tz = @timezone_open((string) $tz);

        if ($tz === false) {
            throw new \InvalidArgumentException(
                'Unknown timezone ('.$tz.')'
            );
        }

        return $tz;
    }

    public static function now($tz = null)
    {
        return new static(null, $tz);
    }
}
