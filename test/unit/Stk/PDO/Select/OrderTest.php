<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class OrderTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testSimple()
    {
        $this->assertEquals('SELECT a.* FROM `users` a ORDER BY `a`', $this->select->order('a')->toSql());
    }

    public function testSimpleDesc()
    {
        $this->assertEquals('SELECT a.* FROM `users` a ORDER BY `a.name` DESC', $this->select->order('a.name', true)->toSql());
    }

    public function testRand()
    {
        $this->assertEquals('SELECT a.* FROM `users` a ORDER BY (RAND())', $this->select->order('(RAND())')->toSql());
    }

    public function testClear()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->order('a')->order()->toSql());
    }
}