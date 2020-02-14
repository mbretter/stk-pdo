<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class LimitTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testSimple()
    {
        $this->assertEquals('SELECT a.* FROM `users` a LIMIT 10', $this->select->limit(10)->toSql());
    }

    public function testSkipCount()
    {
        $this->assertEquals('SELECT a.* FROM `users` a LIMIT 40,10', $this->select->limit(10, 40)->toSql());
    }

    public function testClear()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->limit(10)->limit()->toSql());
    }
}