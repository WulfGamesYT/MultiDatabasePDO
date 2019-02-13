# MultiDatabasePDO
This is a free, easy to use, lightweight and powerful PHP library that allows you to connect to multiple MySQL databases through PDO. I've always wondered why MySQL doesn't have built in horizontal scaling thats simple for everyone. I've come up with a solution, just have multiple databases with the same table names and columns, and this library will allow you to scale, have as many databases as you want!

## Features
* Connect to multiple MySQL databases using PDO, without having performance issues!
* Get rows from multiple databases from the same named table.
* Perform insert queries efficiently by only doing 1 query instead of adding into all databases/tables.
* Free to use, and it's really easy too, which is great!

## Getting Started
Simply include the file `MultiDatabasePDO.php` in your autoload PHP class or include header file on all pages.
Then you can connect to all your databases easily by doing:
```php
$multiPDO = new MultiDatabasePDO([
    ["mysql", "1.1.1.1", "database_1", "username", "password"],
    ["mysql", "2.2.2.2", "database_2", "username", "password"]
]);
```
