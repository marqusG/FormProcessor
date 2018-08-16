# FormProcessor
4 classes which work together to perform CRUD ops easily and quickly

**FormProcessor** is a bunch of php classes which work together to perform CRUD
operations. More precisely, they allow the user to add, edit and delete records
to/from a specific table.

#Update 16 august 2018
- fixed a bug which prevent to create multiple selects from external tables
- removed table_name from the config file: now you must pass table_name to the 
  constructor: this makes the class more flexible.


## General description

### FormBuilder class

**FormBuilder** is the starting point of the project: I needed something that easily
could create a form reading the table structure from a mysql database. Of course,
the form created this way is really simple, so I have added some configuration
option to allow a bit more flexibility. The result is far to be perfect but it
often fits my needs and hope it could help you too.

### FormSaver class

**FormSaver** gets the data posted by the form created by FormBuilder, processes
them (to store eventually uploaded files in to the server) and save data in the
database table.

### FormTable class

**FormTable** builds a html table to show data stored in the mysql table. The
resulting table allow to sort records in ascending or descending order clicking
on each column name; in addition, each record has 2 action buttons, 'Edit' end
'Delete'. To edit a record FormBuilder is called again passing the record id.

### FormModel class

**FormModel** is used by each of other classes when they need to perform some
database operation.

## Usage
To illustrate the use of these classes I have included a full working example.
To see it in action you have to use the 2 sql files provided in the folder 'db'
and create the 2 table 'products' and 'category' in your local or remote server.
Then you have to upload to your server the 2 folders 'lib' and 'example' and
finally just set the values required for the database connection in the
config.php file. Now you're ready to go to <yourserver>/manage.php.

If you want to see an example of the code required to create a form, here you go:
### Add a new record
```php
require_once "../lib/FormBuilder.class.php";
$fb = new FormBuilder();
echo $fb->build_form();
```

### Save a new record
```php
require_once "../lib/FormSaver.class.php";
$fs = new FormSaver();
echo $fs->save();
```

### Edit an existing record
```php
require_once "../lib/FormBuilder.class.php";
$fb = new FormBuilder(123);//the id value for the record to edit
echo $fb->build_form();
```

### Save an updated record
```php
require_once "../lib/FormSaver.class.php";
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fs = new FormSaver($item_id); //you can use this code even to save a new record: if $item_id is null there is no problem
echo $fs->save();
```

### Save an updated record specifying dinamically a subdirectory where store uploaded files
```php
require_once "../lib/FormSaver.class.php";
$root_target_dir = $user_first_name . DIRECTORY_SEPARATOR . 'personal_files';
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_SANITIZE_NUMBER_INT);
$fs = new FormSaver(root_target_dir, $item_id); //files will be saved in their specific subdirectory in www.example.com/John/personal_files/
echo $fs->save();
```

## Weakness and TODO
I'm conscious that, even if it works fine enough, this code can be greatly improved.
The security is the first weakness I can think about, but there is a lot of stuff
to do. Here I report a short list of aspects I will work on in the next future:
* using prepared statement (maybe switch to PDO?)
* add pagination in FormTable
* implement inheritance and build a class hierarchy (I'm thinking to a
FormProcessor class as parent of all other classes)
* multiplying methods to make them more specialized
* replacing array with objects where it is possible
* convert config.php in a ne class
* add more options and customization opportunities
* separate html markup and logic (not sure: after all I don't want to reinvent Wordpress)
* any idea?

## Conclusions
After all, these four small classes, working together operate like a supersimple,
elementary CMS. Actually, this is what I needed to create for my clients, small
businessmen who are too busy with their real work to spend too time learning how
to use Wordpress or Joomla. And for sure, I'm not so arrogant to think I can build
a serious competitor starting from here :D But I think I can improve this humble
piece of code to make it more standardized, more secure, more flexible and finally
more useful for all those developers who needs some quick and easy way to manage
a database, maybe only for testing purposes or for small projects.

That's all folks. Hope you can find it useful and don't hexitate to contact me
for criticism and suggestions... okay, even for congrats, if you really think
I deserve them :)

You can find me at my blog codingfix.com, on Twitter, Facebook and Instagram.
