<?php
/**
 * Class FormBuilder
 * @author    Marco Gasi
 * @author    blog codingfix.com
 * @version   v. 1.0
 * @copyright Copyright (c) 2018, Marco
 */
spl_autoload_register(function () {
    include 'FormModel.class.php';
});
/**
 * FormBuilder, with its brothers FormSaver and FormModel, allows you  to build
 * a ready-to-use form reading the database table and save the data to a mysql
 * database with almost zero configuration.
 *
 * HOW IT WORKS
 *
 * 	FormBuilder reads structure and data for the given table directly from the database
 * 	and it creates form elements accordingly to the column type:
 *
 * 	Column type			         Input type
 * 	varchar, int, decimal		 text
 * 	text					           textarea
 * 	tinyint/bit				       checkbox
 *
 *  Other elements have to be set in the config file (read below)
 *
 * 	In config files you MUST set following options
 *
 *  - database host
 * 	- database username
 * 	- database password
 * 	- database name
 * 	- table name
 *
 *  In config file you CAN setup the following additional options:
 *
 * 	  RADIO BUTONS: you have to set an array with column names as keys and an array which holds the possible values:
 * 	  for instance "radios" => array("size" => array("small", "medium", "large"))
 * 	- SELECTS: you have to options
 * 	    1) you can set a 'list' passing manually a series of values (strings)
 * 	    2) you can specify one or more columns names you want to be converted in selects: in this case your table
 * 	       should hold a column with that name where the index of the corresponding record in another table with
 * 	       identical name. If you are managing a table for your products, they will probably be organized in categories;
 * 	       FormBuilder expects to find in your database a table called 'category' whose values (id and name) will be
 * 	       used to build a select.
 * 	- FILES: you can set an array of columns names whose data require to upload a file. FormSaver doesn't use BLOB
 * 	  fields because it's a bad practice to store files directly in the database, so files will be stored in a directory
 * 	  called as the column's name in your server and the path to them will be stored in the database. So if you have a
 * 	  column called 'pictures', if such a directory doesn't exist, FormSaver will create it and it will store files in
 * 	  it.
 *  - root_target_dir: if empty, files will be saved in a subdirectory in your document root; the subdirectory will be
 *    named accordingly to the name of the related table column name; if your site is www.example.com and you are
 *    uploading files for the column 'pictures', the root_target_dir will be something like
 *    /var/www/vhosts/example.com/httpdocs/pictures; if you set a root_target_dir to some value, like, for instance,
 *    'user_id', the pictures will be saved in /var/www/vhosts/example.com/httpdocs/user_id/pictures
 *
 */
class FormBuilder
{
    /**
     * [public $table_name, the name of the table we want to process]
     * @var [string]
     */
    public $table_name;
    /**
     * [private $lists, a multiple associative array of values thant must be grouped in select elements]
     * @var [array]
     */
    private $lists;
    /**
     * [private $selects an array of names wich correspond to external tables which host the values or the index it is present in the processed table under the column with the same name]
     * @var [array]
     */
    private $selects;
    /**
     * [private $hidden_inputs a list of fields we don't want be edited but that must be present in the form ]
     * @var [array]
     */
    private $hidden_inputs;
    /**
     * [private $ignored_inputs, a list of those fields we don't want to be edited or just shown]
     * @var [array]
     */
    private $ignored_inputs;
    /**
     * [private site url, used to link files and display them to the user in the edit form]
     * @var [string]
     */
    private $site_url;
    /**
     * [private $upload, a list of table columns names that require to upload one or more file]
     * @var [type]
     */
    private $upload;
    /**
     * [private $radios, an array with the column name as key and another array with the possible velaues as value]
     * @var [array]
     */
    private $radios;
    /**
     * [private $edit_mode, true if the form is in editing mode]
     * @var [boolean]
     */
    private $edit_mode;
    /**
     * [private $item_id, the id of the item is being edited]
     * @var [integer]
     */
    private $item_id;
    /**
     * [private $item, an array with the existing values for the given item_id]
     * @var [type]
     */
    private $item;
    /**
     * [private $model, internal object of class FormModel]
     * @var [object]
     */
    private $model;
    /**
     * [private $structure, complex array which holds a mysql table structure]
     * @var [array]
     */
    private $structure;
    /**
     * [private $processed, array which is dinamically filled with the field names once they are processed]
     * @var [array]
     */
    private $processed;

