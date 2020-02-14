<?php

namespace StkTest\PDO\Select;

use Stk\PDO\Select;
use StkTest\Base;

class WhereTest extends Base
{
    /** @var Select */
    protected $select;

    protected function setUp(): void
    {
        $this->select = new Select($this->quoteFunc(), 'users');
    }

    public function testSimple()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE a=b', $this->select->where('a=b')->toSql());
    }

    public function testPlaceholderString()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a='b'", $this->select->where('a=?', 'b')->toSql());
    }

    public function testPlaceholderInteger()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a=1234", $this->select->where('a=?', 1234)->toSql());
    }

    public function testPlaceholderFloat()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a=1.234", $this->select->where('a=?', 1.234)->toSql());
    }

    public function testPlaceholderMulti()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE (a=1234 AND b='foo')", $this->select->where('(a=? AND b=?)', 1234, 'foo')->toSql());
    }

    public function testIsNull()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE a IS NULL', $this->select->where('a IS NULL')->toSql());
    }

    public function testIsNull2()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE a IS NULL', $this->select->where('a IS ?', null)->toSql());
    }

    public function testRegex()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a REGEX 'foo'", $this->select->where('a REGEX ?', 'foo')->toSql());
    }

    public function testStringArray()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a IN ('alice','bob')",
            $this->select->where('a IN ?', ['alice', 'bob'])->toSql());
    }

    public function testIntArray()
    {
        $this->assertEquals("SELECT a.* FROM `users` a WHERE a IN (234,6457)",
            $this->select->where('a IN ?', [234, 6457])->toSql());
    }

    public function testAnd()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE a IS NULL AND b > 2',
            $this->select->where('a IS NULL')->where('b > ?', 2)->toSql());
    }

    public function testOr()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE a IS NULL OR b > 2',
            $this->select->where('a IS NULL')->orWhere('b > ?', 2)->toSql());
    }

    public function testClear()
    {
        $this->assertEquals('SELECT a.* FROM `users` a', $this->select->where('a IS NULL')->where()->toSql());
    }

    public function testNested()
    {
        $this->assertEquals('SELECT a.* FROM `users` a WHERE (a = \'alive\' OR b > 34) OR (a = \'bob\' OR b < 2)',
            $this->select
                ->where('(a = ? OR b > ?)', 'alive', 34)
                ->orWhere('(a = ? OR b < ?)', 'bob', 2)->toSql());
    }

}