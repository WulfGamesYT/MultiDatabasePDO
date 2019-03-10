[![MySQL & PHP](https://codegeekz.com/wp-content/uploads/php-mysql-logo-large.gif)](https://github.com/WulfGamesYT/MultiDatabasePDO)
<br><br>
[![Current Version](https://dabuttonfactory.com/button.png?f=Open+Sans&ts=16&tc=666&hp=24&vp=12&c=round&bgt=unicolored&bgc=eee&bs=1&bc=ccc&t=Latest+Version%3A+v1.0.3)](https://github.com/WulfGamesYT/MultiDatabasePDO/raw/master/MultiDatabasePDO.php)

# MultiDatabasePDO
This is a **free**, **easy to use**, **lightweight** and **powerful** PHP library that allows you to connect to multiple MySQL databases through PDO. I've always wondered why MySQL doesn't have built in horizontal scaling thats simple for everyone. I've come up with a solution, just have multiple databases with the same table names and columns, and this library will allow you to scale, have as many databases as you want! Before you start please make sure you understand [the basics of PDO](https://secure.php.net/manual/en/book.pdo.php).

**NOTE:** I've built this specifically for MySQL but I believe this will work with PostgreSQL, MariaDB, CouchDB etc. Remember, this is for SQL databases so it won't work with database management systems like MongoDB and Apache Cassandra.

## Features
✔ Connect to multiple MySQL databases using PDO, without having performance issues!<br>
✔ Retrieve rows from multiple databases from tables named the same.<br>
✔ Perform insert queries efficiently by only doing 1 query instead of adding into all databases/tables.<br>
✔ Free to use, and it's really easy too, which is great!<br>
✔ Easily sort, limit and manage results/rows.<br>
✔ Never have to worry about scaling, just add more MySQL databases and you'll be fine!

## Requirements
&#10132; PHP 7+ & Apache/Nginx (uses features for PHP 7 and above).<br>
&#10132; MySQL 5.7+ (to clarify MySQL 5.7 works fine, so any version higher than MySQL 5.7 would be great).<br>
&#10132; A PDO-compatible database driver ([read more about this here](https://secure.php.net/manual/en/ref.pdo-mysql.php)).

## Licence
**You may use MultiDatabasePDO for personal, educational and commercial use under the following terms:**<br>
&#10132; You don't sell, give or host (original or edited copies) of this library to other users, you must link them to this repository.<br>
&#10132; You don't change the comment in the file or remove it, doing so will make me think you want to claim it as your own.

## Getting Started
Simply download the latest version of [MultiDatabasePDO](https://github.com/WulfGamesYT/MultiDatabasePDO/raw/master/MultiDatabasePDO.php) and include it in your autoload php file (or header inc file on every page). Once done, you can connect to all your databases by doing:
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

Next, I would recommend [reading the documentation on the wiki](https://github.com/WulfGamesYT/MultiDatabasePDO/wiki) to understand what each function does. Also, it's important to know that there are some differences between this library and the standard PDO library, notably:
* You can't pass in an array of placeholders/values in the `execute()` method, use `bindValue()` for each placeholder.
* You can't bind PHP variables directly, use `bindValue()` method for assigning values to each placeholder.
* You can't use `ORDER BY`, `LIMIT` or `OFFSET` in your SQL queries, instead please [see this guide](#organising-results).
* Avoid using `AUTO INCREMENT` for columns, instead if you have an ID column [make use of this function here](#random-id-generator).

## The Example Tables
For example purposes, imagine we have the following tables, both called "Users". Each example in this README below will be using these tables and their values/columns. Note that you have to use the same columns for every table in ALL your databases.<br>

**"Users" table, from database 1.**<br>

| ID (int)      | Username (text)     | PassHash (text)     | Email (text)         | FirstName (text) | LastName (text) |
| ------------- | ------------------- | ------------------- | -------------------- | ---------------- | --------------- |
| 1             | WulfGamesYT         | ThLfkbQFyvDx        | wulf@example.com     | Liam             | Allen           |
| 2             | IndianaJones55      | npxCn975RSaP        | im@indiana.jones     | Indiana          | Jones           |
| 3             | YaBoiTableFlipper69 | BT7V2U6VJv2d        | yaboi@gmail.com      | Steve            | Jones           |

**"Users" table, from database 2.**<br>

| ID (int)      | Username (text)     | PassHash (text)     | Email (text)         | FirstName (text) | LastName (text) |
| ------------- | ------------------- | ------------------- | -------------------- | ---------------- | --------------- |
| 4             | ReallyDude          | 6XBmD4bzGP87        | reallydude@yahoo.com | Liam             | Mason           |
| 5             | HellYeaBoi          | LeyTpTwvvMUM        | hellyea@gmail.com    | Julie            | Crosby          |

## Example Query #1: SELECT
To select rows from ALL databases and ALL tables, you can simply do, like normal PDO in PHP:
```php
$selectQuery = $multiPDO->prepare("SELECT ID, Username, Email FROM Users WHERE Username = :username");
$selectQuery->bindValue(":username", "WulfGamesYT");
$selectQuery->execute();
while($row = $selectQuery->getNextRow()) { var_dump($row); }
```

That will produce some example output like:
```
array(3) {
  ["ID"]=>
  int(1)
  ["Username"]=>
  string(11) "WulfGamesYT"
  ["Email"]=>
  string(16) "wulf@example.com"
}
```

## Example Query #2: INSERT
Say if we had a form and you can POST the info to your PHP file, and you want to insert 1 new record into a table from a database called "Users", all you need to do is the following. Note that this will be inserted into the second table in the example tables above because it has the lowest row count. Please [read this](#random-id-generator) on how to generate a random string for the "ID" column instead of using `AUTO INCREMENT`.
```php
$longSQL = "INSERT INTO Users VALUES (6, :username, :pass, :email, :firstname, :lastname)";
$insertQuery = $multiPDO->prepare($longSQL);
$insertQuery->bindValues([
    ":username" => $_POST["username"],
    ":pass" => password_hash($_POST["password"], PASSWORD_DEFAULT),
    ":email" => $_POST["email"],
    ":firstname" => $_POST["name-first"],
    ":lastname" => $_POST["name-last"]
]);
$insertQuery->execute(true, "Users");
```

Notice that with the `execute()` method we pased in 2 parameters, this is required for inserting new rows, because it tells the class we're inserting (by passing in: true) a new row into a table called "Users". Don't put untrusted user input as the second parameter as SQL Injection can occur.

## Example Query #3: UPDATE
This is basically the same as doing a SELECT query, this will update ALL tables in ALL databases that match the WHERE clause if specified, for example:
```php
$updateQuery = $multiPDO->prepare("UPDATE Users SET Username = :newusername WHERE Username = :oldusername");
$updateQuery->bindValues([":newusername" => "MyFancyUsername", ":oldusername" => "WulfGamesYT"]);
$updateQuery->execute();
```

Now if we ran a SELECT query on ALL the tables named "Users" we will see the updated row.

## Example Query #4: DELETE
Again, all we need to do is:
```php
$deleteQuery = $multiPDO->prepare("DELETE FROM Users WHERE Username = :username");
$deleteQuery->bindValue(":username", "MyFancyUsername");
$deleteQuery->execute();
```

Now if we ran a SELECT query on ALL the tables named "Users" we will see the updated row.

## Organising Results
It's important to note you can't use `ORDER BY`, `LIMIT` or `OFFSET` in your SQL queries. Instead you have to use the following functions that are available, which make it easy to organise your final results/rows.

**Ordering Results (instead of "ORDER BY"):**
You can order your results just like you can in SQL queries with "ASC" or "DESC" passed into the second parameter to the `sortBy()` method.

This is how you order number columns:
```php
$selectQuery = $multiPDO->prepare("SELECT * FROM Users");
$selectQuery->execute();

//Now sort by the "ID" column in descending order.
$selectQuery->sortBy("ID", "DESC");

while($row = $selectQuery->getNextRow()) { var_dump($row); }
```

This is how you order string/object columns:
```php
$selectQuery = $multiPDO->prepare("SELECT * FROM Users");
$selectQuery->execute();

//Now sort by the "Username" column in ascending order.
$selectQuery->sortBy("Username", "ASC");

while($row = $selectQuery->getNextRow()) { var_dump($row); }
```

You can order multiple columns, or multiple times if you want. In the example below we will be ordering a column called "FirstName" in descending order, then a column called "LastName". This will list users in the table in alphabetical order, if they have the same first name then it will also order by the last name. Put the least important order column first, then the most important at the end as you can see in the code:
```php
$selectQuery = $multiPDO->prepare("SELECT * FROM Users");
$selectQuery->execute();

//Now sort both the columns.
$selectQuery->sortBy("LastName", "ASC");
$selectQuery->sortBy("FirstName", "ASC");

while($row = $selectQuery->getNextRow()) { var_dump($row); }
```

## Random ID Generator
Instead of `AUTO INCREMENT`, or if you need a way of generating unique strings in your tables for a column, you can make use of a function called `generateRandomID()`. Here is an example of how to use it when inserting new rows into your tables:
```php
//Here we generate a truly random string for the "ID" column in the "Users" table.
//Optionally we can pass in a length for the random string as the 3rd parameter, default length is 48.
$randomID = $multiPDO->generateRandomID("ID", "Users");

$longSQL = "INSERT INTO Users VALUES (:id, :username, :pass, :email, :firstname, :lastname)";
$insertQuery = $multiPDO->prepare($longSQL);
$insertQuery->bindValues([
    ":id" => $randomID,
    ":username" => $_POST["username"],
    ":pass" => password_hash($_POST["password"], PASSWORD_DEFAULT),
    ":email" => $_POST["email"],
    ":firstname" => $_POST["name-first"],
    ":lastname" => $_POST["name-last"]
]);
$insertQuery->execute(true, "Users");
```

The way the `generateRandomID()` function works is that it will:
1. Generate a random string of desired length.
2. Perform a SELECT query on all tables to see if the random string is there in the specified column.
3. If any rows exist with the value of the random string in the specified column go back to step 1, else continue.
4. Return the random string.

## Have Questions? Like It?
If you need to ask a question, reach out to me on Twitter.<br>
Twitter: https://www.twitter.com/WulfGamesYT

If you like this library please consider starring it and sharing it with fellow developers who like PHP & MySQL! Stay tuned for updates and be sure to report any bugs you find to me. Thank you for reading!
