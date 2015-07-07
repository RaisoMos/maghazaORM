# maghazaORM
Simple ORM is an object-relational mapper for PHP using PDO. It provides a simple way to create, retrieve, update & delete records.
<h2>Configuration</h2>
to use this class you need to:
1. require config.php and DB.class.php
2. change configuration on config.php (username,database...)
3. extend the model to your class

<h2>Basic usage</h2>
Create your class:
```
class User extends Model{ <br>
  public static $__tableName__ = 'users';
  public 
	  $username ='',
      $email = '',
	  $password ='';
	  
  public function __construct(){
    parent::__construct();
  }
}
```
Retrieve a record by it's id:
```
$user = User::find(1);
```
Retrieve a record by field name:
``` 
$user = User::findby('name','test'); 
```
Retrieve all users:
```
$users = User::all();
```
Creating a record:
```
$user = new User();
$user->set('username', 'RaisoMos');
$user->set('password', '1234');
$user->save();
```
Updating a record:
```
$user = User::find(1);
$user->set('username', 'test');
$user->save();
```
Delating a record:
```
$user = User::find(1);
$user->delete();
```
