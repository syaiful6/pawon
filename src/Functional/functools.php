<?php

namespace Pawon\Functional;

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
