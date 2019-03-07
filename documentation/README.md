# MultiDatabasePDO Documentation
Here is the list of all the public functions you can use in the latest version (some public functions are unlisted because they are for use by the main class only). The latest version of MultiDatabasePDO can be found here: https://github.com/WulfGamesYT/MultiDatabasePDO/raw/master/MultiDatabasePDO.php

TODO: Move this to an actual wiki.

## Class: MultiDatabasePDO
This is the main class, you should only have 1 instance of this throughout PHP. It holds all the information about the currently connected databases and tables/queries.

### Function: Constructor
```php
public function __construct(array $connectionParamsList)
```
`$connectionParamsList` The list of databases to initially connect to, you must supply a DSN, username and password for each.

### Function: Has Any Errors
```php
public function hasAnyErrors() : bool
```
Returns true if there were any errors connecting to all the databases initially.

## Class: MultiDatabasePDOStatement
This is a class to hold all the information about a query to all tables.

### Function: Bind Value
```php
public function bindValue($nameOrNumber, $value)
```
`$nameOrNumber` The name or number of the placeholder (must precede with a colon ':' if using a string placeholder).<br>
`$value` The variable or value to pass to the placeholder.
