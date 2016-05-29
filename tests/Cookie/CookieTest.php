<?php

namespace Pawon\tests\Cookie;

use PHPUnit_Framework_TestCase;
use Pawon\Cookie\Cookie;
use Pawon\Cookie\Morshel;

class CookieTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testConstructorLoadString()
    {
        $cookie = new Cookie('theme=light; sessionToken=abc123');
        $this->assertTrue(isset($cookie['theme']));
        $this->assertTrue(isset($cookie['sessionToken']));
    }

    /**
     *
     */
    public function testSetAndPrintCookie()
    {
        $cookie = new Cookie();
        $cookie['foo'] = 'bar';

        $this->assertSame('Set-Cookie: foo=bar', (string) $cookie);
    }

    /**
     *
     */
    public function testGetItemCookieReturnMorshel()
    {
        $cookie = new Cookie();
        $cookie['foo'] = 'bar';
        // morshel make it easier to format the cookie item. The cookie object
        // just act as container
        $this->assertTrue($cookie['foo'] instanceof Morshel);
    }
}
