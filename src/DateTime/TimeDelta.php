<?php

namespace Pawon\DateTime;

use DateInterval;

class TimeDelta extends DateInterval
{
    /**
     *
     */
    public function __construct(...$args)
    {
        $len = count($args);
        // check this is the format our parent know
        if ($len === 1 && is_string($args[0]) && ctype_alnum($args[0])) {
            parent::__construct($args[0]);
            $this->normalizeAttr();

            return;
        }
        if ($len > 7) {
            throw new \InvalidArgumentException('timedelta only accept max 7 args');
        }
        if ($len < 7) {
            $add = array_fill(0, 7 - $len, 0);
            $args = array_merge($args, $add);
        }
        list($years, $months, $weeks, $days, $hours, $minutes, $seconds) = $args;
        // normalize the week to days, minutes and hours to seconds
        // so our parent know how to deal with it
        $days += $weeks * 7;
        $seconds += ($minutes * 60) + ($hours * 3600);

        parent::__construct(
            sprintf('P%dY%dM%dDT%S', $year, $months, $days, $seconds)
        );
    }

    /**
     *
     */
    protected function normalizeAttr()
    {
        $args = array_map(function ($prop) {
            return $this->{$prop};
        }, ['h', 'i', 's']);
        list($hours, $minutes, $seconds) = $args;
        // we not store minutes and hours
        $seconds += ($minutes * 60) + ($hours * 3600);
        $this->s = $seconds;
        $this->i = $this->h = 0;
    }

    /**
     * years, months, weeks, days, hours, minutes, seconds.
     */
    public static function create(...$args)
    {
        return new static(...$args);
    }

    /**
     *
     */
    public function __callStatic($name, $args)
    {
        $arg = count($args) === 0 ? 1 : $args[0];
        // normalize years to year,months to month etc
        $method = $name[strlen($name) - 1] === 's'
            ? substr($name, 0, strlen($name) - 1)
            : $name;
        switch ($method) {
            case 'year':
                return static::create($arg);
            case 'month':
                return static::create(0, $arg);
            case 'week':
                return static::create(0, 0, $arg);
            case 'day':
                return static::create(0, 0, 0, $arg);
            case 'hour':
                return static::create(0, 0, 0, 0, $arg);
            case 'minute':
                return static::create(0, 0, 0, 0, 0, $arg);
            case 'second':
                return static::create(0, 0, 0, 0, 0, 0, $arg);

            default:
                throw new \BadMethodCallException(
                    "Method {$name} does not exist."
                );
        }
    }

    /**
     *
     */
    public function add(DateInterval $interval)
    {
        $sign = $interval->invert === 1 ? -1 : 1;
        if (static::createdFromDiff($interval)) {
            $this->d += $interval->days * $sign;
        } else {
            if (!$interval instanceof self) {
                $args = array_map(function ($p) use ($interval) {
                    return $interval->{$p};
                }, ['y', 'm', 'd', 'h', 'i', 's']);
                $interval = static::create(...$args);
            }
            $this->y += $interval->y * $sign;
            $this->m += $interval->m * $sign;
            $this->d += $interval->d * $sign;
            $this->s += $interval->s * $sign;
        }

        return $this;
    }

    /**
     *
     */
    public function sub(DateInterval $interval)
    {
        $sign = $interval->invert === 1 ? -1 : 1;
        if (static::createdFromDiff($interval)) {
            $this->d -= $interval->d * $sign;
        } else {
            if (!$interval instanceof self) {
                $args = array_map(function ($p) use ($interval) {
                    return $interval->{$p};
                }, ['y', 'm', 'd', 'h', 'i', 's']);
                $interval = static::create(...$args);
            }
            $this->y -= $interval->y * $sign;
            $this->m -= $interval->m * $sign;
            $this->d -= $interval->d * $sign;
            $this->s -= $interval->s * $sign;
        }

        return $this;
    }

    /**
     *
     */
    public function mul($int)
    {
        if (!is_integer($int)) {
            throw new \InvalidArgumentException(
                'Only support integer for mul()'
            );
        }

        $this->y = $this->y * $int;
        $this->m = $this->m * $int;
        $this->d = $this->d * $int;
        $this->s = $this->s * $int;

        return $this;
    }

    /**
     * Determine if the interval was created via DateTime:diff() or not.
     *
     * @param DateInterval $interval
     *
     * @return bool
     */
    private static function createdFromDiff(DateInterval $interval)
    {
        return $interval->days !== false && $interval->days !== -99999;
    }
}
