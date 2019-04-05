[![MySQL & PHP](https://codegeekz.com/wp-content/uploads/php-mysql-logo-large.gif)](https://github.com/WulfGamesYT/MultiDatabasePDO)
<br><br>
[![Current Version](https://dabuttonfactory.com/button.png?f=Open+Sans&ts=16&tc=666&hp=24&vp=12&c=round&bgt=unicolored&bgc=eee&bs=1&bc=ccc&t=Download+Latest+Version)](https://github.com/WulfGamesYT/MultiDatabasePDO/releases)

# MultiDatabasePDO
This is a **free**, **easy to use**, **lightweight** and **powerful** PHP library that allows you to connect to multiple MySQL databases with PDO. I've built this specifically for MySQL but I believe this will work with PostgreSQL, MariaDB, CouchDB etc. Remember, this is for SQL databases so it won't work with database management systems like MongoDB and Apache Cassandra.

## Features
✔ Connect to multiple MySQL databases using PDO.<br>
✔ Retrieve rows from multiple databases.<br>
✔ Perform insert queries efficiently by doing only 1 query instead of adding new rows in all tables in every database.<br>
✔ Easily sort, limit and manage results/rows.<br>
✔ Scale easily, simply by adding more databases (no need to use slaves, masters or clusters).<br>
✔ Generate truly unique identifiers (called MDGUID's).

## Coming Soon
In MultiDatabasePDO v1.0.9 there's a few cool things planned:<br>
&#10132; Efficient pagination system (fetch and sort rows from multiple databases into pages).<br>
&#10132; Sort by multiple columns instead of just 1. (Currently there's a bug with `sortBy()`).<br>
&#10132; Have specific tables in the first database only, instead of having multiple un-used tables in each database.<br>

Some changes you'll need to prepare for:<br>
&#10132; The MDGUID queue table has been renamed to `$_MultiDatabasePDO_MDGUIDQueueSystem`.<br>
&#10132; You must now provide the table name in the `execute()` method e.g. `execute("Users")`.<br>
&#10132; With the `execute()` method, the insert mode boolean is the 2nd param, which is optional.<br>
&#10132; MultiDatabasePDO will only allow 1 SQL query per multi statement, so if you have > 1 query it will throw an error.

## Requirements
&#10132; PHP 7+ & Apache/Nginx (uses features for PHP 7 and above).<br>
&#10132; MySQL 5.7+ (to clarify MySQL 5.7 works fine, so any version higher than MySQL 5.7 would be great).<br>
&#10132; A PDO-compatible database driver ([read more about this here](https://secure.php.net/manual/en/ref.pdo-mysql.php)).

## Licence
**You may use MultiDatabasePDO for personal, educational and commercial use under the following terms:**<br>
&#10132; You don't sell, give or host (original or edited copies) of this library to other users, you must link them to this repository.<br>
&#10132; You don't change the comment in the file or remove it, doing so will make me think you want to claim it as your own.

## Getting Started

**1. CONNECTING TO YOUR DATABASES!**<br>
Before you start please make sure you understand [the basics of PDO](https://secure.php.net/manual/en/book.pdo.php). Simply download the latest release and include the file named `MultiDatabasePDO.php` which will automatically include all the extra classes for you. Your setup code should look like:
```php
require "./MultiDatabasePDO/MultiDatabasePDO.php";
$multiPDO = new \WulfGamesYT\MultiDatabasePDO\MultiDatabasePDO([
    ["mysql", "1.1.1.1", "database_1", "username", "password"],
    ["mysql", "2.2.2.2", "database_2", "username", "password"]
]);
```

Now we need to check for any errors using a simple function called `hasAnyErrors()`. You can list connections which fail with the function `getFailedConnections()`.
```php
if($multiPDO->hasAnyErrors()) {
    error_log("Error connecting to database(s): " . $multiPDO->getFailedConnections());
    exit("Error connecting to our main databases! Please try again later.");
}
```

**2. READING THE WIKI & USE THIS LIBRARY THE RIGHT WAY!**<br>
Next, I would recommend [reading the documentation on the wiki](https://github.com/WulfGamesYT/MultiDatabasePDO/wiki) to understand what each function does. Also, it's important to know that there are some differences between this library and the standard PDO library, notably:
* You can't pass in an array of placeholders/values in the `execute()` method, use `bindValue()` or `bindValues()`.
* You can't use `ORDER BY`, `LIMIT` or `OFFSET` in your SQL queries, instead please [see this guide](#organising-results).
* Avoid using `AUTO INCREMENT` for columns, instead if you have an ID column [make use of this function](#mdguid-generator).

**3. SETTING UP YOUR DATABASES AND TABLES!**<br>
If you plan on using MultiDatabasePDO, you must ensure all your tables from every database you connect to is structured the same way:
* Each column has to be named the same in every table.
* Each database has to have the same tables in (named exactly the same as each other).
* Each column from every table has to have matching data types.

## The Example Tables
For example purposes, imagine we have the following 2 tables from 2 different databases, both structured and named the same. Each example in this README below ueses these tables. Realistically your tables would have thousands, if not millions of rows before you'd need to consider using MultiDatabasePDO (or if you ever want to prepare for scaling your web app).

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

## Example Query #2: INSERT
Say if we had a form and you can POST the info to your PHP file, and you want to insert 1 new record into a table from a database called "Users", all you need to do is the following. Note that this will be inserted into the second table in the example tables above because it has the lowest row count. Instead of putting in a manual ID and using the int data type in your tables use the `generateMDGUID()` function below.
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
It's important to note you can't use `ORDER BY`, `LIMIT` or `OFFSET` in your SQL queries to order all the rows from each database, only the rows in that current table in 1 database. Instead you have to use the following functions that are available with MultiDatabasePDO which make it easy to organise your final results/rows.

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

## MDGUID Generator
Instead of `AUTO INCREMENT`, or if you need a way of generating truly unique GUID's across multiple databases you can make use of our function called `generateMDGUID()`. Below is a guide on how they work and how they guarentee 100% uniqueness, and an example of how to use the function when inserting new rows into your tables.

**How MDGUID's work and guarentee uniqueness:**
1. MultiDatabasePDO generates a UUID v4, prefixed by an MD5 of the current timestamp, forming an MDGUID.
2. MultiDatabasePDO then inserts the MDGUID into a MDGUID queue table in the first database you have connected.
3. MultiDatabasePDO assigns a UNIQUE column in the queue table and tries repeatedly to insert the new MDGUID.
4. If the MDGUID is inserted successfully it means it's truly unique.
5. You can then insert a new row when you receive the MDGUID as a string.

**Example:**
```php
//Here we generate the MDGUID.
$mdguid = $multiPDO->generateMDGUID();

$longSQL = "INSERT INTO Users VALUES (:mdguid, :username, :pass, :email, :firstname, :lastname)";
$insertQuery = $multiPDO->prepare($longSQL);
$insertQuery->bindValues([
    ":mdguid" => $mdguid,
    ":username" => $_POST["username"],
    ":pass" => password_hash($_POST["password"], PASSWORD_DEFAULT),
    ":email" => $_POST["email"],
    ":firstname" => $_POST["name-first"],
    ":lastname" => $_POST["name-last"]
]);
$insertQuery->execute(true, "Users");
```

## Have Questions? Like It?
If you need to ask a question, reach out to me on Twitter.<br>
Twitter: https://www.twitter.com/WulfGamesYT

If you like this library please consider starring it and sharing it with fellow developers who like PHP & MySQL! Stay tuned for updates and be sure to report any bugs you find to me. Thank you for reading!
