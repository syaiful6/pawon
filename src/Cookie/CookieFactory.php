<?php

namespace Pawon\Cookie;

/**
 * An interface for creating cookie
 */
interface CookieFactory
{
    /**
     * Create a cookie suitable for use on http header raw. The string returned
     * then can be used like header('Set-Cookie', $output).
     *
     * @param  string  $name
     * @param  string  $value
     * @param  mixed   $expires
     * @param  mixed   $maxAge
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly*
     * @return string
     *
     */
    public function make($name, $value, $expires = null, $maxAge = null, $path = null, $domain = null, $secure = false, $httpOnly = false);

    /**
     * Create a cookie that lasts "forever" (five years). The string returned can
     * be used on http raw.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly
     * @return string
     */
    public function forever($name, $value, $path = null, $domain = null, $secure = false, $httpOnly = false);

    /**
     * Expire the given cookie. This also return string suitable for use on http
     * header raw.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $domain
     * @return string
     */
    public function forget($name, $path = null, $domain = null);
}
