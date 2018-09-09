<?php
/**
 *     In config files you MUST set following options.
 *
 * - database host
 * - database username
 * - database password
 * - database name
 * - table name
 *
 *  All items in 'general' section are optional. Some of them are related to the creation of specific html tags:
 *
 *     - RADIO BUTONS: you have to set an array with column names as keys and an array which holds the possible values:
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
 *
 * The rest of optional items are quiate simple and are briefly explained in the inline comments below.
 *
 * @var array
 */
$config = [
    'database' => [
        'dbHost' => 'localhost',
        'dbUsername' => '',
        'dbPassword' => '',
        'dbName' => '',
    ],
    'general' => [
        'upload' => [
            'products' => [
                'documents',
                'pictures',
            ],
        ],
        'selects' => [
            'products' => [
                'category',
            ],
        ],
        'lists' => [
            'products' => [
                'color' => [
                    'green',
                    'blue',
                    'orange',
                ],
            ],
        ],
        'radios' => [
            'products' => [
                'size' => [
                    'small',
                    'medium',
                    'large',
                ],
            ],
        ],
        'ignoredInputs' => [
            'products' => [
                'id',
            ],
        ], //inputs you don't want to appear in the form
        'hiddenInputs' => [
            'products' => [],
        ], //inputs you want in the form but that you want to keep hidden
        'siteUrl' => '', //you can set here the url of your site to diplay a preview of the files that are in uploading queue or to display the files already uploaded
        'rootTargetDir' => '', //FormPrpcessor will create directories to hold uploaded files using the columns names provided in the array 'upload'; in this example it will create the directories 'pictures' and 'documents'; these directories will be create by default in your document root (ie www.example.com/pictures). If you set rootTargetDir, its value will be used to creates files directories within the path set here (ie www.example.com/FormBuilder/pictures); you can pass this value dinamically when you instanciate the object of class FormSaver. This can be useful if you want to save files in a user specific subdirectoriy, for instance.
        'validExtensions' => [
            'pictures' => [
                'jpg',
                'png',
                'gif',
            ],
            'documents' => [
                'pdf',
                'doc',
                'txt',
                'odt',
            ],
        ], //self-explanatory item, isn't it?
    ],
];

return $config;
