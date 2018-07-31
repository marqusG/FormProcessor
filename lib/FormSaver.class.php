<?php
/**
 * Class FormSaver
 * @author Marco Gasi
 * @author blog codingfix.com
 *
 * FormSaver gets data from the form built by FormSaver, processes them and
 * puts them in the given mysql table
 */
 spl_autoload_register(function () {
     include 'FormModel.class.php';
 });
 /**
  * [FormSaver gets data submitted by FormBuilder, processes them as needed and provides storage methods for files and data]
  */
class FormSaver
{
    /**
     * [private instance of the class FormModel]
     * @var [object]
     */
    private $model;
    /**
     * [private true or false]
     * @var [boolean]
     */
    private $edit_mode;
    /**
     * [private the id of the record we are editing]
     * @var [integer]
     */
    private $item_id;
    /**
     * [private the name of the table we are processing]
     * @var [string]
     */
    private $table_name;
    /**
     * [private the absolute path to the document root]
     * @var [string]
     */
    private $root;
    /**
     * [private multidimensional array which sets the valid extension accordingly to the file type]
     * @var [array]
     */
    private $valid_extensions;
    /**
     * [private list of file types will be uploaded: documents, pictures and so on; they must be identical to the table columns' names where the related data will be stored]
     * @var [type]
     */
    private $filetypes;

    /**
     *
     * @param [integer] $item_id [the id of the item to edit]
     */
    /**
     * [__construct initializes properties using config file, eventually creates a new directory for uploading files to; if $item_id is provided, puts the class in edit_mode]
     * @param [string] $custom_target_dir [a custom directory tree where the directories to store files will be created]
     * @param [integer] $item_id          [description]
     */
    public function __construct($custom_target_dir = '', $item_id = null)
    {
        $config = require "config.php";
        $this->table_name = $config['database']['table_name'];
        $this->filetypes = $config['general']['upload'];
        $this->valid_extensions = $config['general']['valid_extensions'];
        $this->model = new FormModel();
        $this->root = getenv("DOCUMENT_ROOT") . DIRECTORY_SEPARATOR;
        $root_target_dir = empty($custom_target_dir) ? $config['general']['root_target_dir'] : $custom_target_dir;
        $directories = explode('/', $root_target_dir);
        if (count($directories) == 1) {
            if (!empty($root_target_dir)) {
                if (!file_exists($this->root . $root_target_dir) || !is_dir($this->root . $root_target_dir)) {
                    mkdir($this->root . $root_target_dir);
                }
                $this->root .= $root_target_dir . DIRECTORY_SEPARATOR;
            }
        } elseif (count($directories) > 1) {
            foreach ($directories as $d) {
                if (!empty($d)) {
                    if (!file_exists($this->root . $d) || !is_dir($this->root . $d)) {
                        mkdir($this->root . $d);
                    }
                    $this->root .= $d . DIRECTORY_SEPARATOR;
                }
            }
        }
        if (count($this->filetypes)>0) {
            foreach ($this->filetypes as $dir) {
                if (isset($dir)) {
                    if (!file_exists($this->root . $dir) || !is_dir($this->root . $dir)) {
                        mkdir($this->root . $dir);
                    }
                }
            }
        }
        $this->item_id = $item_id;
        if (isset($item_id) && is_int($item_id)) {
            $this->edit_mode = true;
            $item_data = $this->model->get_item_data($this->table_name, $this->item_id);
            $this->item = $item_data[0];
        }
    }

