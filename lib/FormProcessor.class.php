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
 *     FormBuilder reads structure and data for the given table directly from the database
 *     and it creates form elements accordingly to the column type:
 *
 *     Column type                     Input type
 *     varchar, int, decimal         text
 *     text                               textarea
 *     tinyint/bit                       checkbox
 *
 *  Other elements have to be set in the config file (read below)
 *
 *     In config files you MUST set following options
 *
 *  - database host
 *     - database username
 *     - database password
 *     - database name
 *     - table name
 *
 *  In config file you CAN setup the following additional options:
 *
 *       RADIO BUTONS: you have to set an array with column names as keys and an array which holds the possible values:
 *       for instance "radios" => array("size" => array("small", "medium", "large"))
 *     - SELECTS: you have to options
 *         1) you can set a 'list' passing manually a series of values (strings)
 *         2) you can specify one or more columns names you want to be converted in selects: in this case your table
 *            should hold a column with that name where the index of the corresponding record in another table with
 *            identical name. If you are managing a table for your products, they will probably be organized in categories;
 *            FormBuilder expects to find in your database a table called 'category' whose values (id and name) will be
 *            used to build a select.
 *     - FILES: you can set an array of columns names whose data require to upload a file. FormSaver doesn't use BLOB
 *       fields because it's a bad practice to store files directly in the database, so files will be stored in a directory
 *       called as the column's name in your server and the path to them will be stored in the database. So if you have a
 *       column called 'pictures', if such a directory doesn't exist, FormSaver will create it and it will store files in
 *       it.
 *  - rootTargetDir: if empty, files will be saved in a subdirectory in your document root; the subdirectory will be
 *    named accordingly to the name of the related table column name; if your site is www.example.com and you are
 *    uploading files for the column 'pictures', the rootTargetDir will be something like
 *    /var/www/vhosts/example.com/httpdocs/pictures; if you set a rootTargetDir to some value, like, for instance,
 *    'userId', the pictures will be saved in /var/www/vhosts/example.com/httpdocs/userId/pictures
 */
class FormProcessor
{
    /**
     * [public $tableName, the name of the table we want to process].
     *
     * @var [string]
     */
    public $tableName;

    /**
     * [private site url, used to link files and display them to the user in the edit form].
     *
     * @var [string]
     */
    private $siteUrl;

    /**
     * [private $editMode, true if the form is in editing mode].
     *
     * @var [boolean]
     */
    private $editMode;

    /**
     * [private $itemId, the id of the item is being edited].
     *
     * @var [integer]
     */
    private $itemId;

    /**
     * [private $item, an array with the existing values for the given itemId].
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
    private $rootTargetDir;

    /**
     * wraps config.php file values.
     *
     * @var [multiple array]
     */
    private $config;

