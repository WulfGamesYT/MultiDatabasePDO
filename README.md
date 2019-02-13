![MySQL & PHP](https://codegeekz.com/wp-content/uploads/php-mysql-logo-large.gif)

# MultiDatabasePDO
This is a **free**, **easy to use**, **lightweight** and **powerful** PHP library that allows you to connect to multiple MySQL databases through PDO. I've always wondered why MySQL doesn't have built in horizontal scaling thats simple for everyone. I've come up with a solution, just have multiple databases with the same table names and columns, and this library will allow you to scale, have as many databases as you want! Before you start please make sure you understand [the basics of PDO](https://secure.php.net/manual/en/book.pdo.php).

## Features
✔ Connect to multiple MySQL databases using PDO, without having performance issues!<br>
✔ Get rows from multiple databases from the same named table.<br>
✔ Perform insert queries efficiently by only doing 1 query instead of adding into all databases/tables.<br>
✔ Free to use, and it's really easy too, which is great!

## Getting Started
Simply include the file `MultiDatabasePDO.php` in your autoload PHP class or include header file on all pages.
Then you can connect to all your databases easily by doing:
```php
$multiPDO = new MultiDatabasePDO([
    ["mysql", "1.1.1.1", "database_1", "username", "password"],
    ["mysql", "2.2.2.2", "database_2", "username", "password"]
]);
```

Now we need to check for any errors using a simple function called `hasAnyErrors()`! You can get the failed connections by calling the function `getFailedConnections()`.
```php
if($multiPDO->hasAnyErrors()) {
    error_log("Error connecting to database(s): " . $multiPDO->getFailedConnections());
    exit("Error connecting to our main databases! Please try again later.");
}
```

## Example Query #1: SELECT
To select rows from ALL databases and ALL tables, you can simply do, like normal PDO in PHP:
```php
$statement = $multiPDO->prepare("SELECT * FROM Users WHERE Username = :username LIMIT 1");
$statement->bindValue(":username", "WulfGamesYT");
$statement->execute();
while($row = $statement->getNextRow()) { var_dump($row); }
```

That will produce some example output like:
```
array(3) {
  ["Username"]=>
  string(11) "WulfGamesYT"
  ["PasswordHash"]=>
  string(21) "haha123"
  ["Email"]=>
  string(20) "you@dontknow.com"
}
```

## Example Query #2: INSERT
Say if we had a form and you can POST the info to your PHP file, and you want to insert 1 new record into a tabled named "Users", all you need to do is the following:
```php
$insertQuery = $multiPDO->prepare("INSERT INTO Users VALUES (:username, :passwd, :email)");
$insertQuery->bindValue(":username", $_POST["username"]);
$insertQuery->bindValue(":passwd", password_hash($_POST["password"], PASSWORD_DEFAULT));
$insertQuery->bindValue(":email", $_POST["email"]);
$insertQuery->execute(true, "Users");
```

Notice that with the `execute()` method we pased in 2 parameters, this is required for inserting new rows, because it tells the class we're inserting, so we only insert a new row into the table with the lowest row count from all your databases, and secondly the name of the table (don't put untrusted user input here as SQL Injection can occur). Now check all your databases and you'll notice that the one with the lowest row count in the table "Users" has the new row in.

## Example Query #3: UPDATE
This is basically the same as doing a SELECT query, this will update ALL tables in ALL databases that match the WHERE clause if specified, for example:
```php
$statement = $multiPDO->prepare("UPDATE Users SET Username = :newusername WHERE Username = :oldusername");
$statement->bindValue(":newusername", "MyFancyUsername");
$statement->bindValue(":oldusername", "WulfGamesYT");
$statement->execute();
```
Now if we ran a SELECT query on ALL the tables named "Users" we will see the updated row.

## Known Issues & Bugs
**Currently, there are some issues that plan on being fixed in some way:**<br>
* The LIMIT keyboard doesn't work well, for example doing UPDATE and LIMIT 1 will actually update all table rows only once in each, but doing the query in multiple tables, therefore not limiting to 1 but the amount of databases.
