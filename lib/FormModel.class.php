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
 * [FormModel actually performs database CRUD operations].
 */
class FormModel
{
    /**
     * [private $db].
     *
     * @var [mysqli database connection]
     */
    private $db;
    /**
     * [private $table_name].
     *
     * @var [string]
     */
    private $table_name;

    /**
     * [__construct initializes variable from the config file and establishes a mysqli connection to the database].
     */
    public function __construct($table_name)
    {
        $config = require 'config.php';
        $this->table_name = $table_name;
        $this->db = new mysqli($config['database']['db_host'], $config['database']['db_username'], $config['database']['db_password'], $config['database']['db_name']);
        if ($this->db->connect_error) {
            die('Connect Error: '.$this->db->connect_error);
        }
        $this->db->set_charset('utf8');
    }

    /**
     * [get_table_values].
     *
     * @param [string] $table    [table name]
     * @param [string] $orderby  [the field the records must be sorted accordingly to]
     * @param [string] $orderdir [ascending or descending order]
     *
     * @return [array] [column names and values]
     */
    public function get_table_values($table, $orderby = '', $orderdir = '')
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
     * [get_table_structure].
     *
     * @param [string] $table [table name]
     *
     * @return [array] [sructure of the given table]
     */
    public function get_table_structure($table)
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

    /**
     * [get_item_data get data for a given record in the given table].
     *
     * @param [string]  $table   [table name]
     * @param [integer] $item_id [id of the record]
     *
     * @return [array] [column names and values for the given record]
     */
    public function get_item_data($table, $item_id)
    {
        $query = "SELECT * from $table WHERE id='$item_id'";
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
     * [get_pictures get the value of the pictures column for a given record].
     *
     * @param [integer] $item_id [the id of the record]
     *
     * @return [string] [a semicolon separated string which holds the files' names]
     */
    public function get_files($file_type, $item_id)
    {
        $query = "SELECT $file_type FROM $this->table_name WHERE id='$item_id'";
        $result = $this->db->query($query);
        if ($result) {
            $row = mysqli_fetch_row($result);

            return $row[0];
        }

        return false;
    }

    /**
     * [get_pictures get the value of the pictures column for a given record].
     *
     * @param [integer] $item_id [the id of the record]
     *
     * @return [string] [a semicolon separated string which holds the files' names]
     */
    public function get_pictures($item_id)
    {
        $query = "SELECT pictures FROM $this->table_name WHERE id='$item_id'";
        $result = $this->db->query($query);
        if ($result) {
            $row = mysqli_fetch_row($result);

            return $row[0];
        }

        return false;
    }

    /**
     * [get_documents get the value of the documents column for a given record].
     *
     * @param [integer] $item_id [the id of the record]
     *
     * @return [string] [a semicolon separated string which holds the files' names]
     */
    public function get_documents($item_id)
    {
        $query = "SELECT documents FROM $this->table_name WHERE id='$item_id'";
        $result = $this->db->query($query);
        if ($result) {
            $row = mysqli_fetch_row($result);

            return $row[0];
        }

        return false;
    }

    /**
     * [insert insert a new record or updates an existing one].
     *
     * @param [array]   $data    [columns and values]
     * @param [integer] $item_id [id of the record to update]
     *
     * @return [integer] [last insert id]
     */
    public function insert($data, $item_id = null)
    {
        $columns = '';
        $values = '';
        if (!isset($item_id)) {
            foreach ($data as $k => $v) {
                $columns .= "$k,";
                $values .= "'$v',";
            }
            $columns = rtrim($columns, ',');
            $values = rtrim($values, ',');
            $query = "INSERT INTO $this->table_name ($columns) VALUES($values)";
        } else {
            $query = "UPDATE $this->table_name SET ";
            foreach ($data as $k => $v) {
                $query .= "$k='$v',";
            }
            $query = rtrim($query, ',');
            $query .= " WHERE id='$item_id'";
        }

        // return $query;
        $this->db->query($query);

        return $this->db->insert_id;
    }

    /**
     * delete_item deletes a record from the table.
     *
     * @param [integer] $item_id [the id of the record to delete]
     *
     * @return [null] [this method doesn't return anything]
     */
    public function delete_item($item_id)
    {
        $query = "DELETE FROM $this->table_name WHERE id='$item_id'";
        $this->db->query($query);
    }

    /**
     * Updates pictures string in table column, The actual work to remove from the string the
     * deleted picture and to actually delete the picture files is don by FormProcessor method
     * delete_picture.
     *
     * @param [string]  $pictures
     * @param [integer] $item_id
     */
    public function delete_picture($pictures, $item_id)
    {
        $query = "UPDATE $this->table_name SET pictures='$pictures' WHERE id='$item_id'";
        $this->db->query($query);
    }

    /**
     * Updates documents string in table column, The actual work to remove from the string the
     * deleted document and to actually delete the document files is done by FormProcessor method
     * delete_document.
     *
     * @param [string]  $documents
     * @param [integer] $item_id
     */
    public function delete_document($documents, $item_id)
    {
        $query = "UPDATE $this->table_name SET documents='$documents' WHERE id='$item_id'";
        $this->db->query($query);
    }
}
