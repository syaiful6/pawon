<?php

namespace Pawon;

use Illuminate\Support\Str;
use Pawon\Core\ServiceManagerProxy;
use Zend\Expressive\Template\TemplateRendererInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
