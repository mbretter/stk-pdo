<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class UnionTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testAll()
    {
        $select2 = $this->select->from()->from('groups');
        $union = $this->select->union($select2);
        $this->assertEquals('(SELECT a.* FROM `users` a) UNION ALL (SELECT * FROM `groups`)', $union->toSql());
    }

    public function testNotAll()
    {
        $select2 = $this->select->from()->from('groups');
        $union = $this->select->union($select2, false);
        $this->assertEquals('(SELECT a.* FROM `users` a) UNION (SELECT * FROM `groups`)', $union->toSql());
    }

    public function testMultiple()
    {
        $select2 = $this->select->from()->from('groups');
        $select3 = $this->select->from()->from('permissions');
        $union = $this->select->union($select2)->union($select3);
        $this->assertEquals('(SELECT a.* FROM `users` a) UNION ALL (SELECT * FROM `groups`) UNION ALL (SELECT * FROM `permissions`)', $union->toSql());
    }

    public function testCount()
    {
        $select2 = $this->select->from()->from('groups');
        $union = $this->select->union($select2);
        $this->assertEquals('SELECT COUNT(*) FROM (SELECT a.* FROM `users` a) AS u', $union->count()->toSql());
    }

}