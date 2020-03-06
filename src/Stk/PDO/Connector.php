<?php

namespace Stk\PDO;

use DateTime;
use PDO;
use PDOStatement;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;
use Stk\Immutable\ImmutableInterface;
use Stk\Immutable\Record;
use Stk\Service\Injectable;

class Connector implements Injectable
{
    use LoggerAwareTrait;

    /** @var  PDO */
    protected $_pdo;

    /** @var  string */
    protected $_table;

    /**
     * an assoc array, key is the table name, value is an array of fields or a single fieldname
     * [users] => userid
     * [users_groups] => [userid,groupid]
     *
     * @var array
     */
    protected $_pkMappings = [];

    /**
     * the scheme for deriving the primary key, if no table mapping was found
     * currently {table} is supported as the only placeholder
     * if null is passed, pure assoc arrays are returned
     *
     * @var string|null
     */
    protected $_pkScheme = '{table}_id';

    /**
     * the classname for fetched records, an assoc data array is passed to the constructor
     * defaults to an immutable Record
     *
     * @var string
     */
    protected $_rowClassName = Record::class;

    /**
     *
     * @param PDO $pdo
     * @param string $table
     * @param string|null $pkScheme
     * @param array $pkMappings
     */
    public function __construct(PDO $pdo, string $table, string $pkScheme = null, array $pkMappings = [])
    {
        $this->_pdo        = $pdo;
        $this->_table      = $table;
        $this->_pkMappings = $pkMappings;
        if (strlen($pkScheme)) {
            $this->_pkScheme = $pkScheme;
        }
    }

    public function setPkMappings(array $mappings)
    {
        $this->_pkMappings = $mappings;

        return $this;
    }

    public function setPkScheme(string $scheme)
    {
        $this->_pkScheme = $scheme;

        return $this;
    }

    public function setRowClassName(string $className = null)
    {
        $this->_rowClassName = $className;

        return $this;
    }

    /**
     * @param PDO $pdo
     *
     * @return Connector
     */
    public function setPDO(PDO $pdo)
    {
        $this->_pdo = $pdo;

        return $this;
    }

    /**
     * @return PDO
     */
    public function getPDO()
    {
        return $this->_pdo;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        $this->_table = $table;

        return $this;
    }

    /**
     * @return  string
     */
    public function getTable()
    {
        return $this->_table;
    }

    /**
     * makes an update if the keyfield is set, otherwise an insert is made
     * returns the immutable, after an insert the _id is set into the immutable
     * and a new clone is returned.
     *
     * @param ImmutableInterface $row
     * @param array $keyFields
     *
     * @return ImmutableInterface
     */
    public function save(ImmutableInterface $row, $keyFields = [])
    {
        $keyFields = $this->derivePrivateKey($keyFields);

        $doInsert = false;
        foreach ($keyFields as $f) {
            // check for null and integer 0, and not with has()
            // some special checks would be needed for certain vendors:
            // oracle: Sequence.nextval
            // sql server: NEXT VALUE FOR Schema.Sequence
            if ($row->get($f) === null || $row->get($f) === 0) {
                $doInsert = true;
                break;
            }
        }

        if ($doInsert) {
            $this->insert($row);
        } else {
            $this->update($row, $keyFields);
        }

        return $row;
    }

    /**
     * insertOne Immutable
     *
     * @param ImmutableInterface $row
     *
     * @return bool
     */
    public function insert(ImmutableInterface $row)
    {
        $this->debug(__METHOD__ . ":" . print_r($row, true));

        $fieldValues = $this->buildValueSet($row);
        $fields      = array_map(function ($f) { return sprintf('`%s`', $f); }, array_keys($fieldValues));

        $params = substr(str_repeat('?,', count($fields)), 0, -1);

        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->_table, implode(',', $fields), $params);

