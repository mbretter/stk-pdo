<?php

namespace StkTest\PDO;

use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stk\Immutable\Record;
use PDO;
use Stk;
use StkTest\Base;

class ConntectorTest extends Base
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
        $this->pdo->method('quote')->willReturnCallback(function ($a) {
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

    public function testUpdateWithFailure()
    {
        $data = [
            'foo'      => 'bar',
            'users_id' => 1234
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('UPDATE `users` SET `foo` = ? WHERE `users_id` = ?')
            ->willReturn($this->statement);
        $this->statement->method('execute')->willReturn(false);
        $this->assertFalse($this->connector->update($row));
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

    // save

    public function testSaveUpdate()
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

        $this->assertSame($row, $this->connector->save($row));
    }

    public function testSaveInsertWithKeyfield()
    {
        $data = [
            'foo'      => 'bar',
            'users_id' => 0
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('INSERT INTO `users` (`foo`,`users_id`) VALUES (?,?)')
            ->willReturn($this->statement);

        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);

        $this->assertSame($row, $this->connector->save($row));
    }

    public function testSaveInsertWithoutKeyfield()
    {
        $data = [
            'foo' => 'bar'
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('INSERT INTO `users` (`foo`) VALUES (?)')
            ->willReturn($this->statement);

        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);

        $this->assertSame($row, $this->connector->save($row));
    }

    public function testSaveInsertWithNullKeyfield()
    {
        $data = [
            'foo'      => 'bar',
            'users_id' => null
        ];
        $row  = new Record($data);

        $this->pdo->method('prepare')->with('INSERT INTO `users` (`foo`,`users_id`) VALUES (?,?)')
            ->willReturn($this->statement);

        $this->statement->method('execute')->with(array_values($data))->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);

        $this->assertSame($row, $this->connector->save($row));
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
        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
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

    public function testFetchColumn()
    {
        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
        $this->pdo->method('query')->willReturn($this->statement);
        $this->statement->method('fetchColumn')->with(0)->willReturn('foo');
        $this->assertEquals('foo', $this->connector->fetchColumn($select));
    }

    public function testFetchColumnWithError()
    {
        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
        $this->pdo->method('query')->willReturn(false);
        $this->assertFalse($this->connector->fetchColumn($select));
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

        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
        $select = $select->where('id = ?', 1234);
        $this->pdo->method('query')->willReturn($this->statement)->with('SELECT a.* FROM `users` a WHERE id = 1234');
        $this->statement->method('fetch')->willReturn($data);

        $row = $this->connector->findOne($select);
        $this->assertInstanceOf(Record::class, $row);
        $this->assertEquals($data, $row->get());
    }

    public function testFindOneWithError()
    {
        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
        $select = $select->where('id = ?', 1234);

        $this->pdo->method('query')->willReturn(false);

        $row = $this->connector->findOne($select);
        $this->assertFalse($row);
    }

    public function testFindById()
    {
        $data = [
            'foo'      => 'bar',
            'users_id' => 1234
        ];

        $this->pdo->method('prepare')->with('SELECT * FROM `users` WHERE `users_id` = ?')->willReturn($this->statement);
        $this->statement->method('fetch')->willReturn($data);

        $row = $this->connector->findById(1234);
        $this->assertInstanceOf(Record::class, $row);
        $this->assertEquals($data, $row->get());
    }

    public function testFindByIdWithError1()
    {
        $this->pdo->method('prepare')->willReturn(false);

        $row = $this->connector->findById(1234);
        $this->assertFalse($row);
    }

    public function testFindByIdWithError2()
    {
        $this->pdo->method('prepare')->with('SELECT * FROM `users` WHERE `users_id` = ?')->willReturn($this->statement);
        $this->statement->method('execute')->willReturn(false);

        $row = $this->connector->findById(1234);
        $this->assertFalse($row);
    }

    // execute

    public function testExecute()
    {
        $sql = 'DELETE FROM `users` WHERE `users_id` = ?';
        $this->pdo->method('prepare')->with($sql)->willReturn($this->statement);
        $this->statement->method('execute')->with([1234])->willReturn(true);
        $this->statement->method('rowCount')->willReturn(1);
        $this->assertEquals(1, $this->connector->execute($sql, [1234]));
    }

    // last insert id

    public function testGetLastInsertId()
    {
        $this->pdo->method('lastInsertId')->willReturn(1234);
        $this->assertEquals(1234, $this->connector->getLastInsertId());
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

    public function testDebug()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->connector->setLogger($logger);
        $logger->expects($this->once())->method('debug');
        $select = new Stk\PDO\Select($this->quoteFunc(), 'users');
        $this->connector->query($select);
    }

}