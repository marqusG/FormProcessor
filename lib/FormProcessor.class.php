<?php

/**
 * Class FormProcessor.
 *
 * @author    Marco Gasi
 * @author    blog codingfix.com
 *
 * @version   v. 1.0
 *
 * @copyright Copyright (c) 2018, Marco
 */
spl_autoload_register(function () {
    include_once 'FormModel.class.php';
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
 */
class FormProcessor
{
    /**
     * [public $table_name, the name of the table we want to process].
     *
     * @var [string]
     */
    public $table_name;

    /**
     * [private $lists, a multiple associative array of values thant must be grouped in select elements].
     *
     * @var [array]
     */
    private $lists;

    /**
     * [private $selects an array of names wich correspond to external tables which host the values or the index it is present in the processed table under the column with the same name].
     *
     * @var [array]
     */
    private $selects;

    /**
     * [private $hidden_inputs a list of fields we don't want be edited but that must be present in the form ].
     *
     * @var [array]
     */
    private $hidden_inputs;

    /**
     * [private $ignored_inputs, a list of those fields we don't want to be edited or just shown].
     *
     * @var [array]
     */
    private $ignored_inputs;

    /**
     * [private site url, used to link files and display them to the user in the edit form].
     *
     * @var [string]
     */
    private $site_url;

    /**
     * [private $upload, a list of table columns names that require to upload one or more file].
     *
     * @var [type]
     */
    private $upload;

    /**
     * [private $radios, an array with the column name as key and another array with the possible velaues as value].
     *
     * @var [array]
     */
    private $radios;

    /**
     * [private $edit_mode, true if the form is in editing mode].
     *
     * @var [boolean]
     */
    private $edit_mode;

    /**
     * [private $item_id, the id of the item is being edited].
     *
     * @var [integer]
     */
    private $item_id;

    /**
     * [private $item, an array with the existing values for the given item_id].
     *
     * @var array
     */
    private $item;

    /**
     * private $model, internal object of class FormModel.
     *
     * @var object
     */
    private $model;

    /**
     * private $structure, complex array which holds a mysql table structure.
     *
     * @var array
     */
    private $structure;

    /**
     * private $processed, array which is dinamically filled with the field names once they are processed.
     *
     * @var array
     */
    private $processed;

    /**
     * private $javascript, if true the returned value will contain the javascript code to append to the page.
     *
     * @var bool
     */
    private $print_required_javascript;

    /**
     * [private the absolute path to the document root].
     *
     * @var [string]
     */
    private $root;
    /**
     * [private the absolute path to the folder where files must be saved to].
     *
     * @var [string]
     */
    private $root_target_dir;
    /**
     * [private multidimensional array which sets the valid extension accordingly to the file type].
     *
     * @var [array]
     */
    private $valid_extensions;
    /**
     * [private list of file types will be uploaded: documents, pictures and so on; they must be identical to the table columns' names where the related data will be stored].
     *
     * @var [array]
     */
    private $filetypes;
    /**
     * [private list of document extensions].
     *
     * @var [array]
     */
    private $doc_ext;
    /**
     * [private list of pictures extensions].
     *
     * @var [array]
     */
    private $pic_ext;

    /**
     * initializes properties using provided config file].
     *
     * @param [integer] $item_id [the id of the item if we are building a
     *                           form to edit existing data]
     */
    public function __construct($table_name, $item_id = null)
    {
        $config = require 'config.php';
        $this->table_name = $table_name;
        $this->site_url = $config['general']['site_url'];
        $this->lists = isset($config['general']['lists']) ? $config['general']['lists'] : null;
        $this->selects = isset($config['general']['selects']) ? $config['general']['selects'] : null;
        $this->hidden_inputs = isset($config['general']['hidden_inputs']) ? $config['general']['hidden_inputs'] : null;
        $this->ignored_inputs = isset($config['general']['ignored_inputs']) ? $config['general']['ignored_inputs'] : null;
        $this->site_url = isset($config['general']['site_url']) ? $config['general']['site_url'] : '';
        $this->upload = isset($config['general']['upload']) ? $config['general']['upload'] : '';
        $this->radios = isset($config['general']['radios']) ? $config['general']['radios'] : '';
        $this->print_required_javascript = isset($config['general']['print_required_javascript']) ? $config['general']['print_required_javascript'] : true;
        $this->filetypes = $config['general']['upload'];
        $this->valid_extensions = $config['general']['valid_extensions'];
        $this->root = getenv('DOCUMENT_ROOT').DIRECTORY_SEPARATOR;
        $this->root_target_dir = !empty($config['general']['root_target_dir']) ? rtrim($config['general']['root_target_dir'], '/').'/' : '';
        $this->pic_ext = isset($config['general']['valid_extension']['pictures']) ? $config['general']['valid_extension']['pictures'] : array('jpg', 'png', 'gif');
        $this->doc_ext = isset($config['general']['valid_extension']['documents']) ? $config['general']['valid_extension']['documents'] : array('txt', 'doc', 'odf', 'pdf');
        $this->processed = array();
        $this->model = new FormModel($this->table_name);
        $this->structure = $this->get_table_structure($this->table_name);
        $this->build_dir_tree();
        if (isset($item_id) && is_numeric($item_id)) {
            $this->edit_mode = true;
            $this->item_id = $item_id;
            $item_data = $this->model->get_item_data($this->table_name, $this->item_id);
            $this->item = $item_data[0];
        }
    }

    /**
     * Builds the javascript code needed to display images and document icons when uploading.
     *
     * @return string
     */
    public function print_javascript_for_add_page()
    {
        $js = <<<JSC
                window.URL = window.URL || window.webkitURL;
                var elPreview = document.getElementById("pictures-preview"),
                        imagesData = [],
                        uploadable = true;
                useBlob = false && window.URL;

                function readImage(file) {
                    console.log('reading file');
                    var reader = new FileReader();
                    reader.addEventListener("load", function () {
                        var image = new Image();
                        image.addEventListener("load", function () {
                            var imgSize = Math.round(file.size / 1024);
                            var imageInfo = {
                                imgname: file.name,
                                imgwidth: image.width,
                                imgheight: image.height,
                                imgtype: file.type,
                                imgsize: Math.round(file.size / 1024)};
                            imagesData.push(imageInfo);
                            if (imageInfo.imgsize <= 2000) {
                                elPreview.appendChild(this);
                                elPreview.insertAdjacentHTML("beforeend", '<br><input type="radio" class="radio-col-purple" id="'+imageInfo.imgname+'" name="default" value="' + imageInfo.imgname + '" /><label for="'+imageInfo.imgname+'"> Make default</label><br><br>');
                            } else {
                                $('#pictures-uploader').append("<p style='color: red'>" + imageInfo.imgname + " exceeds max allowed size of 2Mb and it won't be uploaded.</p>");
                                console.log(imageInfo.imgname + ' is not uploadable');
                                uploadable = false;
                            }
                        });
                        image.src = useBlob ? window.URL.createObjectURL(file) : reader.result;
                        if (useBlob) {
                            window.URL.revokeObjectURL(file);
                        }
                    });
                    reader.readAsDataURL(file);
                }
JSC;
        if (count($this->upload) > 0) {
            foreach ($this->upload as $upload) {
                $input_id = $upload;
                $uploader_id = $upload.'-uploader';
                $js .= <<<JSC
            $(document).on('change', '#$input_id', function () {
                var this_input = $(this);
                uploadable = true;
                $('#$input_id-preview').empty();
                $('#$uploader_id p').empty();
                for (var i = 0; i < $(this).get(0).files.length; ++i) {
                    var file = $(this).get(0).files[i];
                    if (this_input.attr('id') == 'pictures') {
                        if ((/\.(png|jpeg|jpg|gif)$/i).test(file.name)) {
                            readImage(file);
                        } else {
                            $('#$input_id').val('');
                            alert('Only images files accepted.');
                        }
                    } else if (this_input.attr('id') == 'documents') {
                        if ((/\.(png|jpeg|jpg|gif)$/i).test(file.name)) {
                            $('#$input_id').val('');
                            alert('Only text documents accepted.');
                        } else if ((/\.(txt)$/i).test(file.name)) {
                            $('#$input_id-preview').append("<img src='icons/txt.png' />");
                            $('#$input_id-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(doc)$/i).test(file.name)) {
                            $('#$input_id-preview').append("<img src='icons/doc.png' />");
                            $('#$input_id-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(odt)$/i).test(file.name)) {
                            $('#$input_id-preview').append("<img src='icons/doc.png' />");
                            $('#$input_id-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(pdf)$/i).test(file.name)) {
                            $('#$input_id-preview').append("<img src='icons/pdf.png' />");
                            $('#$input_id-preview').append('<p>' + file.name + '</p>');
            } else {
                            $('#$input_id').val('');
                            alert('Only .txt, .doc, .odt and .pdf extensions are allowed.');
                        }
                    }
                }
            });
JSC;
            }
        }

        return $js;
    }

    /**
     * Builds the javascript code needed to delete images and documents when editing.
     *
     * @return string
     */
    public function print_javascript_for_edit_page()
    {
        $table_name = $this->table_name;
        $site_url = $this->site_url.$this->root_target_dir;
        $js = <<<JSC
                $(document).on('click', '.del-image', function (e) {
                    e.preventDefault();
                    var this_el = $(this);
                    var item_id = $(this).data('id');
                    var file_name = $(this).data('file');
                    var site_url = '$site_url';
                    var url = site_url + 'delete_picture.php';
                    $.ajax({
                        type: 'post',
                        url: url,
                        data: {file_name: file_name, item_id: item_id},
                        success: function (result)
                        {
                            console.log(result);
                            this_el.parents('.img-wrapper').remove();

                        },
                        error: function (err)
                        {
                            console.log('result '+JSON.stringify(err, null, 2));
                        }
                    });
                });
                $(document).on('click', '.del-icon', function (e) {
                    e.preventDefault();
                    var this_el = $(this);
                    var item_id = $(this).data('id');
                    var file_name = $(this).data('file');
                    var site_url = '$site_url';
                    var url = site_url + 'delete_document.php';
                    $.ajax({
                        type: 'post',
                        url: url,
                        data: {file_name: file_name, item_id: item_id},
                        success: function (result)
                        {
                            console.log(result);
                            this_el.parents('.icon-wrapper').remove();

                        },
                        error: function (err)
                        {
                            console.log('result '+JSON.stringify(err, null, 2));
                        }
                    });
                });
JSC;

        return $js;
    }

    /**
     * [build_select ].
     *
     * @param [array]   $field_name [name, id, class]
     * @param [array]   $data       [options' values and text]
     * @param [boolean] $selected   [selected option]
     *
     * @return [string] [select html element]
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
        $select .= '</select>';

        return $select;
    }

    /**
     * [build_label ].
     *
     * @param [string] $value [value for name, id and text]
     *
     * @return [string] [label html element]
     */
    public function build_label($value)
    {
        return "<label for='".$value."' id='".$value."_lbl'>".ucfirst($value).'</label>';
    }

    /**
     * [build_input ].
     *
     * @param [array]  $field_name [name, id, class]
     * @param [string] $value      [text value]
     *
     * @return [string] [input html element]
     */
    public function build_input($field_name, $value)
    {
        return "<div class='form-group'><div class='form-line'><input type='text' name='$field_name' id='$field_name' class='form-control $field_name' value='$value' /></div></div>";
    }

    /**
     * [build_textarea description].
     *
     * @param [array]  $field_name [name, id, class]
     * @param [string] $value      [text in textarea]
     *
     * @return [string] [textarea html element]
     */
    public function build_textarea($field_name, $value)
    {
        return "<div class='form-group'><div class='form-line'><textarea name='$field_name' id='$field_name' class='form-control $field_name'>$value</textarea></div></div>";
    }

    /**
     * [build_bool_input description].
     *
     * @param [string] $type       [checkbox/radiobutton]
     * @param [array]  $field_name [name/id/class]
     * @param [string] $label      [the label value]
     * @param [string] $checked    [true/false]
     *
     * @return [string] [bool input html element]
     */
    public function build_bool_input($type, $field_name, $label, $checked = false)
    {
        $checked = $checked ? 'checked' : '';
        if ($type == 'radio') {
            return "<input type='$type' name='$field_name' id='$field_name$label' class='radio-col-purple form-control $field_name' value='$label' $checked /> <label for='$field_name$label'>".ucfirst($label).'</label>';
        } else {
            return "<input type='$type' name='$field_name' id='$field_name' class='filled-in chk-col-purple form-control $field_name' $checked /> <label for='$field_name'>".ucfirst($label).'</label>';
        }
    }

    /**
     * [build_upload description].
     *
     * @param [array] $field_name [name, id, class]
     * @param [array] $multiple   [true/false]
     *
     * @return [string] [file html element]
     */
    public function build_upload($field_name, $multiple)
    {
        return "<input type='file' name='".$field_name."[]' id='$field_name' class='$field_name' multiple='$multiple' />";
    }

    /**
     * [get_external_values retrievs structure and values of another table].
     *
     * @param [array] $table [the table name]
     *
     * @return [object] [structure and values of the given table]
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
     * [get_table_structure get the structure of the given table].
     *
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
     * [drop_ignored_inputs removes from the array the inputs that must not be processed].
     *
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
     * returns an array with the names and eventually the value of the hidden fields].
     *
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
                        $hidden_fields = array($input['Field'] => isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
                        array_push($this->processed, $input['Field']);
                    }
                }
            }
        }

        return $hidden_fields;
    }

    /**
     * [build_form description].
     *
     * @return [string] [the whole form]
     */
    public function build_form()
    {
        $form = '';
        $this->drop_ignored_inputs();
        $hidden_fields = $this->process_hidden_inputs();
        $form .= '<form action="save.php" id="save-form" enctype="multipart/form-data" method="post" accept-charset="utf-8">';
        //required to keep the table name dinamically assigned to the constructor
        $form .= "<input type='hidden' name='table_name', value='$this->table_name' />";
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
            $form .= '<fieldset>';
            if (count($this->lists) > 0) {
                if (isset($this->lists[$input['Field']])) {
                    $data = array();
                    foreach ($this->lists[$input['Field']] as $list_item) {
                        $data[str_replace(' ', '_', $list_item)] = $list_item;
                    }
                    $selected = isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '';
                    $form .= $this->build_label($input['Field']);
                    $form .= $this->build_select($input['Field'], $data, $selected);

                    array_push($this->processed, $input['Field']);
                }
            }
            if (count($this->selects) > 0) {
                if (in_array($input['Field'], $this->selects)) {
                    $obj = $this->get_external_values($input['Field']);
                    foreach ($obj->data as $cd) {
                        $data[$cd['id']] = $cd['name'];
                    }
                    $selected = isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '';
                    $form .= $this->build_label($input['Field']);

                    $form .= $this->build_select($input['Field'], $data, $selected);

                    array_push($this->processed, $input['Field']);
                }
            }
            if (array_key_exists($input['Field'], $this->radios)) {
                $type = 'radio';
                $form .= $this->build_label($input['Field']);
                foreach ($this->radios[$input['Field']] as $option) {
                    if (strtolower($this->item[$input['Field']]) == strtolower($option)) {
                        $checked = true;
                    } else {
                        $checked = false;
                    }
                    $label = ucfirst($option);
                    $form .= $this->build_bool_input($type, $input['Field'], $label, $checked);
                }
                array_push($this->processed, $input['Field']);
            }

            if (count($this->upload) > 0) {
                foreach ($this->upload as $upload) {
                    if ($input['Field'] == $upload) {
                        $files = isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '';
                        if (!empty($files)) {
                            $fls = explode(';', $files);
                            $x = 0;
                            foreach ($fls as $f) {
                                $parts = explode('.', $f);
                                $ext = end($parts);
                                if (in_array($ext, $this->pic_ext)) {
                                    $form .= "<div class='img-wrapper'>";
                                    $form .= "<img src='".$this->site_url.$this->root_target_dir."pictures/$f' />";
                                    $form .= "<div class='controls'>";
                                    if ($x != 0) {
                                        $form .= $this->build_bool_input('radio', $input['Field'], 'Make default', false);
                                    } else {
                                        $form .= '<label>Default Image</label>';
                                    }
                                    $form .= "<p><a class='btn btn-danger btn-xs del-image' data-file='$f' name='deleteImage' data-id='$this->item_id' /> Delete image</a></p>";
                                    $form .= '</div>';
                                    $form .= '</div>';
                                } elseif (in_array($ext, $this->doc_ext)) {
                                    $form .= "<div class='icon-wrapper'>";
                                    $form .= "<img src='".$this->site_url.$this->root_target_dir."icons/$ext.png' />";
                                    $form .= "<div class='controls'>";
                                    $form .= "<p>$f</p>";
                                    $form .= "<p><a class='btn btn-danger btn-xs del-icon' data-file='$f' name='deleteDoc' data-id='$this->item_id' /> Delete document</a></p>";
                                    $form .= '</div>';
                                    $form .= '</div>';
                                }
                                ++$x;
                            }
                        }
                        $form .= '<div id="'.$input['Field'].'-uploader" class="uploader">';
                        $form .= $this->build_label($input['Field']);
                        $form .= $this->build_upload($input['Field'], true);
                        $form .= '<div id="'.$input['Field'].'-preview" class="previewer"></div>';

                        array_push($this->processed, $input['Field']);
                    }
                }
            }
            if (!in_array($input['Field'], $this->processed)) {
                if (stristr($input['Type'], 'varchar') !== false) {
                    $form .= $this->build_label($input['Field']);
                    $form .= $this->build_input($input['Field'], isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
                } elseif (stristr($input['Type'], 'text') !== false) {
                    $form .= $this->build_label($input['Field']);
                    $form .= $this->build_textarea($input['Field'], isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
                } elseif (stristr($input['Type'], 'tinyint') !== false || stristr($input['Type'], 'bit') !== false) {
                    $checked = (isset($this->item[$input['Field']]) && $this->item[$input['Field']] == '1') ? 'checked' : '';
                    $type = 'checkbox';
                    $label = ucfirst($input['Field']);
                    $form .= $this->build_bool_input($type, $input['Field'], $label, $checked);
                } elseif (stristr($input['Type'], 'int') !== false) {
                    $form .= $this->build_label($input['Field']);
                    $form .= $this->build_input($input['Field'], isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
                } elseif (stristr($input['Type'], 'decimal') !== false) {
                    $form .= $this->build_label($input['Field']);
                    $form .= $this->build_input($input['Field'], isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
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
        $form .= '</form>';

        return $form;
    }

    /**********************************************************************************
     **********************************************************************************
     *
     *                                  SAVE
     *
     **********************************************************************************
     **********************************************************************************/

    private function build_dir_tree()
    {
        $root = $this->root;
        if (!empty($this->root_target_dir)) {
            $directories = explode('/', $this->root_target_dir);
            if (count($directories) == 1) {
                if (!file_exists($root.$this->root_target_dir) || !is_dir($root.$this->root_target_dir)) {
                    mkdir($root.$this->root_target_dir);
                }
                $root .= $this->root_target_dir.DIRECTORY_SEPARATOR;
            } elseif (count($directories) > 1) {
                foreach ($directories as $d) {
                    if (!empty($d)) {
                        if (!file_exists($root.$d) || !is_dir($root.$d)) {
                            mkdir($root.$d);
                        }
                        $root .= $d.DIRECTORY_SEPARATOR;
                    }
                }
            }
        }
        if (count($this->filetypes) > 0) {
            foreach ($this->filetypes as $dir) {
                if (isset($dir)) {
                    if (!file_exists($root.$dir) || !is_dir($root.$dir)) {
                        mkdir($root.$dir);
                    }
                }
            }
        }
    }

    /**
     * creates a semicolon-separated string with the names of the uploaded files;
     * if some file is set to be the default file, the strings is managed to put the
     * default file name at first place; duplcated file names are removed.
     *
     * @param [string] $existing_files [semicolon-separated string with files' names]
     * @param [string] $default_file   [the default file name]
     * @param [array]  $new_files      [new uploaded files]
     *
     * @return [string] [semicolon-separated string with processed files' names]
     */
    public function create_files_string($existing_files, $default_file = null, $new_files = null)
    {
        $new_fs = '';
        if (isset($new_files)) {
            if (!isset($existing_files)) {
                $old_fs = array();
            } else {
                $old_fs = explode(';', $existing_files);
            }
            if (count($new_files) > 0 && count($old_fs) > 0) {
                $duplicates = array_intersect(explode(';', $new_files), $old_fs);
                if (count($duplicates) > 0) {
                    for ($i = 0; $i < count($duplicates); ++$i) {
                        for ($x = 0; $x < count($old_fs); ++$x) {
                            if ($old_fs[$x] == $duplicates[$i]) {
                                array_splice($old_fs, $x, 1);
                            }
                        }
                    }
                }
            }
            $existing_files = implode(';', $old_fs);
            $new_fs = $new_files;
        }
        if ($existing_files !== '') {
            $existing_files = rtrim($existing_files, ';');
        }
        if ($new_fs != '') {
            $final_files = !empty($existing_files) ? $existing_files.';'.$new_fs : $new_fs;
        } else {
            $final_files = trim($existing_files, ';');
        }

        if (isset($default_file)) {
            $all_files = explode(';', $final_files);
            if ($all_files[0] !== $default_file) {
                for ($i = 0; $i < count($all_files); ++$i) {
                    if ($all_files[$i] === $default_file) {
                        $final_files = $all_files[$i].';';
                        array_splice($all_files, $i, 1);
                    }
                }
                $temp_files = implode(';', $all_files);
                $final_files .= rtrim($temp_files, ';');
            }
        }

        return $final_files;
    }

    /**
     * actually stores uploaded files in the server.
     *
     * @param [string] $file_type [default file name]
     * @param [string] $default   [default file name]
     *
     * @return [array] [an array which holds an array of errors if
     *                 errors occurred otherwise the array files with
     *                 the semicolon-separated string]
     */
    public function process_files($file_type, $default = null)
    {
        $result = array();
        $files = array();

        for ($i = 0; $i < count($_FILES["$file_type"]['name']); ++$i) {
            $max_file_size = 1024 * 2000; //1 Mb
            $ext = explode('.', basename($_FILES["$file_type"]['name'][$i]));
            $file_extension = end($ext);
            $name = strtolower(filter_var($_FILES["$file_type"]['name'][$i], FILTER_SANITIZE_STRING));
            if (($_FILES["$file_type"]['size'][$i] <= $max_file_size) && in_array($file_extension, $this->valid_extensions["$file_type"])) {
                if (move_uploaded_file($_FILES["$file_type"]['tmp_name'][$i], $this->root.$this->root_target_dir.$file_type.DIRECTORY_SEPARATOR.$name)) {
                    // echo "file has been moved to " . $this->root . $file_type . DIRECTORY_SEPARATOR . $name ."<br />";
                    if ($file_type == 'pictures') {
                        $img = @imagecreatefromstring(@file_get_contents($this->root.$this->root_target_dir.$file_type.DIRECTORY_SEPARATOR.$name));
                        if ($img !== false) {
                            $files[] = strtolower($name);
                            imagedestroy($img);
                        } else {
                            unlink($this->root.$this->root_target_dir.$file_type.DIRECTORY_SEPARATOR.$name);
                            $result['errors'][] = 'The uploaded file is not a valid image file.';
                        }
                    } else {
                        $files[] = strtolower($name);
                    }
                } else {
                    $result['errors'][] = 'Error moving file to the destination directory.';
                }
            } else {
                $result['errors'][] = 'Invalid file size';
            }
        }

        if (count($files) > 0) {
            $new_files = implode(';', $files);
            $files = rtrim($new_files, ';');
            if ($this->edit_mode) {
                $existing_files = $this->model->get_files($file_type, $this->item_id);
                if ($existing_files !== false) {
                    $files = $this->create_files_string($existing_files, $default, $new_files);
                }
            } elseif ($default != null) {
                $files = $this->create_files_string($files, $default, $new_files);
            }
        } else {
            if ($this->edit_mode) {
                $existing_files = $this->model->get_files($file_type, $this->item_id);
                if ($existing_files !== false) {
                    $files = $this->create_files_string($existing_files, $default, $new_files);
                }
            }
        }
        $result['files'] = $files;

        return $result;
    }

    /**
     * manages the saving process.
     *
     * @return [array or integer] [returns the array of errors or the id of the
     *                last element inserted in the database]
     */
    public function save()
    {
        $post = array();
        $structure = $this->model->get_table_structure($this->table_name);
        for ($i = 0; $i < count($structure); ++$i) {
            $input = $structure[$i];
            foreach ($_POST as $k => $v) {
                if ($k == $input['Field']) {
                    $post[$k] = $v;
                }
            }
        }
        array_walk($post, function (&$v, $k) {
            if ($v == 'on') {
                $v = '1';
            }
        });
        $files = array();
        if (isset($this->filetypes)) {
            foreach ($this->filetypes as $ft) {
                if (!empty($_FILES["$ft"]['name'][0])) {
                    $default = null;
                    if (isset($post['default'])) {
                        $default = $post['default'];
                        $key = array_search('default', array_keys($post));
                        if ($key !== false) {
                            array_splice($post, $key, 1);
                        }
                    }
                    $result = $this->process_files($ft, $default);
                    $errors = isset($result['errors']) ? $result['errors'] : array();
                    $files = isset($result['files']) ? $result['files'] : '';
                    if (count($files) > 0) {
                        $post["$ft"] = $files;
                    }
                    $key = array_search('id', $post);
                    if ($key !== false) {
                        array_splice($post, $key, 1);
                    }
                }
            }
        }

        return $this->model->insert($post, $this->item_id);
    }

    /**********************************************************************************
     **********************************************************************************
     *
     *                                  DELETE
     *
     **********************************************************************************
     **********************************************************************************/

    /**
     * Delete single picture.
     *
     * @param [string] $image_name
     * @param [string] $item_id
     *
     * @return bool
     */
    public function delete_picture($image_name, $item_id)
    {
        if (empty($image_name) || empty($item_id)) {
            return;
        }
        $pictures = $this->model->get_files('pictures', $item_id);
        $pics = explode(';', $pictures);
        $key = array_search($image_name, $pics);
        array_splice($pics, $key, 1);
        $pictures = implode(';', $pics);
        $this->model->delete_picture($pictures, $item_id);
        if (file_exists($this->root.$this->root_target_dir.'pictures/'.$image_name)) {
            unlink($this->root.$this->root_target_dir.'pictures/'.$image_name);
        }
    }

    /**
     * Delete a document. Differently to the above function the icon is not removed.
     *
     * @param [type] $image_name
     * @param [type] $item_id
     */
    public function delete_document($doc_name, $item_id)
    {
        if (empty($doc_name) || empty($item_id)) {
            return;
        }
        $documents = $this->model->get_files('documents', $item_id);
        $docs = explode(';', $documents);
        $key = array_search($doc_name, $docs);
        array_splice($docs, $key, 1);
        $documents = implode(';', $docs);

        $this->model->delete_document($documents, $item_id);
        if (file_exists($this->root.$this->root_target_dir.'documents/'.$doc_name)) {
            unlink($this->root.$this->root_target_dir.'documents/'.$doc_name);
        }
    }
}
