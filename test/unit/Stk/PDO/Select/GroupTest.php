<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class GroupTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testSimple()
    {
        $this->assertEquals('SELECT a.* FROM `users` a GROUP BY `a`', $this->select->group('a')->toSql());
    }

    public function testClear()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->group('a')->group()->toSql());
    }
}