        return $this->prepareExecute($sql, array_values($fieldValues));
    }

    /**
     * @param ImmutableInterface $row
     * @param array $keyFields
     *
     * @return int
     */
    public function update(ImmutableInterface $row, $keyFields = [])
    {
        $this->debug(__METHOD__ . ":" . print_r($row, true));

        $keyFields = $this->derivePrivateKey($keyFields);

        $fieldValues = $this->buildValueSet($row);

        $set   = $values = [];
        $where = $whereValues = [];
        foreach ($fieldValues as $f => $v) {
            if (in_array($f, $keyFields)) {
                $where[]       = sprintf('`%s` = ?', $f);
                $whereValues[] = $v;
                continue;
            }
            $set[]    = sprintf('`%s` = ?', $f);
            $values[] = $v;
        }

        if (!count($where)) {
            throw new RuntimeException('No key found for updating the row!');
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $this->_table, implode(',', $set), implode(' AND ', $where));

        return $this->prepareExecute($sql, array_merge($values, $whereValues));
    }

    /**
     * @param ImmutableInterface $row
     * @param array $keyFields
     *
     * @return int
     */
    public function delete(ImmutableInterface $row, $keyFields = [])
    {
        $this->debug(__METHOD__ . ":" . print_r($row, true));

        $keyFields = $this->derivePrivateKey($keyFields);

        $where = $whereValues = [];
        foreach ($keyFields as $f) {
            $where[]       = sprintf('`%s` = ?', $f);
            $whereValues[] = $row->get($f);
        }

        $sql = sprintf('DELETE FROM `%s` WHERE %s', $this->_table, implode(' AND ', $where));

        return $this->prepareExecute($sql, $whereValues);
    }

    /**
     * delete row by id, only supported on tables with a single primary key field
     *
     * @param $id
     * @param string|null $keyField
     *
     * @return int
     */
    public function deleteById($id, string $keyField = '')
    {
        $this->debug(__METHOD__ . ":" . $id);

        if (!strlen($keyField)) {
            $keyFields = $this->derivePrivateKey();
            $keyField  = $keyFields[0];
        }

        $where = sprintf('`%s` = ?', $keyField);

        $sql = sprintf('DELETE FROM `%s` WHERE %s', $this->_table, $where);

        return $this->prepareExecute($sql, [$id]);
    }

    /**
     * execute an SQL statement and return affected rows
     *
     * @param $sql
     * @param array $params
     *
     * @return int
     */
    public function execute($sql, $params = [])
    {
        $this->debug(__METHOD__ . ':' . $sql . ' params:' . implode(',', $params));

        return $this->prepareExecute($sql, $params);
    }

    /**
     * @param SqlInterface $select
     *
     * @return false|PDOStatement
     */
    public function query(SqlInterface $select)
    {
        $sql = $select->toSQL();
        $this->debug(__METHOD__ . ':' . $sql);

        return $this->_pdo->query($sql);
    }

    /**
     * fetch next row from a statement, return null if no more rows are available, false on error
     *
     * @param $stmt PDOStatement
     *
     * @return ImmutableInterface|array|null
     */
    public function fetch(PDOStatement $stmt)
    {
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data === false) {
            return null;
        }

        if ($this->_rowClassName === null) {
            return $data;
        }

        return new $this->_rowClassName($data);
    }

    /**
     * return a single row
     *
     * @param SqlInterface $select
     *
     * @return array|bool|ImmutableInterface|null
     */
    public function findOne(SqlInterface $select)
    {
        $stmt = $this->query($select);
        if ($stmt === false) {
            return false;
        }

        return $this->fetch($stmt);
    }

    /**
     * find a single row by id, no split keys supported
     *
     * @param $id
     * @param string $keyField
     *
     * @return ImmutableInterface|array|bool
     */
    public function findById($id, string $keyField = '')
    {
        $this->debug(__METHOD__ . ":$id");

        if (!strlen($keyField)) {
            $keyFields = $this->derivePrivateKey();
            $keyField  = $keyFields[0];
        }

        $where = sprintf('`%s` = ?', $keyField);

        $sql = sprintf('SELECT * FROM `%s` WHERE %s', $this->_table, $where);

        $stmt = $this->_pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        if ($stmt->execute([$id]) === false) {
            return false;
        }

        return $this->fetch($stmt);
    }

    /**
     * @param null $sequence
     *
     * @return string
     */
    public function getLastInsertId($sequence = null)
    {
        return $this->_pdo->lastInsertId($sequence);
    }

    /**
     * fetch one column from the first returned row, useful when counting
     *
     * @param SqlInterface $select
     * @param int $col
     *
     * @return bool|mixed
     */
    public function fetchColumn(SqlInterface $select, $col = 0)
    {
        $sql = $select->toSQL();
        $this->debug(__METHOD__ . ":$sql");

        $stmt = $this->_pdo->query($sql);
        if ($stmt === false) {
            return false;
        }

        return $stmt->fetchColumn($col);
    }

    // helpers

    /**
     * derive private key, if no keyfields are given
     * 1. try tablename_id
     * 2. try id
     *
     * @param array $keyFields
     *
     * @return array
     */
    public function derivePrivateKey($keyFields = [])
    {
        if (count($keyFields)) {
            return $keyFields;
        }

        if (isset($this->_pkMappings[$this->_table])) {
            $key = $this->_pkMappings[$this->_table];
        } else {
            $key = str_replace('{table}', $this->_table, $this->_pkScheme);
        }

        return is_array($key) ? $key : [$key];
    }

    /**
     * prepare and execute an SQL statement
     * return number of affected rows
     *
     * @param string $sql
     * @param array $params
     *
     * @return bool|int
     */
    protected function prepareExecute(string $sql, array $params = [])
    {
        $stmt = $this->_pdo->prepare($sql);
        if ($stmt === false) {
            return false;
        }

        if ($stmt->execute($params) === false) {
            return false;
        }

        return $stmt->rowCount();
    }

    /**
     * build an array of values, useable as parameters for insert and updates
     * convert Datetime mysql datetime-format
     * only leaf nodes of the immutable are used, the corresponding path is converted into a dotted string
     *
     * @param ImmutableInterface $row
     *
     * @return array
     */
    protected function buildValueSet(ImmutableInterface $row)
    {
        $values = [];
        $row->walk(function ($path, $value) use (&$values) {
            $field = is_array($path) ? implode('.', $path) : $path;

            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            $values[$field] = $value;
        });

        return $values;
    }

    /**
     * Debug logging if logger is available
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->debug($message, $context);
        }
    }
}
