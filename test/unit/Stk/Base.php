<?php

namespace StkTest;

use PHPUnit\Framework\TestCase;

class Base extends TestCase
{
    protected function quoteFunc()
    {
        return function ($v) {
            if (is_int($v)) {
                return $v;
            }

            if (is_float($v)) {
                return number_format($v, 3, '.', '');
            }

            return sprintf("'%s'", addslashes($v));
        };
    }

    public function testX()
    {
        $this->assertTrue(true);
    }
}