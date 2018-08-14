<?php
/**
 * Class FormTable.
 *
 * @author Marco Gasi
 * @author blog codingfix.com
 *
 * FormSaver gets data from the form built by FormSaver, processes them and
 * puts them in the given mysql table
 */
spl_autoload_register(function () {
    include_once 'FormModel.class.php';
});
/**
 * [FormTable builds a table using data stored trhough FormBuilder and FormSaver].
 */
class FormTable
{
    /**
     * [public $table_name, the name of the table we want to process].
     *
     * @var [string]
     */
    private $table_name;
    /**
     * [private instance of the class FormModel].
     *
     * @var [object]
     */
    private $model;
    /**
     * [private instance of config array].
     *
     * @var [array]
     */
    private $config;
    /**
     * [private table structure].
     *
     * @var [array]
     */
    private $structure;
    /**
     * [private the table markup to print on the page].
     *
     * @var [string]
     */
    private $table_markup;

    /**
     * [__construct initializes configuration array, some variables,
     * creates an instance of the model, and initialize the table markup].
     */
    public function __construct($table_name)
    {
        $this->config = require 'config.php';
        $this->table_name = $table_name;
        $this->model = new FormModel($this->table_name);
        $this->structure = $this->model->get_table_structure($this->table_name);
        $this->table_markup = '<table>';
    }

    /**
     * [get_table_data sets the query paramters using session values and calls the model method to query the database].
     *
     * @param [string] $table [the table name]
     *
     * @return [object] [holds table structure and data]
     */
    public function get_table_data($table)
    {
        $_SESSION['orderby'] = isset($_GET['f']) ? $_GET['f'] : 'id';
        $_SESSION['orderdir'] = isset($_GET['o']) ? $_GET['o'] : 'ASC';
        $result = $this->model->get_table_values($table, $_SESSION['orderby'], $_SESSION['orderdir']);
        $this->data = $result;
        $obj = new stdClass();
        $obj->table = $this->data;
        $obj->structure = $this->structure;
        $obj->data = $this->data;

        return $obj;
    }

    /**
     * [build_table_head builds the table head].
     *
     * @return [string] [table head markup]
     */
    public function build_table_head()
    {
        if (isset($_SESSION['orderdir'])) {
            $orderdir = $_SESSION['orderdir'] == 'ASC' ? 'DESC' : 'ASC';
        } else {
            $orderdir = 'ASC';
        }
        $this->table_markup .= '<table><thead><tr>';
        for ($i = 0; $i < count($this->structure); ++$i) {
            $this->table_markup .= "<td><a href='manage.php?f={$this->structure[$i]['Field']}&o=$orderdir'>{$this->structure[$i]['Field']}</a></td>";
        }
        $this->table_markup .= "<td colspan='2'>Actions</td></tr></thead>";

        return $this->table_markup;
    }

    /**
     * [build_table_body builds the table body].
     *
     * @return [string] [table body markup]
     */
    public function build_table_body()
    {
        $this->table_markup .= '<tbody>';
        $data = $this->get_table_data($this->table_name);
        $table_structure = $data->structure;
        $table_values = $data->table;
        for ($i = 0; $i < count($table_values); ++$i) {
            $this->table_markup .= '<tr>';
            $row = $table_values[$i];
            for ($x = 0; $x < count($row); ++$x) {
                $field_name = $table_structure[$x]['Field'];
                $field_type = $table_structure[$x]['Type'];
                if (stristr($field_type, 'tinyint') !== false) {
                    if (!array_key_exists($field_name, $this->config['general']['radios'])) {
                        $output = $row[$field_name] == 0 ? 'Off' : 'On';
                    }
                } else {
                    $output = $row[$field_name];
                }
                $this->table_markup .= "<td>$output</td>";
            }
            $this->table_markup .= "<td><form action='edit.php' method='post'><input type='hidden' name='item_id' value='{$row['id']}' /><input type='submit' name='edit' value='Edit' /></form></td><td><form action='delete.php' method='post'><input type='hidden' name='item_id' value='{$row['id']}' /><input type='submit' name='delete' value='Delete' /></form></td>";
            $this->table_markup .= '</tr>';
        }
        $this->table_markup .= '</tbody>';

        return $this->table_markup;
    }

    /**
     * [build_table calls needed methods to build the table].
     *
     * @return [string] [the table markup]
     */
    public function build_table()
    {
        $table = $this->build_table_head();
        $table .= $this->build_table_body();
        $this->table_markup .= '</table>';

        return $this->table_markup;
    }
}
