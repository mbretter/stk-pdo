<?php

namespace StkTest\PDO;

use Stk\PDO\Select;
use StkTest\Base;

class SelectTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testDefauls()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->toSql());
    }

    public function testDistinct()
    {
        $this->assertEquals('SELECT DISTINCT a.* FROM `users` a', $this->select->distinct()->toSql());
    }

    public function testCount()
    {
        $this->assertEquals('SELECT COUNT(*) FROM `users` a', $this->select->count());
    }

    public function testCountDistinct()
    {
        $this->assertEquals('SELECT COUNT(DISTINCT groupname) FROM `users` a', $this->select->distinct()->count('groupname'));
    }

    public function testAddColumn()
    {
        $this->assertEquals('SELECT a.*,a.`foo` AS `bar` FROM `users` a', $this->select->column('foo as bar')->toSql());
    }

    public function testClearAddColumn()
    {
        $this->assertEquals('SELECT (MIN(foo)) AS `bar` FROM `users` a', $this->select->column()->column('(MIN(foo)) as bar')->toSql());
    }

    public function testAddCalculatedColumn()
    {
        $this->assertEquals('SELECT a.*,(MIN(foo)) AS `bar` FROM `users` a', $this->select->column('(MIN(foo)) as bar')->toSql());
    }
}
