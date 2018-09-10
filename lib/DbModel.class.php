<?php

/**
 * Class DbModel.
 *
 * @author Marco Gasi
 * @author blog codingfix.com
 *
 * DbModel is the helper class to manage the database operations
 */
/**
 * [DbModel provides general methods to manage database].
 */
require_once 'dbConnection.class.php';
class DbModel
{
    protected $db;
    protected $tableName;

    public function __construct($tableName)
    {
        $config = require 'config.php';
        $this->tableName = $tableName;
        $this->db = DbConnection::getInstance();
        DbConnection::setCharsetEncoding();
    }

    /**
     * [getTableValues].
     *
     * @param [string] $table    [table name]
     * @param [string] $orderby  [the field the records must be sorted accordingly to]
     * @param [string] $orderdir [ascending or descending order]
     *
     * @return [array] [column names and values]
     */
    public function getTableValues($table, $orderBy = '', $orderDir = '')
    {
        $orderDir = !empty($orderDir) ? strip_tags(htmlspecialchars($orderDir)) : '';
        $orderBy = !empty($orderBy) ? strip_tags(htmlspecialchars($orderBy)) : '';
        $query = "SELECT * FROM $table";
        $query .= !empty($orderBy) ? " ORDER BY $orderBy $orderDir" : '';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * [getTableStructure].
     *
     * @param [string] $table [table name]
     *
     * @return [array] [sructure of the given table]
     */
    public function getTableStructure($table)
    {
        $query = "DESCRIBE $table";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
