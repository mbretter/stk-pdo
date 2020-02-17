<?php

namespace Stk\PDO;

interface SqlInterface
{
    /**
     * @return string
     */
    public function toSQL(): string;
}