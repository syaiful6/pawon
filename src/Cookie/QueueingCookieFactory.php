<?php

namespace Pawon\Cookie;

interface QueueingCookieFactory extends CookieFactory
{
    /**
     * Queue a given cookie, if this method called with one argument then queue it
     *
     * @param  string  $name
     * @param  string  $value
     * @param  mixed   $expires
     * @param  mixed   $maxAge
     * @param  string  $path
     * @param  string  $domain
     * @param  bool    $secure
     * @param  bool    $httpOnly*
     * @return void
     */
    public function queue($name, $value, $expires = null, $maxAge = null, $path = null, $domain = null, $secure = false, $httpOnly = false);

    /**
     * Remove the given cookie name from queue
     *
     * @param string
     * @return void
     */
    public function unqueue($name);

    /**
     * Get queued cookie, all item should be usable on http header raw.
     *
     * @return array
     */
    public function getQueuedCookies();
}
