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

        $stmt = $this->db->prepare("SELECT * from $tableName WHERE id= :itemId");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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
        $stmt = $this->db->prepare("SELECT $fileType from $tableName WHERE id= :itemId");
        $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            return $row[$fileType];
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
        $placeholders = '';
        foreach ($data as $key => $value) {
            $columns .= "$key,";
            $values .= "$value,";
            $placeholders .= ":$key,";
        }
        $columns = rtrim($columns, ',');
        $values = rtrim($values, ',');
        $placeholders = rtrim($placeholders, ',');
        if (!isset($itemId)) {
            $stmt = $this->db->prepare("INSERT INTO $this->tableName ($columns) VALUES($placeholders)");
        } else {
            $query = "UPDATE $this->tableName SET ";
            foreach ($data as $key => $value) {
                $query .= "$key=':$key',";
            }
            $query = rtrim($query, ',');
            $query .= " WHERE id= :itemId";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':itemId', $itemId);
        }
        // notice the & in foreach clause for $value
        // it is required otherwise only the last value is used
        // binParam need by reference: http: //www.php.net/manual/fr/pdostatement.bindparam.php#98145
        foreach ($data as $key => &$value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $stmt->execute();
        // to check parameterized query
        // $stmt->debugDumpParams();

        return $this->db->lastInsertId();
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
        $query = "DELETE FROM $this->tableName WHERE id = :itemId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':itemId', $itemId);
        $stmt->execute();
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
        $query = "UPDATE $this->tableName SET pictures = :pictures WHERE id = :itemId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':pictures', $pictures);
        $stmt->bindParam(':itemId', $itemId);
        $stmt->execute();
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
        $query = "UPDATE $this->tableName SET documents = :documents WHERE id = :itemId";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':documents', $documents);
        $stmt->bindParam(':itemId', $itemId);
        $stmt->execute();
    }
}
