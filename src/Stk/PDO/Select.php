<?php

namespace Stk\PDO;

use Closure;

class Select implements SqlInterface
{
    protected $_from = [];
    protected $_columns = [];
    protected $_where = [];
    protected $_order = [];
    protected $_limit;
    protected $_join = [];
    protected $_group = [];
    protected $_having = []; // XXX ToDo
    protected $_distinct = false;
    protected $_count = false;
    protected $_union = [];

    /**
     * the function which is responsible for quoting values, this may be vendor dependent
     *
     * @var  Closure
     */
    protected $quoteFunc;

    public function __construct(Closure $quote = null, $table = null)
    {
        if ($table !== null) {
            $this->_from[] = [['a' => $table], ['*']];
        }

        $this->quoteFunc = $quote;
    }

    public function distinct()
    {
        $me            = clone($this);
        $me->_distinct = true;

        return $me;
    }

    public function count($field = null)
    {
        $me         = clone($this);
        $me->_count = $field === null ? true : $field;

        return $me;
    }

    /**
     * add from clause
     *
     *
     * @param null $table
     * @param array|null $fields
     *
     * @return Select
     */
    public function from($table = null, array $fields = null)
    {
        $me = clone($this);
        if ($table === null) {
            $me->_from = [];

            return $me;
        }
        if ($fields === null) {
            $fields = ['*'];
        }

        $me->_from[] = [$table, $fields];

        return $me;
    }

    public function column($c = null)
    {
        $me = clone($this);
        if ($c === null) {
            $me->_columns = [];
            // clear from fields
            $nf = [];
            foreach ($me->_from as $f) {
                $nf[] = [$f[0], []];
            }
            $me->_from = $nf;

            return $me;
        }

        $me->_columns[] = $c;

        return $me;
    }

    protected function _where($l, $w, $v = [])
    {
        $me = clone($this);
        if ($w === null) {
            $me->_where = [];

            return $me;
        }

        $me->_where[] = [$l, $this->_expandParams($w, $v)];

        return $me;
    }

