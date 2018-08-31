<?php

/**
 * Class FormModel.
 *
 * @author Marco Gasi
 * @author blog codingfix.com
 *
 * FormModel is the helper class to manage the database operations
 */
/**
 * [DbModel provides general methods to manage database].
 */
class DbModel
{
    protected $db;
    protected $tableName;

    public function __construct($tableName)
    {
        $config = require 'config.php';
        $this->tableName = $tableName;
        $this->db = new mysqli($config['database']['dbHost'], $config['database']['dbUsername'], $config['database']['dbPassword'], $config['database']['dbName']);
        if ($this->db->connect_error) {
            die('Connect Error: '.$this->db->connect_error);
        }
        $this->db->set_charset('utf8');
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
    public function getTableValues($table, $orderby = '', $orderdir = '')
    {
        $order_by = !empty($orderby) ? "ORDER BY $orderby $orderdir" : '';
        $query = "SELECT * FROM $table $order_by";
        $result = $this->db->query($query);
        $a = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                array_push($a, $row);
            }
        }

        return $a;
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
        $result = $this->db->query($query);
        $a = array();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                array_push($a, $row);
            }
        }

        return $a;
    }
}
