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
include_once 'DbModel.class.php';

class FormModel extends DbModel
{
    /**
    * [__construct initializes variable from the config file and establishes a mysqli connection to the database].
    */
    public function __construct($tableName)
    {
        parent::__construct($tableName);
    }

    /**
    * [getItemData get data for a given record in the given table].
    *
    * @param [string]  $table  [table name]
    * @param [integer] $itemId [id of the record]
    *
    * @return [array] [column names and values for the given record]
    */
    public function getItemData($tableName, $itemId)
    {
        $query = "SELECT * from $tableName WHERE id='$itemId'";
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
    * [getFiles get the value of the files column for a given record].
    *
    * @param [integer] $itemId [the id of the record]
    *
    * @return [string] [a semicolon separated string which holds the files' names]
    */
    public function getFiles($fileType, $itemId)
    {
        $query = "SELECT $fileType FROM $this->tableName WHERE id='$itemId'";
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
    * @param [array]   $data   [columns and values]
    * @param [integer] $itemId [id of the record to update]
    *
    * @return [integer] [last insert id]
    */
    public function insert($data, $itemId = null)
    {
        $columns = '';
        $values = '';
        if (!isset($itemId)) {
            foreach ($data as $k => $v) {
                $columns .= "$k,";
                $values .= "'$v',";
            }
            $columns = rtrim($columns, ',');
            $values = rtrim($values, ',');
            $query = "INSERT INTO $this->tableName ($columns) VALUES($values)";
        } else {
            $query = "UPDATE $this->tableName SET ";
            foreach ($data as $k => $v) {
                $query .= "$k='$v',";
            }
            $query = rtrim($query, ',');
            $query .= " WHERE id='$itemId'";
        }
        $this->db->query($query);

        return $this->db->insert_id;
    }

    /**
    * deleteItem deletes a record from the table.
    *
    * @param [integer] $itemId [the id of the record to delete]
    *
    * @return [null] [this method doesn't return anything]
    */
    public function deleteItem($itemId)
    {
        $query = "DELETE FROM $this->tableName WHERE id='$itemId'";
        $this->db->query($query);
    }

    /**
    * Updates pictures string in table column, The actual work to remove from the string the
    * deleted picture and to actually delete the picture files is don by FormProcessor method
    * deletePicture.
    *
    * @param [string]  $pictures
    * @param [integer] $itemId
    */
    public function deletePicture($pictures, $itemId)
    {
        $query = "UPDATE $this->tableName SET pictures='$pictures' WHERE id='$itemId'";
        $this->db->query($query);
    }

    /**
    * Updates documents string in table column, The actual work to remove from the string the
    * deleted document and to actually delete the document files is done by FormProcessor method
    * deleteDocument.
    *
    * @param [string]  $documents
    * @param [integer] $itemId
    */
    public function deleteDocument($documents, $itemId)
    {
        $query = "UPDATE $this->tableName SET documents='$documents' WHERE id='$itemId'";
        $this->db->query($query);
    }
}