    /**
     * initializes properties using provided config file]
     * @param [integer] $item_id [the id of the item if we are building a
     * form to edit existing data]
     */
    public function __construct($item_id = null)
    {
        $config = require 'config.php';
        $this->table_name = $config['database']['table_name'];
        $this->site_url = $config['general']['site_url'];
        $this->lists = isset($config['general']['lists']) ? $config['general']['lists'] : null;
        $this->selects = isset($config['general']['selects']) ? $config['general']['selects'] : null;
        $this->hidden_inputs = isset($config['general']['hidden_inputs']) ? $config['general']['hidden_inputs'] : null;
        $this->ignored_inputs = isset($config['general']['ignored_inputs']) ? $config['general']['ignored_inputs'] : null;
        $this->site_url = isset($config['general']['site_url']) ? $config['general']['site_url'] : '';
        $this->upload = isset($config['general']['upload']) ? $config['general']['upload'] : '';
        $this->radios = isset($config['general']['radios']) ? $config['general']['radios'] : '';
        $this->processed = array();
        $this->model = new FormModel();
        $this->structure = $this->get_table_structure($this->table_name);
        if (isset($item_id) && is_numeric($item_id)) {
            $this->edit_mode = true;
            $this->item_id = $item_id;
            $item_data = $this->model->get_item_data($this->table_name, $this->item_id);
            $this->item = $item_data[0];
        }
    }

    /**
     * [build_select ]
     * @param  [array] $field_name     [name, id, class]
     * @param  [array] $data        [options' values and text]
     * @param  [boolean] $selected  [selected option]
     * @return [string]             [select html element]
     */
    public function build_select($field_name, $data, $selected)
    {
        $select = "<select name='$field_name' class='form-control $field_name'>";
        foreach ($data as $k => $v) {
            if ($k == $selected) {
                $select .= "<option value='$k' selected>$v</option>";
            } else {
                $select .= "<option value='$k'>$v</option>";
            }
        }
        $select .= "</select>";
        return $select;
    }

    /**
     * [build_label ]
     * @param  [string] $value [value for name, id and text]
     * @return [string]        [label html element]
     */
    public function build_label($value)
    {
        return "<label for='" . $value . "' id='" . $value . "_lbl'>" . ucfirst($value) . "</label>";
    }

    /**
     * [build_input ]
     * @param  [array] $field_name  [name, id, class]
     * @param  [string] $value   [text value]
     * @return [string]          [input html element]
     */
    public function build_input($field_name, $value)
    {
        return "<input type='text' name='$field_name' id='$field_name' class='form-control $field_name' value='$value' />";
    }

    /**
     * [build_textarea description]
     * @param  [array] $field_name  [name, id, class]
     * @param  [string] $value   [text in textarea]
     * @return [string]          [textarea html element]
     */
    public function build_textarea($field_name, $value)
    {
        return "<textarea name='$field_name' id='$field_name' class='form-control $field_name'>$value</textarea>";
    }

    /**
     * [build_bool_input description]
     * @param  [string] $type    [checkbox/radiobutton]
     * @param  [array] $field_name  [name/id/class]
     * @param  [string] $label   [the label value]
     * @param  [string] $checked  [true/false]
     * @return [string]          [bool input html element]
     */
    public function build_bool_input($type, $field_name, $label, $checked = false)
    {
        $checked = $checked ? 'checked' : '';
        if ($type == 'radio') {
            return "<label><input type='$type' name='$field_name' id='$field_name' class='form-control $field_name' value='$label' $checked /> " . ucfirst($label) . "</label>";
        } else {
            return "<label><input type='$type' name='$field_name' id='$field_name' class='form-control $field_name' $checked /> " . ucfirst($label) . "</label>";
        }
    }

