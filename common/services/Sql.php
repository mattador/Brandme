<?php

namespace Common\Services;

use Phalcon\Mvc\Model;

/**
 * Standard PDO implementation for when PHQL isn't flexible enough
 *
 * Class Sql
 * @package Frontend\Services
 */
class Sql extends Model
{
    /**
     * Returns array of select results
     *
     * @param string $query
     * @return array
     */
    public static function find($query = null)
    {
        $sql = new Sql();
        $stmt = $sql->getReadConnection()->query($query);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        $resultSet = $stmt->fetchAll();
        return $resultSet;
    }

    /**
     * Executes a write or update query, returning a boolean on success or failure
     *
     * @param $query
     * @return bool
     */
    public static function write($query)
    {
        $sql = new Sql();
        return $sql->getWriteConnection()->execute($query);
    }


}