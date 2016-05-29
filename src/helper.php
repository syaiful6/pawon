<?php

namespace Pawon;

use Headbanger\Set;
use Headbanger\BaseSet;
use Headbanger\ArrayList;
use Illuminate\Support\Str;
use Itertools\StopIteration;
use Pawon\Functional\PlaceHolder;
use Pawon\Functional\Singledispatch;
use Pawon\Core\ServiceManagerProxy;
use Zend\Expressive\Template\TemplateRendererInterface;
use Symfony\Component\Translation\TranslatorInterface;
use function Itertools\take;
use function Itertools\map;

/**
 * Gets the value of an environment variable. Supports boolean, empty and null.
 *
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;

        case 'false':
        case '(false)':
            return false;

        case 'empty':
        case '(empty)':
            return '';

        case 'null':
        case '(null)':
            return;
    }

    if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
        return substr($value, 1, -1);
    }

    return $value;
}

/**
 *
 */
function service($name = null)
{
    if ($name === null) {
        return ServiceManagerProxy::getInstance();
    }

    return ServiceManagerProxy::getInstance()->get($name);
}

/**
 *
 */
function trans($id = null, $parameters = [], $domain = 'messages', $locale = null)
{
    if ($id === null) {
        return service(TranslatorInterface::class);
    }

    return service(TranslatorInterface::class)->trans($id, $parameters, $domain, $locale);
}

/**
 *
 */
function template($name = null, array $params = [])
{
    if ($name === null) {
        return service(TemplateRendererInterface::class);
    }

    return service(TemplateRendererInterface::class)->render($name, $params);
}

/**
 *
 */
function invoke($toCall, ...$args)
{
    if (is_callable($toCall)) {
        return $toCall(...$args);
    }

    return new $toCall(...$args);
}

/**
 * Partial application
 */
function partial($fn, ...$args)
{
    return function (...$params) use ($fn, $args) {
        return $fn(...merge_left_params($args, $params));
    };
}

/**
*
*/
function partial_right($fn, ...$args)
{
    return function (...$params) use ($fn, $args) {
        return $fn(...merge_right_params($args, $params));
    };
}

/**
 *
 */
function single_dispatch(callable $fn)
{
    return new Singledispatch($fn);
}

/**
 * Mark the parameter as placeholder
 */
function _()
{
    return PlaceHolder::create();
}

/**
 *
 */
function merge_left_params($left, $right)
{
    resolve_placeholder($left, $right);
    return array_merge($left, $right);
}

/**
 *
 */
function merge_right_params($left, $right)
{
    resolve_placeholder($left, $right);
    return array_merge($right, $left);
}

/**
 *
 */
function resolve_placeholder(array &$parameters, array &$source)
{
    foreach ($parameters as $position => &$param) {
        if ($param instanceof PlaceHolder) {
            if (count($source) === 0) {
                throw new \RuntimeException(
                    'Cant resolve placeholder. the source is empty'
                );
            }
            $param = array_shift($source);
        }
    }
}

/**
 * Return a random int in the range [0,n]
 */
function random_below($n)
{
    return mt_rand(0, $n - 1);
}

/**
 *
 */
function choose_sequence($sequence, $index)
{
    return $sequence[$index];
}

/**
 *
 */
function random_choice($seq)
{
    if (is_array($seq) || ($seq instanceof \ArrayAccess
        && $seq instanceof \Countable)) {
        return choose_sequence($seq, random_below(count($seq)));
    }

    throw new \InvalidArgumentException(
        'argument 1 must be array or instanceof ArrayAccess and Countable'
    );
}

/**
 * infinite iterator that yield random below $n, over, over and over forever.
 */
function random_sequence($n)
{
    while (true) {
        yield random_below($n);
    }
}

/**
 *
 */
function unique_everseen($iterable, callable $key = null)
{
    $seen = new Set();
    if ($key === null) {
        $key = function ($k) {
            return $k;
        };
    }
    foreach ($iterable as $elem) {
        $k = $key($elem);
        if (!$seen->contains($k)) {
            $seen->add($k);
            yield $elem;
        }
    }
}

/**
 *
 */
function random_sample($population, $k)
{
    if ($population instanceof BaseSet) {
        $population = new ArrayList($population);
    } elseif ($population instanceof \Traversable) {
        $population = iterator_to_array($population);
    }

    $n = count($population);
    if (! (0 <= $k && $k <= $n)) {
        throw new \LogicException('Sample larger than population');
    }
    $generator = unique_everseen(random_sequence($n));
    $result = map(partial(__NAMESPACE__.'\\choose_sequence', $population), $generator);

    return take($k, $result);
}

/**
 *
 */
function urlsafe_base64_encode($s)
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), "\n=");
}

/**
 *
 */
function urlsafe_base64_decode($s)
{
    $padded = $s.str_repeat('=', strlen($s) % 4);
    return base64_decode(strtr($padded, '-_', '+/'));
}
