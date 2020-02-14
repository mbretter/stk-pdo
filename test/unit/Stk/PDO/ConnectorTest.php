<?php

namespace StkTest\PDO;

use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stk\Immutable\Record;
use PDO;
use Stk;

class ConntectorTest extends TestCase
{
    /** @var PDO|MockObject */
    protected $pdo;

    /** @var PDOStatement|MockObject */
    protected $statement;

    /** @var PDO */
    protected $connector;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->pdo->method('quote')->willReturnCallback(function($a)  {
            return addslashes($a);
        });

        $this->statement = $this->createMock(PDOStatement::class);

        $this->connector = new Stk\PDO\Connector($this->pdo, 'users');
    }

    // insert

    public function testInsert()
    {
        $data = [
            'id'  => 1234,
            'foo' => 'bar'
        ];
        $row  = new Record($data);

        $this->pdo->expects($this->once())->method('prepare')
            ->with('INSERT INTO `users` (`id`,`foo`) VALUES (?,?)')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->insert($row));
    }

    public function testInsertWithDateTime()
    {
        $data = [
            'id'      => 1234,
            'foo'     => 'bar',
            'created' => new DateTime('2020-02-14 23:32:18')
        ];
        $row  = new Record($data);

        $this->pdo->expects($this->once())->method('prepare')
            ->with('INSERT INTO `users` (`id`,`foo`,`created`) VALUES (?,?,?)')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with([
            1234,
            'bar',
            '2020-02-14 23:32:18'
        ])->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->insert($row));
    }

    // update

    public function testUpdate()
    {
        $data = [
            'foo'      => 'bar',
            'users_id' => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('UPDATE `users` SET `foo` = ? WHERE `users_id` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->update($row));
    }

    public function testUpdateWithKeyFields()
    {
        $data = [
            'foo'      => 'bar',
            'objectid' => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('UPDATE `users` SET `foo` = ? WHERE `objectid` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->update($row, ['objectid']));
    }

    public function testUpdateWithPkMapping()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('UPDATE `users` SET `foo` = ? WHERE `id` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);

        $this->connector->setPkMappings(['users' => 'id']);
        $this->assertEquals(1, $this->connector->update($row));

        $this->connector->setPkMappings(['users' => ['id']]);
        $this->assertEquals(1, $this->connector->update($row));
    }

    public function testUpdateWithPkScheme()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('UPDATE `users` SET `foo` = ? WHERE `id` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);

        $this->connector->setPkScheme('id');
        $this->assertEquals(1, $this->connector->update($row));
    }

    public function testUpdateNoKeyFound()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];
        $row  = new Record($data);

        $this->expectExceptionObject(new RuntimeException('No key found for updating the row!'));
        $this->connector->update($row);
    }

    // delete

    public function testDelete()
    {
        $data = [
            'users_id' => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('DELETE FROM `users` WHERE `users_id` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->delete($row));
    }

    public function testDeleteFailedPrepare()
    {
        $data = [
            'users_id' => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('DELETE FROM `users` WHERE `users_id` = ?')->willReturn(false);
        $this->statement->expects($this->never())->method('execute');
        $this->assertFalse($this->connector->delete($row));
    }

    public function testDeleteById()
    {
        $this->pdo->method('prepare')->with('DELETE FROM `users` WHERE `users_id` = ?')->willReturn($this->statement);
        $this->statement->method('execute')->with([1234])->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->deleteById(1234));
    }

    // query

    public function testQuery()
    {
        $select = $this->connector->select('SELECT * FROM bla');
        $this->pdo->method('query')->willReturn($this->statement);

        $this->assertSame($this->statement, $this->connector->query($select));
    }

    public function testFetch()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];
        $this->statement->method('fetch')->willReturn($data);
        $row = $this->connector->fetch($this->statement);

        $this->assertInstanceOf(Record::class, $row);
        $this->assertEquals($data, $row->get());
    }

    public function testFetchArray()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];
        $this->statement->method('fetch')->willReturn($data);
        $this->connector->setRowClassName(null);
        $row = $this->connector->fetch($this->statement);

        $this->assertIsArray($row);
        $this->assertEquals($data, $row);
    }

    public function testFetchEnded()
    {
        $this->statement->method('fetch')->willReturn(false);
        $row = $this->connector->fetch($this->statement);
        $this->assertNull($row);
    }

    public function testFindOne()
    {
        $data = [
            'foo' => 'bar',
            'id'  => 1234
        ];

        $select = $this->connector->select()->where('id = ?', 1234);
        $this->pdo->method('query')->willReturn($this->statement)->with('SELECT a.* FROM `users` a WHERE id = 1234');
        $this->statement->method('fetch')->willReturn($data);

        $row = $this->connector->findOne($select);
        $this->assertInstanceOf(Record::class, $row);
        $this->assertEquals($data, $row->get());
    }

    // dumb tests

    public function testGetPDO()
    {
        $this->connector->setPDO($this->pdo);
        $this->assertSame($this->pdo, $this->connector->getPDO());
    }

    public function testGetTable()
    {
        $this->connector->setTable('groups');
        $this->assertSame('groups', $this->connector->getTable());
    }

}