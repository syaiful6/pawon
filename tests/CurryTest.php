<?php

namespace Pawon\tests;

use Pawon\Functional\Curry;
use PHPUnit_Framework_TestCase;
use function Pawon\curry;

// for test
function add($a, $b, $c)
{
    return $a + $b + $c;
}

class CurryTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    protected function getCurry()
    {
        return new Curry(__NAMESPACE__.'\\add');
    }

    /**
     *
     */
    public function testCurryCallOneArgumentReturnCurry()
    {
        $curry = $this->getCurry();
        $curried = $curry(1);

        $this->assertTrue($curried instanceof Curry);
        $this->assertNotSame($curry, $curried);
    }

    /**
     *
     */
    public function testCurryMoreThanRequiredArgsIgnored()
    {
        $curry = $this->getCurry();
        $curried = $curry(1);

        $res = $curried(2, 3, 4);
        $this->assertSame(6, $res);
    }

    /**
     *
     */
    public function testCurryCanbeCalledWithoutArgument()
    {
        $curry = $this->getCurry();
        $curried = $curry(1);

        $again = $curried();
        $this->assertTrue($curried instanceof Curry);
        $this->assertNotSame($again, $curried);
    }

    /**
     *
     */
    public function testCallCurriedTwiceOk()
    {
        $map = new Curry('array_map');
        $mapUpper = $map('strtoupper');

        $this->assertSame(['A', 'B', 'C'], $mapUpper(['a', 'B', 'c']));
        $this->assertSame(['D', 'E', 'F'], $mapUpper(['D', 'e', 'f']));
        // this sould oke
        $mapLower = $map('strtolower');
        $this->assertSame(['a', 'b', 'c'], $mapLower(['A', 'b', 'C']));
    }

    /**
     *
     */
    public function testCurryBuiltin()
    {
        // array map is so always used
        $map = new Curry('array_map');
        $map = $map('strtoupper');

        $this->assertSame(['A', 'B', 'C'], $map(['a', 'B', 'c']));
    }

    /**
     *
     */
    public function testCallCurryOneAndMore()
    {
        $curry = $this->getCurry();
        $curried = $curry(1);

        $res = $curried(2, 3);

        $this->assertEquals(6, $res);
    }

    /**
     *
     */
    public function testCurryHelper()
    {
        $curried = curry(__NAMESPACE__.'\\add', 1, 2);
        $this->assertSame(6, $curried(3));
    }
}
