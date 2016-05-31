<?php

namespace Pawon\Tests\Functional;

use PHPUnit_Framework_TestCase;
use Pawon\Functional\Control\Just;
use Pawon\Functional\Control\Nothing;

function half($x)
{
	return $x % 2 == 0 ? new Just(floor($x/2)) : new Nothing();
}

class MaybeMonadTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testMaybeJustSimple()
    {
        $d = (new Just(3))->bind(__NAMESPACE__.'\\half');
        $this->assertTrue($d instanceof Nothing);
        $this->assertNull($d->extract());
    }

    /**
     *
     */
    public function testMaybeJustExtracting()
    {
        $d = new Just(5);
        $this->assertSame(5, $d->extract());
    }
}
