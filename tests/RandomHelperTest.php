<?php

namespace Pawon\Tests;

use PHPUnit_Framework_TestCase;
use function Pawon\random_sample;
use function Pawon\random_choice;

class RandomHelperTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testRandomChoice()
    {
        $data = [
            'foo',
            'bar',
            'baz',
            'daz',
        ];

        $choosen = random_choice($data);

        $this->assertTrue(in_array($choosen, $data));
    }

    /**
     *
     */
    public function testRandomSampleData()
    {
        $sample = random_sample(range(0, 20), 5);
        $this->assertCount(5, $sample);
    }
}
