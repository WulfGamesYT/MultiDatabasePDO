## MultiDatabasePDO Documentation
Here is the list of all the public functions you can use in the latest version. The latest version of MultiDatabasePDO can be found here: https://github.com/WulfGamesYT/MultiDatabasePDO/raw/master/MultiDatabasePDO.php

## Class: MultiDatabasePDO
This is the main class, you should only have 1 instance of this throughout PHP. It holds all the information about the currently connected databases and tables/queries.

### Constructor
```php
public function __construct(array $connectionParamsList)
```
`$connectionParamsList` The list of databases to initially connect to, you must supply a DSN, username and password for each.

## Class: MultiDatabasePDOStatement
This is a class to hold all the information about a query to all tables.

TODO...