    /**
     * creates a semicolon-separated string with the names of the uploaded files;
     * if some file is set to be the default file, the strings is managed to put the
     * default file name at first place; duplcated file names are removed
     * @param  [string] $existing_files  [semicolon-separated string with files' names]
     * @param  [string] $default_file    [the default file name]
     * @param  [array] $new_files        [new uploaded files]
     * @return [string]                  [semicolon-separated string with processed files' names]
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
            $final_files = !empty($existing_files) ? $existing_files . ';' . $new_fs : $new_fs;
        } else {
            $final_files = trim($existing_files, ';');
        }

        if (isset($default_file)) {
            $all_files = explode(';', $final_files);
            if ($all_files[0] !== $default_file) {
                for ($i = 0; $i < count($all_files); ++$i) {
                    if ($all_files[$i] === $default_file) {
                        $final_files = $all_files[$i] . ';';
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
     * actually stores uploaded files in the server
     * @param  [string] $file_type   [default file name]
     * @param  [string] $default     [default file name]
     * @return [array]               [an array which holds an array of errors if
     *                               errors occurred otherwise the array files with
     *                               the semicolon-separated string]
     */
    public function process_files($file_type, $default = null)
    {
        $result = array();
        $files = array();

        for ($i = 0; $i < count($_FILES["$file_type"]['name']); $i++) {
            $max_file_size = 1024 * 2000; //1 Mb
            $ext = explode('.', basename($_FILES["$file_type"]['name'][$i]));
            $file_extension = end($ext);
            $name = strtolower(filter_var($_FILES["$file_type"]['name'][$i], FILTER_SANITIZE_STRING));
            if (($_FILES["$file_type"]["size"][$i] <= $max_file_size) && in_array($file_extension, $this->valid_extensions["$file_type"])) {
                if (move_uploaded_file($_FILES["$file_type"]['tmp_name'][$i], $this->root . $file_type . DIRECTORY_SEPARATOR . $name)) {
                    // echo "file has been moved to " . $this->root . $file_type . DIRECTORY_SEPARATOR . $name ."<br />";
                    if ($file_type == 'pictures') {
                        $img = @imagecreatefromstring(@file_get_contents($this->root . $file_type . DIRECTORY_SEPARATOR . $name));
                        if ($img !== false) {
                            $files[] = strtolower($name);
                            imagedestroy($img);
                        } else {
                            unlink($this->root . $file_type . DIRECTORY_SEPARATOR . $name);
                            $result["errors"][] = "The uploaded file is not a valid image file.";
                        }
                    } else {
                        $files[] = strtolower($name);
                    }
                } else {
                    $result["errors"][] = "Error moving file to the destination directory.";
                }
            } else {
                $result["errors"][] = "Invalid file size";
            }
        }

        if (count($files) > 0) {
            $new_files = implode(';', $files);
            $files = rtrim($new_files, ';');
            if ($this->edit_mode) {
                $existing_files = $this->model->get_files($this->item_id);
                if ($existing_files !== false) {
                    $files = $this->create_files_string($existing_files, $default, $new_files);
                }
            } elseif ($default != null) {
                $files = $this->create_files_string($files, $default, $new_files);
            }
        } else {
            if ($this->edit_mode) {
                $existing_files = $this->model->get_files($this->item_id);
                if ($existing_files !== false) {
                    $files = $this->create_files_string($existing_files, $default, $new_files);
                }
            }
        }
        $result['files'] = $files;
        return $result;
    }

    /**
     * manages the saving process
     * @return [array or integer] [returns the array of errors or the id of the
     *                             last element inserted in the database]
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
                if (isset($_FILES["$ft"]["name"])) {
                    $default = null;
                    if (isset($post['default'])) {
                        $default = $post['default'];
                        $key = array_search('default', array_keys($post));
                        if ($key !== false) {
                            array_splice($post, $key, 1);
                        }
                    }

                    if (!$this->edit_mode) {
                        $result = $this->process_files($ft, $default);
                        $errors = isset($result['errors']) ? $result['errors'] : array();
                        $files = isset($result['files']) ? $result['files'] : '';
                    } else {
                        $existing_files = $this->model->get_files($this->table, $this->prod_id);
                        $fls = $this->create_files_string($existing_files, $default);
                        $files = $fls;
                    }
                    if (count($files) > 0) {
                        $post["$ft"] = $files;
                    }
                    $key = array_search('id', $post);
                    if ($key !== false) {
                        array_splice($post, $key, 1);
                    }
                }
            }
            return $this->model->insert($post, $this->item_id);
        } else {
            return $this->model->insert($post, $this->item_id);
        }
    }
}
