<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class JoinTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testBasic()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id'])->toSql();
        $this->assertEquals('SELECT a.*,g.* FROM `users` a JOIN `groups` g ON g.id = u.group_id', $sql);
    }

    public function testLeft()
    {
        $sql = $this->select->joinLeft(['g' => 'groups'], ['g.id = u.group_id'])->toSql();
        $this->assertEquals('SELECT a.*,g.* FROM `users` a LEFT JOIN `groups` g ON g.id = u.group_id', $sql);
    }

    public function testRight()
    {
        $sql = $this->select->joinRight(['g' => 'groups'], ['g.id = u.group_id'])->toSql();
        $this->assertEquals('SELECT a.*,g.* FROM `users` a RIGHT JOIN `groups` g ON g.id = u.group_id', $sql);
    }

    public function testNoFields()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id'], [])->toSql();
        $this->assertEquals('SELECT a.* FROM `users` a JOIN `groups` g ON g.id = u.group_id', $sql);
    }

    public function testWithParams()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id AND a.uid = ?', 1000])->toSql();
        $this->assertEquals('SELECT a.*,g.* FROM `users` a JOIN `groups` g ON g.id = u.group_id AND a.uid = 1000', $sql);
    }

    public function testWithMultipleParams()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id AND a.uid = ? AND g.name = ?', 1000, 'admin'])->toSql();
        $this->assertEquals("SELECT a.*,g.* FROM `users` a JOIN `groups` g ON g.id = u.group_id AND a.uid = 1000 AND g.name = 'admin'", $sql);
    }

    public function testWithField()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id'], ['name as groupname'])->toSql();
        $this->assertEquals("SELECT a.*,g.`name` AS `groupname` FROM `users` a JOIN `groups` g ON g.id = u.group_id", $sql);
    }

    public function testClear()
    {
        $sql = $this->select->join(['g' => 'groups'], ['g.id = u.group_id'])->join()->toSql();
        $this->assertEquals('SELECT a.* FROM `users` a', $sql);
    }

    public function testWithNoTableAlias()
    {
        $sql = $this->select->join('groups', ['id = group_id'], ['groupname'])->toSql();
        $this->assertEquals("SELECT a.*,`groupname` FROM `users` a JOIN `groups` ON id = group_id", $sql);
    }
}