    /**
     * [build_upload description]
     * @param  [array] $field_name     [name, id, class]
     * @param  [array] $multiple       [true/false]
     * @return [string]             [file html element]
     */
    public function build_upload($field_name, $multiple)
    {
        return "<input type='file' name='".$field_name."[]' id='$field_name' class='$field_name' multiple='$multiple' />";
    }

    /**
     * [get_external_values retrievs structure and values of another table]
     * @param  [array] $table    [the table name]
     * @return [object]          [structure and values of the given table]
     */
    private function get_external_values($table)
    {
        $structure = $this->model->get_table_structure($table);
        $result = $this->model->get_table_values($table);
        $this->data = $result;
        $obj = new stdClass();
        $obj->table = $this->data;
        $obj->structure = $structure;
        $obj->data = $this->data;
        return $obj;
    }

    /**
     * [get_table_structure get the structure of the given table]
     * @return [array] [structure of a given table]
     */
    public function get_table_structure()
    {
        $structure = $this->model->get_table_structure($this->table_name);
        $result = array();
        for ($i = 0; $i < count($structure); ++$i) {
            $result[] = array('Field' => $structure[$i]['Field'], 'Type' => $structure[$i]['Type']);
        }
        return $result;
    }

    /**
     * [drop_ignored_inputs removes from the array the inputs that must not be processed]
     * @return [null]
     */
    private function drop_ignored_inputs()
    {
        if (isset($this->ignored_inputs)) {
            foreach ($this->ignored_inputs as $ignored) {
                for ($i = 0; $i < count($this->structure); ++$i) {
                    if (array_search($ignored, $this->structure[$i]) !== false) {
                        array_splice($this->structure, $i, 1);
                    }
                }
            }
        }
    }

    /**
     * [process_hidden_inputs put hidden fields in processed array and
     * returns an array with the names and eventually the value of the hidden fields]
     * @return [array] [hidden inputs]
     */
    private function process_hidden_inputs()
    {
        $hidden_fields = array();
        if (isset($this->hidden_inputs)) {
            foreach ($this->hidden_inputs as $hidden) {
                for ($i = 0; $i < count($this->structure); ++$i) {
                    $input = $this->structure[$i];
                    if (array_search($hidden, $this->structure[$i]) !== false) {
                        $hidden_fields = array($input["Field"] => isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '');
                        array_push($this->processed, $input["Field"]);
                    }
                }
            }
        }
        return $hidden_fields;
    }

