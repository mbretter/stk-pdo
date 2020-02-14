<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class FromTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testFrom()
    {
        $this->assertEquals('SELECT a.*,g.* FROM `users` a,`groups` g', $this->select->from(['g' => 'groups'], ['*'])->toSql());
    }

    public function testFromWithClear()
    {
        $this->assertEquals('SELECT g.* FROM `groups` g', $this->select->from()->from(['g' => 'groups'], ['*'])->toSql());
        $this->assertEquals('SELECT g.* FROM `groups` g', $this->select->from()->from(['g' => 'groups'])->toSql());
    }

    public function testFromNoAlias()
    {
        $this->assertEquals('SELECT * FROM `groups`',
            $this->select->from()->from('groups', ['*'])->toSql());
    }

}