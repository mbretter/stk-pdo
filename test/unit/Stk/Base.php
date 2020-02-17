<?php

namespace StkTest;

use PHPUnit\Framework\TestCase;

class Base extends TestCase
{
    protected function quoteFunc()
    {
        return function ($v) {
            if (is_int($v) || is_float($v)) {
                return $v;
            }

            return sprintf("'%s'", addslashes($v));
        };
    }

    public function testX()
    {
        $this->assertTrue(true);
    }
}