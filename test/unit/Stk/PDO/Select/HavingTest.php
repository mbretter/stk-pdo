<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class HavingTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testSimple()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING a=b', $this->select->having('a=b')->toSql());
    }

    public function testPlaceholderString()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a='b'", $this->select->having('a=?', 'b')->toSql());
    }

    public function testPlaceholderInteger()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a=1234", $this->select->having('a=?', 1234)->toSql());
    }

    public function testPlaceholderFloat()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a=1.234", $this->select->having('a=?', 1.234)->toSql());
    }

    public function testPlaceholderMulti()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING (a=1234 AND b='foo')", $this->select->having('(a=? AND b=?)', 1234, 'foo')->toSql());
    }

    public function testIsNull()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING a IS NULL', $this->select->having('a IS NULL')->toSql());
    }

    public function testIsNull2()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING a IS NULL', $this->select->having('a IS ?', null)->toSql());
    }

    public function testRegex()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a REGEX 'foo'", $this->select->having('a REGEX ?', 'foo')->toSql());
    }

    public function testStringArray()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a IN ('alice','bob')",
            $this->select->having('a IN ?', ['alice', 'bob'])->toSql());
    }

    public function testIntArray()
    {
        $this->assertEquals("SELECT a.* FROM `users` a HAVING a IN (234,6457)",
            $this->select->having('a IN ?', [234, 6457])->toSql());
    }

    public function testAnd()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING a IS NULL AND b > 2',
            $this->select->having('a IS NULL')->having('b > ?', 2)->toSql());
    }

    public function testOr()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING a IS NULL OR b > 2',
            $this->select->having('a IS NULL')->orHaving('b > ?', 2)->toSql());
    }

    public function testClear()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->having('a IS NULL')->having()->toSql());
    }

    public function testNested()
    {
        $this->assertEquals('SELECT a.* FROM `users` a HAVING (a = \'alive\' OR b > 34) OR (a = \'bob\' OR b < 2)',
            $this->select
                ->having('(a = ? OR b > ?)', 'alive', 34)
                ->orHaving('(a = ? OR b < ?)', 'bob', 2)->toSql());
    }

}