    public function where($c = null)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->_where('AND', $c, $args);
    }

    public function orWhere($c)
    {
        $args = func_get_args();
        array_shift($args);

        return $this->_where('OR', $c, $args);
    }

    public function order($o = null, $desc = false)
    {
        $me = clone($this);
        if ($o === null) {
            $me->_order = [];

            return $me;
        }

        $me->_order[] = [$o, $desc];

        return $me;
    }

    public function group($o = null)
    {
        $me = clone($this);
        if ($o === null) {
            $me->_group = [];

            return $me;
        }

        $me->_group[] = $o;

        return $me;
    }

    public function limit($count = null, $skip = null)
    {
        $me = clone($this);
        if ($count === null) {
            $me->_limit = null;

            return $me;
        }

        if ($skip === null) {
            $me->_limit = [(int)$count];
        } else {
            $me->_limit = [(int)$skip, (int)$count];
        }

        return $me;
    }

    public function union(Select $select, $all = true)
    {
        $me           = clone($this);
        $me->_union[] = [$select, $all];

        return $me;
    }

    protected function _join($lr = null, $t = null, $on = null, $f = null)
    {
        $me = clone($this);
        if ($t === null) {
            $me->_join = [];

            return $me;
        }
        if ($f === null) {
            $f = ['*'];
        }

        // expand params from on clause
        if (is_array($on)) {
            $w  = array_shift($on);
            $on = $this->_expandParams($w, $on);
        }

        $me->_join[] = [$lr, $t, $on, $f];

        return $me;
    }

    public function join($t = null, $on = null, $f = null)
    {
        return $this->_join('JOIN', $t, $on, $f);
    }

    public function joinLeft($t = null, $on = null, $f = null)
    {
        return $this->_join('LEFT JOIN', $t, $on, $f);
    }

    public function joinRight($t = null, $on = null, $f = null)
    {
        return $this->_join('RIGHT JOIN', $t, $on, $f);
    }

    public function toSql(): string
    {
        $fields = '';
        $from   = '';
        foreach ($this->_from as $f) {
            if (strlen($from)) $from .= ',';
            if (is_array($f[0])) {
                $q    = key($f[0]);
                $from .= sprintf('%s %s', $this->qf($f[0][$q]), $q);
            } else {
                $q    = null;
                $from .= $this->qf($f[0]);
            }

            $expanded = $this->_expandFields(array_merge($f[1], $this->_columns), $q);
            if (strlen($expanded)) {
                if (strlen($fields)) $fields .= ',';
                $fields .= $expanded;
            }
        }

        $join = '';
        foreach ($this->_join as $j) {
            $type = $j[0]; // LEFT RIGHT INNER etc.
            $t    = $j[1]; // table
            $on   = $j[2]; // condition
            $f    = $j[3]; // fields
            if (is_array($t)) {
                $q     = key($t);
                $table = sprintf('%s %s', $this->qf($t[$q]), $q);
            } else {
                $q     = null;
                $table = $this->qf($t);
            }

            $expanded = $this->_expandFields($f, $q);
            if (strlen($expanded)) {
                if (strlen($fields)) $fields .= ',';
                $fields .= $expanded;
            }

            if (strlen($join)) $join .= ' ';
            $join .= sprintf(' %s %s ON %s', $type, $table, $on);
        }

        $where = '';
        foreach ($this->_where as $w) {
            if (strlen($where)) {
                $where .= sprintf(' %s %s', $w[0], $w[1]);
            } else {
                $where .= sprintf('%s', $w[1]);
            }
        }

        $order = '';
        foreach ($this->_order as $o) {
            if (strlen($order)) $order .= ',';
            $order .= $this->qf($o[0]);
            if ($o[1] === true) $order .= ' DESC';
        }

        $group = '';
        foreach ($this->_group as $g) {
            if (strlen($group)) $group .= ',';
            $group .= $this->qf($g);
        }

        $limit = '';
        if ($this->_limit !== null) {
            if (count($this->_limit) > 1) {
                $limit = sprintf('%d,%d', $this->_limit[0], $this->_limit[1]);
            } else {
                $limit = $this->_limit[0];
            }
        }

        if ($this->_count) {
            if ($this->_distinct) {
                $cfields = sprintf('COUNT(DISTINCT %s)', $this->_count);
            } else {
                $cfields = 'COUNT(*)';
            }
            if (count($this->_union)) {
                $q = sprintf('SELECT %s FROM (SELECT %s FROM %s', $cfields, $fields, $from);
            } else {
                $q = sprintf('SELECT %s FROM %s', $cfields, $from);
            }
        } else {
            $q = sprintf('SELECT %s%s FROM %s', $this->_distinct ? 'DISTINCT ' : '', $fields, $from);
        }

        if (strlen($join)) {
            $q .= $join;
        }
        if (strlen($where)) {
            $q .= sprintf(' WHERE %s', $where);
        }

        if (strlen($group)) {
            $q .= sprintf(' GROUP BY %s', $group);
        }
        if (strlen($order) && !$this->_count) {
            $q .= sprintf(' ORDER BY %s', $order);
        }
        if (strlen($limit)) {
            $q .= sprintf(' LIMIT %s', $limit);
        }

        if ($this->_count && count($this->_union)) {
            $q .= ') AS u';
        } elseif (count($this->_union)) {
            $q = '(' . $q . ')';
            foreach ($this->_union as $u) {
                $q .= sprintf(' UNION%s (%s)', $u[1] ? ' ALL' : '', $u[0]->toSql());
            }
        }

        return $q;
    }

    // quoting

    /**
     * quote field name
     *
     * @param $f
     *
     * @return string
     */
    public function qf($f)
    {
        if ($f == '*') {
            return $f;
        }

        if (stristr($f, ' as ')) {
            [$r, $c] = preg_split('/ as /i', $f, 2);

            return sprintf('%s AS `%s`', $r[0] == '(' ? $r : "`$r`", $c);
        } else {
            return sprintf('%s', $f[0] == '(' ? $f : "`$f`");
        }
    }

    /**
     * quote value
     *
     * @param $v
     *
     * @return mixed|string
     */
    public function qv($v)
    {
        if ($v === null) {
            return 'NULL';
        } else {
            $qf = $this->quoteFunc;

            return $qf($v);
        }
    }

    // helpers

    protected function _expandParams($def, $values)
    {
        $expanded = $def;
        foreach ($values as $value) {
            if (is_array($value)) {
                $mapped   = array_map(function ($v) { return $this->qv($v); }, $value);
                $expanded = preg_replace('/(\W+)\?/', '$1(' . implode(',', $mapped) . ')', $expanded, 1);
            } else {
                $expanded = preg_replace('/(\W+)\?/', '${1}' . $this->qv($value), $expanded, 1);
            }
        }

        return $expanded;
    }

    protected function _expandFields($fieldDef, $tableAlias)
    {
        $fields = '';
        foreach ($fieldDef as $x) {
            if (strlen($fields)) $fields .= ',';
            if ($tableAlias === null) {
                $fields .= $this->qf($x);
            } else {
                if ($x[0] == '(') {
                    $fields .= sprintf('%s', $this->qf($x));
                } else {
                    $fields .= sprintf('%s.%s', $tableAlias, $this->qf($x));
                }
            }
        }

        return $fields;
    }
}
