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

    public function testLeftMultiple()
    {
        $sql = $this->select->join(['iptc' => 'iptc'], ['iptc.pic_id = a.pic_id'], ['a', 'b'])
            ->joinLeft(['pr' => 'pic_relation'], ['pr.pic_id = a.pic_id'], [])
            ->joinLeft(['c' => 'category'], ['c.id = pr.category_id'], ['name as agency'])
            ->joinLeft(['ag' => 'agency'], ['ag.id = pr.agency_id'], ['name as agency'])
            ->joinLeft(['ph' => 'photographer'], ['ph.id = pr.photographer_id'], ['firstname', 'lastname'])
            ->order('pic_id', true)->toSql();
        $this->assertEquals('SELECT a.*,iptc.`a`,iptc.`b`,c.`name` AS `agency`,ag.`name` AS `agency`,ph.`firstname`,ph.`lastname` FROM `users` a JOIN `iptc` iptc ON iptc.pic_id = a.pic_id LEFT JOIN `pic_relation` pr ON pr.pic_id = a.pic_id LEFT JOIN `category` c ON c.id = pr.category_id LEFT JOIN `agency` ag ON ag.id = pr.agency_id LEFT JOIN `photographer` ph ON ph.id = pr.photographer_id ORDER BY `pic_id` DESC', $sql);
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