    /**
     * [build_form description]
     * @return [string] [the whole form]
     */
    public function build_form()
    {
        $form = '';
        $this->drop_ignored_inputs();
        $hidden_fields = $this->process_hidden_inputs();
        $form .= '<form action="save.php" id="save-form" enctype="multipart/form-data" method="post" accept-charset="utf-8">';
        if (count($hidden_fields) > 0) {
            foreach ($hidden_fields as $key => $values) {
                $form .= "<input type='hidden' name='$key', value='' />";
            }
        }
        if ($this->edit_mode) {
            $form .= "<input type='hidden' name='item_id', value='$this->item_id' />";
            $action = 'Edit';
        } else {
            $action = 'Add';
        }
        $form .= <<<FRM
					<div>
						<h3>$action $this->table_name</h3>
					</div>
FRM;
        for ($i = 0; $i < count($this->structure); ++$i) {
            $input = $this->structure[$i];
            $form .= "<fieldset>";
            // $form .= "<div class='field-wrapper'>";
            if (count($this->lists) > 0) {
                if (isset($this->lists[$input["Field"]])) {
                    $data = array();
                    foreach ($this->lists[$input["Field"]] as $list_item) {
                        $data[str_replace(' ', '_', $list_item)] = $list_item;
                    }
                    $selected = isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '';
                    $form .= $this->build_label($input["Field"]);
                    $form .= $this->build_select($input["Field"], $data, $selected);

                    array_push($this->processed, $input["Field"]);
                }
                // }
            }
            if (count($this->selects) > 0) {
                if (in_array($input["Field"], $this->selects)) {
                    foreach ($this->selects as $select) {
                        $obj = $this->get_external_values($select);
                        foreach ($obj->data as $cd) {
                            $data[$cd['id']] = $cd['name'];
                        }
                        $selected = isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '';
                        $form .= $this->build_label($input["Field"]);

                        $form .= $this->build_select($input["Field"], $data, $selected);

                        array_push($this->processed, $input["Field"]);
                    }
                }
            }
            if (array_key_exists($input["Field"], $this->radios)) {
                $type = 'radio';
                foreach ($this->radios[$input["Field"]] as $option) {
                    if (strtolower($this->item[$input['Field']]) == strtolower($option)) {
                        $checked = true;
                    } else {
                        $checked = false;
                    }
                    $label = ucfirst($option);
                    $form .= $this->build_bool_input($type, $input["Field"], $label, $checked);
                }
                array_push($this->processed, $input["Field"]);
            }

            if (count($this->upload) > 0) {
                foreach ($this->upload as $upload) {
                    if ($input["Field"] == $upload) {
                        $pictures = isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '';
                        if (!empty($pictures)) {
                            $pics = explode(';', $pictures);
                            $x = 0;
                            foreach ($pics as $p) {
                                $form .= "<div class='img-wrapper'>";
                                $form .= "<img src='" . $this->site_url . "pictures/$p' />";
                                $form .= "<div class='controls'>";
                                if ($x != 0) {
                                    $form .= $this->build_bool_input('radio', $input["Field"], $p, false);
                                } else {
                                    $form .= '<label>Default Image</label>';
                                }
                                $form .= "<a class='btn btn-danger btn-xs del-image' data-image='$p' name='deleteImage' data-id='$this->item_id' /> Delete image</a>";
                                $form .= '</div>';
                                $form .= '</div>';
                                ++$x;
                            }
                        }
                        $form .= '<div id="file-uploader">';
                        $form .= $this->build_label($input["Field"]);
                        $form .= $this->build_upload($input["Field"], true);
                        $form .= '<div id="preview"></div>';

                        array_push($this->processed, $input["Field"]);
                    }
                }
            }
            if (!in_array($input["Field"], $this->processed)) {
                if (stristr($input['Type'], 'varchar') !== false) {
                    $form .= $this->build_label($input["Field"]);
                    $form .= $this->build_input($input["Field"], isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '');
                } elseif (stristr($input['Type'], 'text') !== false) {
                    $form .= $this->build_label($input["Field"]);
                    $form .= $this->build_textarea($input["Field"], isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '');
                } elseif (stristr($input['Type'], 'tinyint') !== false || stristr($input['Type'], 'bit') !== false) {
                    $checked = (isset($this->item[$input["Field"]]) && $this->item[$input["Field"]] == '1') ? 'checked' : '';
                    $type = 'checkbox';
                    $label = ucfirst($input["Field"]);
                    $form .= $this->build_bool_input($type, $input["Field"], $label, $checked);
                } elseif (stristr($input['Type'], 'int') !== false) {
                    $form .= $this->build_label($input["Field"]);
                    $form .= $this->build_input($input["Field"], isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '');
                } elseif (stristr($input['Type'], 'decimal') !== false) {
                    $form .= $this->build_label($input["Field"]);
                    $form .= $this->build_input($input["Field"], isset($this->item[$input["Field"]]) ? $this->item[$input["Field"]] : '');
                }
            }
            $form .= '</fieldset>';
        }
        $form .= <<<FRM
				<div>
					<div class='row'>
						<div class='col-lg-12'>
                            <input type="submit" value="Save" name="confirm" />
                            <a class="btn-cancel" href="manage.php">Cancel</a>
						</div>
					</div>
				</div>
			</div>
FRM;
        $form .= "</form>";
        return $form;
    }
}
