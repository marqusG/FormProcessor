# FormProcessor
A class to perform CRUD ops easily and quickly

**FormProcessor** is a php class which allows to perform CRUD
operations. More precisely, it allows the user to add, edit and delete records
to/from a specific table.

#Update 20 august 2018
- removed FormProcessor and FormProcessor classes, merged into the FormProcessor one.
- fixed bug deleting pictures or documents from an existing record
- fixed a bug adding pictures or documents to an existing record
- added code to delete all file associated with a given record when this record is deleted

#Update 16 august 2018
- fixed a bug which prevent to create multiple selects from external tables
- removed table_name from the config file: now you must pass table_name to the 
  constructor: this makes the class more flexible.


## General description

### FormProcessor class

**FormProcessor** has been originated  by the need to easily create a form
reading the table structure from a mysql database. Currently, only a little 
number of MySql field types are supported:
- varchar
- text
- int
- decimal
- tinyint
- bit
I also added some configuration options int a config file which allows to manage
checkboxes, radiobuttons and selects (these ones in 2 different ways).

**FormProcessor** gives you the ability to save the form data and upload the supported 
files types with few lines of code. 

### FormTable class

**FormTable** builds a simple html table to show data stored in the mysql table. 
The resulting table allows to sort records in ascending or descending order clicking
on each column name; in addition, each record has 2 action buttons, 'Edit' end
'Delete'. This class is included only to make it easy to show how FromProcessor works
and it is not really part of the package.

### FormModel class

**FormModel** is used to perform database operations.

## Usage
To illustrate the use of these classes I have included a full working example.
To see it in action you have to use the 2 sql files provided in the folder 'db'
and create the 2 table 'products' and 'category' in your local or remote server.
Then you have to upload to your server the 2 folders 'lib' and 'example' and
finally just set the values required for the database connection in the
config.php file. Now you're ready to go to <yourserver>/manage.php.

I plan to write a more detailed description in the next weeks and even to
create a mini website, even if I prefer to work to improve the class itself
(and there is a lot of work to do to improve at least security and flexibility).

If you want to see an example of the code required to create a form, here you go:
### Add a new record
```php
require_once "../lib/FormProcessor.class.php";
$fb = new FormProcessor();
echo $fb->build_form();
```

### Save a new record
```php
require_once "../lib/FormProcessor.class.php";
$fs = new FormProcessor();
echo $fs->save();
```

### Edit an existing record
```php
require_once "../lib/FormProcessor.class.php";
$fb = new FormProcessor(123);//the id value for the record to edit
echo $fb->build_form();
```

### Save an updated record
```php
require_once "../lib/FormProcessor.class.php";
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fs = new FormProcessor($item_id); //you can use this code even to save a new record: if $item_id is null there is no problem
echo $fs->save();
```

### Save an updated record specifying dinamically a subdirectory where store uploaded files
```php
require_once "../lib/FormProcessor.class.php";
$root_target_dir = $user_first_name . DIRECTORY_SEPARATOR . 'personal_files';
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fs = new FormProcessor(root_target_dir, $item_id); //files will be saved in their specific subdirectory in www.example.com/John/personal_files/
echo $fs->save();
```

## Conclusions

That's all folks. Hope you can find it useful and don't hexitate to contact me
for criticism and suggestions... okay, even for congrats, if you really think
I deserve them :)

You can find me at my blog codingfix.com, on Twitter, Facebook and Instagram.
