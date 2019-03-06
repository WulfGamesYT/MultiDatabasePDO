<?
    class MultiDatabasePDO {
        
        /**
         * ----------------------------------------------------------------------------------------------
         * 
         *     You are using MultiDatabasePDO v1.0.0 - Copyright Liam Allen (WulfGamesYT), All Rights Reserved.
         *     You may use this PHP Script for any project, even commercially under the following terms:
         *       - You may not sell this script as part of a bundle, or seperately to anyone.
         *       - You must keep this comment, removing it makes me think you want to claim this as your own.
         * 
         * ----------------------------------------------------------------------------------------------
        **/
        
        //Were there any errors connecting initially?
        private $hasAnError = false;
        private $failedConnections = [];
        
        //All the PDO and multi statement instances.
        private $pdoDatabases = [];
        private $multiStatements = [];
        
        /**
         * @method Here we add all the connections and create all the PDO instances.
        **/
        public function __construct(array $connectionParamsList) {
            $errorLoggingLevel = error_reporting();
            error_reporting(0);

            //Loop through each connection init array.
            foreach($connectionParamsList as $paramList) {
                $dsn = $paramList[0] . ":host=" . $paramList[1] . ";dbname=" . $paramList[2] . ";charset=utf8mb4";

                try {
                    $pdoConnection = new PDO($dsn, $paramList[3], $paramList[4]);
                    $this->pdoDatabases[] = $pdoConnection;
                } catch(Exception $f) {
                    $this->hasAnError = true;
                    $this->failedConnections[] = $dsn;
                }
            }
            
            //Set all the default attributes.
            $this->addPDOAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $this->addPDOAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->addPDOAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            error_reporting($errorLoggingLevel);
        }
        
        /**
         * @method Prepare a SQL command to then bind values to, returns a multi statement object.
        **/
        public function prepare($query) {
            $multiStatement = new MultiDatabasePDOStatement($this->pdoDatabases, $query);
            $this->multiStatements[] = $multiStatement;
            return $multiStatement;
        }
        
        /**
         * @method Set a single PDO Attribute to all connections.
        **/
        public function addPDOAttribute($attribute, $value) {
            foreach($this->pdoDatabases as $pdo) {
                $pdo->setAttribute($attribute, $value);
            }
        }
        
        /**
         * @method Checks if there are any errors.
        **/
        public function hasAnyErrors() {
            return $this->hasAnError;
        }
        
        /**
         * @method Gets all failed connections.
        **/
        public function getFailedConnections() {
            return implode(" / ", $this->failedConnections);
        }
        
        /**
         * @method When you've finished, call this function to close all the connections.
         * Once called, all connections are reset ready for the class to be unloaded.
        **/
        public function finishAndClose() {
            foreach($this->pdoDatabases as $pdo) { $pdo = null; }
            foreach($this->multiStatements as $multiStatement) { $multiStatement = null; }
            $this->pdoDatabases = [];
            $this->multiStatements = [];
            $this->latestPreparedStatements = [];
            $this->latestReturnedRows = [];
            $this->hasAnError = false;
            $this->failedConnections = [];
        }
        
    }

    class MultiDatabasePDOStatement {

        //The original PDO databases.
        private $originalPDODatabases = [];

        //All the prepared statements and returned rows.
        private $preparedStatements = [];
        private $returnedRows = [];

        /**
         * @method Here we can create a new statement for all PDO databases.
        **/
        public function __construct($pdoDatabases, $query) {
            $this->originalPDODatabases = $pdoDatabases;
            foreach($pdoDatabases as $pdo) {
                $statement = $pdo->prepare($query);
                $this->preparedStatements[] = $statement;
            }
        }

        /**
         * @method Bind a value to each prepared statement.
        **/
        public function bindValue($nameOrNumber, $value) {
            foreach($this->preparedStatements as $statement) {
                $statement->bindValue($nameOrNumber, $value);
            }
        }

        /**
         * @method Execute this multi statement when it's been prepared and values have been binded.
         * If $insertMode is true, then:
         *   - Insert query will only be run once.
         *   - Data is inserted into the table which has the least amount of rows.
         *   - You will need to provide a table name with $tableName variable.
        **/
        public function execute($insertMode = false, $tableName = "") {
            $this->returnedRows = [];

            if($insertMode === true) {
                if($tableName === "") {
                    throw new Exception("Invalid table name, you must specify a table to insert a new row into (will only run once).");
                    exit;
                }

                //Get the table with the lowest row count in all the PDO databases.
                $lowestTableRowCountDatabase = 0;
                $lowestTableRowCount = PHP_INT_MAX;

                $pdoDatabaseCount = count($this->originalPDODatabases);
                for($i = 0; $i < $pdoDatabaseCount; $i++) {
                    $pdo = $this->originalPDODatabases[$i];
                    $check = $pdo->prepare("SELECT * FROM $tableName");
                    $check->execute();
                    if($check->rowCount() < $lowestTableRowCount) {
                        $lowestTableRowCountDatabase = $i;
                        $lowestTableRowCount = $check->rowCount();
                    }
                }

                $statementToExecute = $this->preparedStatements[$lowestTableRowCountDatabase];
                $statementToExecute->execute();
                while($row = $statementToExecute->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) { $this->returnedRows[] = $row; }
            } else {
                foreach($this->preparedStatements as $statement) {
                    $statement->execute();
                    while($row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) { $this->returnedRows[] = $row; }
                }
            }

            return $this->returnedRows;
        }

        /**
         * @method Fetches next row and deletes it afterwards ready for the next.
        **/
        public function getNextRow() {
            $nextRow = isset($this->returnedRows[0]) ? $this->returnedRows[0] : null;
            if($nextRow !== null) { array_splice($this->returnedRows, 0, 1); }
            return $nextRow;
        }

        /**
         * @method Limits the returned rows to a specific amount, with an optional offset.
         * Instead of putting 'LIMIT 5, 10' in your SQL queries, use this method instead, after you've executed.
        **/
        public function limitTo($limit, $offset = 0) {
            if(count($this->returnedRows) > $limit) {
                array_slice($this->returnedRows, $offset, $limit);
            } else {
                $this->returnedRows = [];
            }
        }

        /**
         * @method Sort columns in the returned rows in a specific direction, either 'ASC' or 'DESC'.
         * Instead of putting 'SORT BY ColumnName DESC' in your SQL queries, use this method instead, after you've executed.
        **/
        public function sortBy($column, $direction) {
            if($direction === "ASC" || $direction === "DESC") {
                if(count($this->returnedRows) > 0) {
                    if(gettype($this->returnedRows[0][$column]) === "string") {
                        return $this->returnedRows;
                    } else {
                        usort($this->returnedRows, function($a, $b) use ($column, $direction) {
                            return $direction === "ASC" ? ($a[$column] > $b[$column]) : ($a[$column] < $b[$column]);
                        });
                    }
                } else {
                    return $this->returnedRows;
                }
            } else {
                throw new Exception("Invalid sort direction, please use either 'ASC' or 'DESC'.");
            }
        }

    }
?>