    /**
     * initializes properties using provided config file].
     *
     * @param [integer] $itemId [the id of the item if we are building a
     *                          form to edit existing data]
     */
    public function __construct($tableName, $itemId = null)
    {
        $config = require 'config.php';
        $this->config = require 'config.php';
        $this->tableName = $tableName;
        $this->siteUrl = isset($config['general']['siteUrl']) ? $config['general']['siteUrl'] : '';
        $this->upload = isset($config['general']['upload'][$this->tableName]) ? $config['general']['upload'][$this->tableName] : '';
        $this->root = getenv('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR;
        $this->rootTargetDir = !empty($config['general']['rootTargetDir']) ? rtrim($config['general']['rootTargetDir'], '/') . '/' : '';
        $this->processed = array();
        $this->model = new FormModel($this->tableName);
        $this->structure = $this->getTableStructure($this->tableName);
        $this->buildDirTree();
        if (isset($itemId) && is_numeric($itemId)) {
            $this->editMode = true;
            $this->itemId = $itemId;
            $itemData = $this->model->getItemData($this->tableName, $this->itemId);
            $this->item = $itemData[0];
        }
    }

    /**
     * Builds the javascript code needed to display images and document icons when uploading.
     *
     * @return string
     */
    public function printJsForAddPage()
    {
        $jsc = <<<JSC
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
        if (count($this->config['general']['upload'][$this->tableName]) > 0) {
            foreach ($this->config['general']['upload'][$this->tableName] as $upload) {
                $inputId = $upload;
                $uploaderId = $upload . '-uploader';
                $jsc .= <<<JSC
            $(document).on('change', '#$inputId', function () {
                var this_input = $(this);
                uploadable = true;
                $('#$inputId-preview').empty();
                $('#$uploaderId p').empty();
                for (var i = 0; i < $(this).get(0).files.length; ++i) {
                    var file = $(this).get(0).files[i];
                    if (this_input.attr('id') == 'pictures') {
                        if ((/\.(png|jpeg|jpg|gif)$/i).test(file.name)) {
                            readImage(file);
                        } else {
                            $('#$inputId').val('');
                            alert('Only images files accepted.');
                        }
                    } else if (this_input.attr('id') == 'documents') {
                        if ((/\.(png|jpeg|jpg|gif)$/i).test(file.name)) {
                            $('#$inputId').val('');
                            alert('Only text documents accepted.');
                        } else if ((/\.(txt)$/i).test(file.name)) {
                            $('#$inputId-preview').append("<img src='images/doc_icons/txt.png' />");
                            $('#$inputId-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(doc)$/i).test(file.name)) {
                            $('#$inputId-preview').append("<img src='images/doc_icons/doc.png' />");
                            $('#$inputId-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(odt)$/i).test(file.name)) {
                            $('#$inputId-preview').append("<img src='images/doc_icons/doc.png' />");
                            $('#$inputId-preview').append('<p>' + file.name + '</p>');
                        } else if ((/\.(pdf)$/i).test(file.name)) {
                            $('#$inputId-preview').append("<img src='images/doc_icons/pdf.png' />");
                            $('#$inputId-preview').append('<p>' + file.name + '</p>');
            } else {
                            $('#$inputId').val('');
                            alert('Only .txt, .doc, .odt and .pdf extensions are allowed.');
                        }
                    }
                }
            });
JSC;
            }
        }

        return $jsc;
    }

    /**
     * Builds the javascript code needed to delete images and documents when editing.
     *
     * @return string
     */
    public function printJsForEditPage()
    {
        $siteUrl = $this->siteUrl . $this->rootTargetDir;
        $jsc = <<<JSC
                    $(document).on('click', '.del-image', function (e) {
                        e.preventDefault();
                        var this_el = $(this);
                        var itemId = $(this).data('id');
                        var fileName = $(this).data('file');
                        var siteUrl = '$siteUrl';
                        var url = siteUrl + 'deletePicture.php';
                        $.ajax({
                            type: 'post',
                            url: url,
                            data: {fileName: fileName, itemId: itemId},
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
                        var itemId = $(this).data('id');
                        var fileName = $(this).data('file');
                        var siteUrl = '$siteUrl';
                        var url = siteUrl + 'deleteDocument.php';
                        $.ajax({
                            type: 'post',
                            url: url,
                            data: {fileName: fileName, itemId: itemId},
                            success: function (result)
                            {
                                this_el.parents('.img-wrapper').remove();

                            },
                            error: function (err)
                            {
                                console.log(JSON.stringify(err, null, 2));
                            }
                        });
                    });
JSC;

        return $jsc;
    }

    /**
     * [getSelectMarkup ].
     *
     * @param [array]   $fieldName [name, id, class]
     * @param [array]   $data      [options' values and text]
     * @param [boolean] $selected  [selected option]
     *
     * @return [string] [select html element]
     */
    private function getSelectMarkup($fieldName, $data, $selected)
    {
        $select = "<select name='$fieldName' class='form-control $fieldName'>";
        foreach ($data as $k => $v) {
            $select .= ($k == $selected) ? "<option value='$k' selected>$v</option>" : "<option value='$k'>$v</option>";
        }
        $select .= '</select>';

        return $select;
    }

    /**
     * [getLabelMarkup ].
     *
     * @param [string] $value [value for name, id and text]
     *
     * @return [string] [label html element]
     */
    private function getLabelMarkup($value)
    {
        return !empty($value) ? "<label for='" . $value . "' id='" . $value . "_lbl'>" . ucfirst(str_replace('_', ' ', $value)) . '</label>' : '';
    }

    /**
     * [getInputMarkup ].
     *
     * @param [array]  $fieldName [name, id, class]
     * @param [string] $value     [text value]
     *
     * @return [string] [input html element]
     */
    private function getInputMarkup($fieldName, $value)
    {
        return "<div class='form-group'><div class='form-line'><input type='text' name='$fieldName' id='$fieldName' class='form-control $fieldName' value='$value' /></div></div>";
    }

    /**
     * [getTextareaMarkup description].
     *
     * @param [array]  $fieldName [name, id, class]
     * @param [string] $value     [text in textarea]
     *
     * @return [string] [textarea html element]
     */
    private function getTextareaMarkup($fieldName, $value)
    {
        return !empty($fieldName) ? "<div class='form-group'><div class='form-line'><textarea name='$fieldName' id='$fieldName' class='form-control $fieldName'>$value</textarea></div></div>" : '';
    }

    /**
     * [getBoolIinputMarkup description].
     *
     * @param [string] $type      [checkbox/radiobutton]
     * @param [array]  $fieldName [name/id/class]
     * @param [string] $label     [the label value]
     * @param [string] $checked   [true/false]
     *
     * @return [string] [bool input html element]
     */
    private function getBoolIinputMarkup($type, $fieldName, $label, $checked)
    {
        $checked = ($checked) ? 'checked' : '';
        $boolInput = ($type == 'radio') ? "<input type='$type' name='$fieldName' id='$fieldName' class='radio-col-purple form-control $fieldName' value='$fieldName' $checked /> $label" : "<input type='$type' name='$fieldName' id='$fieldName' class='chk-col-purple form-control $fieldName' $checked /> $label";

        return !empty($fieldName) ? $boolInput : '';
    }

    /**
     * [getUploadMarkup description].
     *
     * @param [array] $fieldName [name, id, class]
     * @param [array] $multiple  [true/false]
     *
     * @return [string] [file html element]
     */
    private function getUploadMarkup($fieldName, $multiple)
    {
        return "<input type='file' name='" . $fieldName . "[]' id='$fieldName' class='$fieldName' multiple='$multiple' />";
    }

    /**
     * [getExternalValues retrievs structure and values of another table].
     *
     * @param [array] $table [the table name]
     *
     * @return [object] [structure and values of the given table]
     */
    private function getExternalValues($table)
    {
        $structure = $this->model->getTableStructure($table);
        $result = $this->model->getTableValues($table);
        $this->data = $result;
        $obj = new stdClass();
        $obj->table = $this->data;
        $obj->structure = $structure;
        $obj->data = $this->data;

        return $obj;
    }

    /**
     * [getTableStructure get the structure of the given table].
     *
     * @return [array] [structure of a given table]
     */
    public function getTableStructure()
    {
        $structure = $this->model->getTableStructure($this->tableName);
        $result = array();
        for ($i = 0; $i < count($structure); ++$i) {
            $result[] = array('Field' => $structure[$i]['Field'], 'Type' => $structure[$i]['Type']);
        }

        return $result;
    }

    /**
     * [dropIgnoredInputs removes from the array the inputs that must not be processed].
     *
     * @return [null]
     */
    private function dropIgnoredInputs()
    {
        $ignoredInputs = $this->config['general']['ignoredInputs'][$this->tableName];
        if (isset($ignoredInputs)) {
            foreach ($ignoredInputs as $ignored) {
                for ($i = 0; $i < count($this->structure); ++$i) {
                    if (array_search($ignored, $this->structure[$i]) !== false) {
                        array_splice($this->structure, $i, 1);
                    }
                }
            }
        }
    }

    /**
     * [processHiddenInputs put hidden fields in processed array and
     * returns an array with the names and eventually the value of the hidden fields].
     *
     * @return [array] [hidden inputs]
     */
    private function processHiddenInputs()
    {
        $hiddenMarkup = '';
        $hiddenInputs = $this->config['general']['hiddenInputs'][$this->tableName];
        $hiddenFields = array();
        if (isset($hiddenInputs)) {
            foreach ($hiddenInputs as $hidden) {
                for ($i = 0; $i < count($this->structure); ++$i) {
                    $input = $this->structure[$i];
                    if (array_search($hidden, $this->structure[$i]) !== false) {
                        $hiddenFields = array($input['Field'] => isset($this->item[$input['Field']]) ? $this->item[$input['Field']] : '');
                        array_push($this->processed, $input['Field']);
                    }
                }
            }
        }
        if (count($hiddenFields) > 0) {
            foreach ($hiddenFields as $key => $values) {
                $hiddenMarkup .= "<input type='hidden' name='$key', value='' />";
            }
        }

        return $hiddenMarkup;
    }

    /**
     * Returns the markup for selects set in config['lists'] param.
     *
     * @param [string] $field
     *
     * @return string
     */
    private function buildLists($field)
    {
        $listMarkup = '';
        $list = $this->config['general']['lists'][$this->tableName];
        if (count($list) > 0) {
            if (isset($list[$field])) {
                $data = array();
                foreach ($list[$field] as $listItem) {
                    $data[str_replace(' ', '_', $listItem)] = $listItem;
                }
                $selected = isset($this->item[$field]) ? $this->item[$field] : '';
                $listMarkup .= $this->getLabelMarkup($field);
                $listMarkup .= $this->getSelectMarkup($field, $data, $selected);

                array_push($this->processed, $field);
            }
        }

        return !empty($field) ? $listMarkup : '';
    }

    /**
     * Returns the markup for selects built from external tables.
     *
     * @param [string] $field
     *
     * @return string
     */
    private function buildSelects($field)
    {
        $selects = $this->config['general']['selects'][$this->tableName];
        $selectsMarkup = '';
        if (count($selects) > 0) {
            if (in_array($field, $selects)) {
                $obj = $this->getExternalValues($field);
                foreach ($obj->data as $cd) {
                    $data[$cd['id']] = $cd['name'];
                }
                $selected = isset($this->item[$field]) ? $this->item[$field] : '';
                $selectsMarkup .= $this->getLabelMarkup($field);

                $selectsMarkup .= $this->getSelectMarkup($field, $data, $selected);

                array_push($this->processed, $field);
            }
        }

        return $selectsMarkup;
    }

    /**
     * Returns radiobutton markup.
     *
     * @param [string] $field
     *
     * @return string
     */
    private function buildRadios($field)
    {
        $radios = $this->config['general']['radios'][$this->tableName];
        $radiosMarkup = '';
        if (array_key_exists($field, $radios)) {
            $type = 'radio';
            $radiosMarkup .= $this->getLabelMarkup($field);
            foreach ($radios[$field] as $option) {
                $checked = (strtolower($this->item[$field]) == strtolower($option)) ? true : false;
                $label = $this->getLabelMarkup($option);
                $radiosMarkup .= $this->getBoolIinputMarkup($type, $field, $label, $checked);
            }
            array_push($this->processed, $field);
        }

        return $radiosMarkup;
    }

    private function buildFileWrapper($fileName, $field, $counter)
    {
        $parts = explode('.', $fileName);
        $ext = end($parts);
        $fileWrapperMarkup = '';
        $picExt = isset($this->config['general']['validExtension']['pictures']) ? $this->config['general']['validExtension']['pictures'] : array('jpg', 'png', 'gif');
        $docExt = isset($this->config['general']['validExtension']['documents']) ? $this->config['general']['validExtension']['documents'] : array('txt', 'doc', 'odf', 'pdf');
        if (in_array($ext, $picExt)) {
            $fileWrapperMarkup .= "<div class='img-wrapper'>";
            $fileWrapperMarkup .= "<img src='" . $this->siteUrl . $this->rootTargetDir . "pictures/$fileName' />";
            $fileWrapperMarkup .= "<div class='controls'>";
            $fileWrapperMarkup .= ($counter != 0) ? $this->getBoolIinputMarkup('radio', $field, 'Make default', false) : '<label>Default Image</label>';
            $fileWrapperMarkup .= "<p><a class='btn btn-danger btn-xs del-image' data-file='$fileName' name='deleteImage' data-id='$this->itemId' /> Delete image</a></p>";
            $fileWrapperMarkup .= '</div>';
            $fileWrapperMarkup .= '</div>';
        } elseif (in_array($ext, $docExt)) {
            $fileWrapperMarkup .= "<div class='icon-wrapper'>";
            $fileWrapperMarkup .= "<img src='" . $this->siteUrl . $this->rootTargetDir . "images/doc_icons/$ext.png' />";
            $fileWrapperMarkup .= "<div class='controls'>";
            $fileWrapperMarkup .= "<p>$fileName</p>";
            $fileWrapperMarkup .= "<p><a class='btn btn-danger btn-xs del-image' data-file='$fileName' name='deleteDoc' data-id='$this->itemId' /> Delete document</a></p>";
            $fileWrapperMarkup .= '</div>';
            $fileWrapperMarkup .= '</div>';
        }

        return $fileWrapperMarkup;
    }

    /**
     * Returns upload markup.
     *
     * @param [string] $field
     *
     * @return string
     */
    private function buildUploads($field)
    {
        $uploadsMarkup = '';
        if (count($this->config['general']['upload'][$this->tableName]) > 0) {
            foreach ($this->config['general']['upload'][$this->tableName] as $upload) {
                if ($field == $upload) {
                    $files = isset($this->item[$field]) ? $this->item[$field] : '';
                    if (!empty($files)) {
                        $fls = explode(';', $files);
                        $counter = 0;
                        foreach ($fls as $fileName) {
                            $uploadsMarkup .= $this->buildFileWrapper($fileName, $field, $counter);
                            ++$counter;
                        }
                    }
                    $uploadsMarkup .= '<div id="' . $field . '-uploader" class="uploader">';
                    $uploadsMarkup .= $this->getLabelMarkup($field);
                    $uploadsMarkup .= $this->getUploadMarkup($field, true);
                    $uploadsMarkup .= '<div id="' . $field . '-preview" class="previewer"></div>';

                    array_push($this->processed, $field);
                }
            }
        }
        return $uploadsMarkup;
    }

    private function buildVarchar($field)
    {
        $varcharMarkup = '';
        if (!in_array($field, $this->processed)) {
            $varcharMarkup .= $this->getLabelMarkup($field);
            $varcharMarkup .= $this->getInputMarkup($field, isset($this->item[$field]) ? $this->item[$field] : '');
        }

        return !empty($field) ? $varcharMarkup : '';
    }

    private function buildTextarea($field)
    {
        $textMarkup = '';
        if (!in_array($field, $this->processed)) {
            $textMarkup .= $this->getLabelMarkup($field);
            $textMarkup .= $this->getTextareaMarkup($field, isset($this->item[$field]) ? $this->item[$field] : '');
        }

        return !empty($field) ? $textMarkup : '';
    }

    private function buildCheckbox($field)
    {
        $checkboxMarkup = '';
        if (!in_array($field, $this->processed)) {
            $checked = (isset($this->item[$field]) && $this->item[$field] == '1') ? 'checked' : '';
            $label = $this->getLabelMarkup($field);
            $checkboxMarkup .= $this->getBoolIinputMarkup('checkbox', $field, $label, $checked);
        }

        return !empty($field) ? $checkboxMarkup : '';
    }

    private function buildIntInput($field)
    {
        $intMarkup = '';
        if (!in_array($field, $this->processed)) {
            $intMarkup = $this->getLabelMarkup($field);
            $intMarkup .= $this->getInputMarkup($field, isset($this->item[$field]) ? $this->item[$field] : '');
        }

        return !empty($field) ? $intMarkup : '';
    }

    private function buildDecimalInput($field)
    {
        $decimalMarkup = '';
        if (!in_array($field, $this->processed)) {
            $decimalMarkup = $this->getLabelMarkup($field);
            $decimalMarkup .= $this->getInputMarkup($field, isset($this->item[$field]) ? $this->item[$field] : '');
        }

        return !empty($field) ? $decimalMarkup : '';
    }

    /**
     * Returns input markup.
     *
     * @param [string] $field
     *
     * @return string
     */
    private function buildInputs($field, $type)
    {
        if (stristr($type, 'varchar') !== false) {
            $inputsMarkup = $this->buildVarchar($field);
        } else if (stristr($type, 'text') !== false) {
            $inputsMarkup = $this->buildTextarea($field);
        } else if (stristr($type, 'tinyint') !== false || stristr($type, 'bit') !== false) {
            $inputsMarkup = $this->buildCheckbox($field);
        } else if (stristr($type, 'int') !== false) {
            $inputsMarkup = $this->buildIntInput($field);
        } else if (stristr($type, 'decimal') !== false) {
            $inputsMarkup = $this->buildDecimalInput($field);
        }

        return $inputsMarkup;
    }

    /**
     * [buildForm description].
     *
     * @return [string] [the whole form]
     */
    public function buildForm()
    {
        $form = '';
        $this->dropIgnoredInputs();
        $form .= '<form action="save.php" id="save-form" enctype="multipart/form-data" method="post" accept-charset="utf-8">';
        //required to keep the table name dinamically assigned to the constructor
        $form .= "<input type='hidden' name='tableName', value='$this->tableName' />";
        $form .= $this->processHiddenInputs();
        $form .= ($this->editMode) ? "<input type='hidden' name='itemId', value='$this->itemId' />" : '';
        $action = ($this->editMode) ? 'Edit' : 'Add';
        $form .= <<<FRM
                    <div>
                        <h3>$action $this->tableName</h3>
                    </div>
FRM;

        for ($i = 0; $i < count($this->structure); ++$i) {
            $input = $this->structure[$i];
            $form .= !empty($this->buildLists($input['Field'])) ? "<fieldset>" . $this->buildLists($input['Field']) . "</fieldset>" : '';
            $form .= !empty($this->buildSelects($input['Field'])) ? "<fieldset>" . $this->buildSelects($input['Field']) . "</fieldset>" : '';
            $form .= !empty($this->buildRadios($input['Field'])) ? "<fieldset>" . $this->buildRadios($input['Field']) . "</fieldset>" : '';
            $form .= !empty($this->buildUploads($input['Field'])) ? "<fieldset>" . $this->buildUploads($input['Field']) . "</fieldset>" : '';
            if (!in_array($input['Field'], $this->processed)) {
                $form .= !empty($this->buildInputs($input['Field'], $input['Type'])) ? "<fieldset>" . $this->buildInputs($input['Field'], $input['Type']) . '</fieldset>' : '';
            }
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
     *                                  SAVE
     **********************************************************************************/

    private function getRoot()
    {
        $root = $this->root;
        if (!empty($this->rootTargetDir)) {
            $directories = explode('/', $this->rootTargetDir);
            if (count($directories) == 1) {
                if (!file_exists($root . $this->rootTargetDir) || !is_dir($root . $this->rootTargetDir)) {
                    mkdir($root . $this->rootTargetDir);
                }
                $root .= $this->rootTargetDir . DIRECTORY_SEPARATOR;
            } elseif (count($directories) > 1) {
                foreach ($directories as $d) {
                    if (!empty($d)) {
                        if (!file_exists($root . $d) || !is_dir($root . $d)) {
                            mkdir($root . $d);
                        }
                        $root .= $d . DIRECTORY_SEPARATOR;
                    }
                }
            }
        }

        return $root;
    }

    /**
     * Creates the directory tree with the destination folder for uploaded files.
     */
    private function buildDirTree()
    {
        $root = $this->getRoot();
        if (count($this->config['general']['upload'][$this->tableName]) > 0) {
            foreach ($this->config['general']['upload'][$this->tableName] as $dir) {
                if (isset($dir)) {
                    if (!file_exists($root . $dir) || !is_dir($root . $dir)) {
                        mkdir($root . $dir);
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
     * @param [string] $existingFiles [semicolon-separated string with files' names]
     * @param [string] $defaultFile   [the default file name]
     * @param [array]  $newFiles      [new uploaded files]
     *
     * @return [string] [semicolon-separated string with processed files' names]
     */
    public function createFilesString($existingFiles, $defaultFile = null, $newFiles = null)
    {
        $newFs = '';
        if (isset($newFiles)) {
            $oldFs = (!isset($existingFiles)) ? array() : explode(';', $existingFiles);
            if (count($newFiles) > 0 && count($oldFs) > 0) {
                $duplicates = array_intersect(explode(';', $newFiles), $oldFs);
                if (count($duplicates) > 0) {
                    for ($i = 0; $i < count($duplicates); ++$i) {
                        for ($x = 0; $x < count($oldFs); ++$x) {
                            if ($oldFs[$x] == $duplicates[$i]) {
                                array_splice($oldFs, $x, 1);
                            }
                        }
                    }
                }
            }
            $existingFiles = implode(';', $oldFs);
            $newFs = $newFiles;
        }
        if ($existingFiles !== '') {
            $existingFiles = rtrim($existingFiles, ';');
        }
        $newFilesString = !empty($existingFiles) ? $existingFiles . ';' . $newFs : $newFs;
        $finalFiles = ($newFs != '') ? $newFilesString : trim($existingFiles, ';');

        if (isset($defaultFile)) {
            $allFiles = explode(';', $finalFiles);
            if ($allFiles[0] !== $defaultFile) {
                for ($i = 0; $i < count($allFiles); ++$i) {
                    if ($allFiles[$i] === $defaultFile) {
                        $finalFiles = $allFiles[$i] . ';';
                        array_splice($allFiles, $i, 1);
                    }
                }
                $tempFiles = implode(';', $allFiles);
                $finalFiles .= rtrim($tempFiles, ';');
            }
        }

        return $finalFiles;
    }

    /**
     * actually stores uploaded files in the server.
     *
     * @param [string] $fileType [default file name]
     * @param [string] $default  [default file name]
     *
     * @return [array] [an array which holds an array of errors if
     *                 errors occurred otherwise the array files with
     *                 the semicolon-separated string]
     */
    public function processFiles($fileType, $default = null)
    {
        $result = array();
        $files = array();
        $fileUploaded = false;
        $filesReceived = true;
        $validExtensions = $this->config['general']['validExtensions'];
        for ($i = 0; $i < count($_FILES[$fileType]['name']); ++$i) {
            $maxFileSize = 1024 * 2000;
            $ext = explode('.', basename($_FILES[$fileType]['name'][$i]));
            $fileExtension = end($ext);
            $name = strtolower(filter_var($_FILES[$fileType]['name'][$i], FILTER_SANITIZE_STRING));
            if (($_FILES[$fileType]['size'][$i] <= $maxFileSize) && in_array($fileExtension, $validExtensions[$fileType])) {
                if (move_uploaded_file($_FILES[$fileType]['tmp_name'][$i], $this->root . $this->rootTargetDir . $fileType . DIRECTORY_SEPARATOR . $name)) {
                    if ($fileType == 'pictures') {
                        $img = imagecreatefromstring(file_get_contents($this->root . $this->rootTargetDir . $fileType . DIRECTORY_SEPARATOR . $name));
                        $img !== false ? $files[] = strtolower($name) : unlink($this->root . $this->rootTargetDir . $fileType . DIRECTORY_SEPARATOR . $name);
                        $img !== false ? imagedestroy($img) : $result['errors'][] = 'The uploaded file is not a valid image file.';
                    }
                    if ($fileType == 'documents') {
                        $files[] = strtolower($name);
                    }
                    $fileUploaded = true;
                }
                if (!$fileUploaded) {
                    $result['errors'][] = 'Error moving file to the destination directory.';
                }
                $filesReceived = true;
            }
            if (!$filesReceived) {
                $result['errors'][] = 'Invalid file size';
            }
        }
        $fileCountPositive = false;
        if (count($files) > 0) {
            $newFiles = implode(';', $files);
            $files = rtrim($newFiles, ';');
            if ($this->editMode) {
                $existingFiles = $this->model->getFiles($fileType, $this->itemId);
                if ($existingFiles !== false) {
                    $files = $this->createFilesString($existingFiles, $default, $newFiles);
                }
            } elseif ($default != null) {
                $files = $this->createFilesString($files, $default, $newFiles);
            }
            $fileCountPositive = true;
        }
        if (!$fileCountPositive) {
            if ($this->editMode) {
                $existingFiles = $this->model->getFiles($fileType, $this->itemId);
                if ($existingFiles !== false) {
                    $files = $this->createFilesString($existingFiles, $default, $newFiles);
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
        $structure = $this->model->getTableStructure($this->tableName);
        for ($i = 0; $i < count($structure); ++$i) {
            $input = $structure[$i];
            $goodPost = filter_input_array(INPUT_POST);
            foreach ($goodPost as $k => $v) {
                if ($k == $input['Field']) {
                    $post[$k] = $v;
                }
            }
        }
        array_walk($post, function (&$value, $key) {
            if ($value == 'on') {
                $value = '1';
            }
        });
        $files = array();
        if (isset($this->config['general']['upload'][$this->tableName])) {
            foreach ($this->config['general']['upload'][$this->tableName] as $ft) {
                if (!empty($_FILES["$ft"]['name'][0])) {
                    $default = null;
                    if (isset($post['default'])) {
                        $default = $post['default'];
                        $key = array_search('default', array_keys($post));
                        if ($key !== false) {
                            array_splice($post, $key, 1);
                        }
                    }
                    $result = $this->processFiles($ft, $default);
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
        return $this->model->insert($post, $this->itemId);
    }

    /**********************************************************************************
     *                                  DELETE
     **********************************************************************************/

    /**
     * Delete single picture.
     *
     * @param [string] $imageName
     * @param [string] $itemId
     *
     * @return bool
     */
    public function deletePicture($imageName, $itemId)
    {
        if (empty($imageName) || empty($itemId)) {
            return;
        }
        $pictures = $this->model->getFiles('pictures', $itemId);
        $pics = explode(';', $pictures);
        $key = array_search($imageName, $pics);
        array_splice($pics, $key, 1);
        $pictures = implode(';', $pics);
        $this->model->deletePicture($pictures, $itemId);
        if (file_exists($this->root . $this->rootTargetDir . 'pictures/' . $imageName)) {
            unlink($this->root . $this->rootTargetDir . 'pictures/' . $imageName);
        }
    }

    /**
     * Delete a document. Differently to the above function the icon is not removed.
     *
     * @param [type] $imageName
     * @param [type] $itemId
     */
    public function deleteDocument($docName, $itemId)
    {
        if (empty($docName) || empty($itemId)) {
            return;
        }
        $documents = $this->model->getFfiles('documents', $itemId);
        $docs = explode(';', $documents);
        $key = array_search($docName, $docs);
        array_splice($docs, $key, 1);
        $documents = implode(';', $docs);

        $this->model->deleteDocument($documents, $itemId);
        if (file_exists($this->root . $this->rootTargetDir . 'documents/' . $docName)) {
            unlink($this->root . $this->rootTargetDir . 'documents/' . $docName);
        }
    }
}
