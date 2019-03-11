<?php
    class MultiDatabasePDO {
        
        /**
         * ----------------------------------------------------------------------------------------------
         * 
         *     You are using MultiDatabasePDO v1.0.4 - Copyright Liam Allen (WulfGamesYT), All Rights Reserved.
         *     Licence terms: https://github.com/WulfGamesYT/MultiDatabasePDO#licence
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
                    $this->pdoDatabases[] = new PDO($dsn, $paramList[3], $paramList[4]);
                } catch(Exception $f) {
                    $this->hasAnError = true;
                    $this->failedConnections[] = $dsn;
                }
            }
            
            //Set all the default attributes.
            $this->addPDOAttributes([
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3
            ]);
            error_reporting($errorLoggingLevel);
        }

        /**
         * @method Checks if there were any errors connecting to the database(s).
        **/
        public function hasAnyErrors() : bool {
            return $this->hasAnError;
        }
        
        /**
         * @method Gets all failed connections.
        **/
        public function getFailedConnections() : string {
            return join(" / ", $this->failedConnections);
        }

        /**
         * @method Prepare a SQL command to then bind values to, returns a multi statement object.
        **/
        public function prepare(string $query) : MultiDatabasePDOStatement {
            $multiStatement = new MultiDatabasePDOStatement($this->pdoDatabases, $query);
            $this->multiStatements[] = $multiStatement;
            return $multiStatement;
        }
        
        /**
         * @method Set a single PDO attribute to all connections.
        **/
        public function addPDOAttribute(int $attribute, $value) {
            foreach($this->pdoDatabases as $pdo) {
                $pdo->setAttribute($attribute, $value);
            }
        }

        /**
         * @method Add multiple PDO attributes to all connections.
        **/
        public function addPDOAttributes(array $items) {
            foreach($items as $attribute => $value) {
                $this->addPDOAttribute($attribute, $value);
            }
        }

        /**
         * @method Generates and returns a random unique string to use as a primary key in tables.
        **/
        public function generateRandomID(string $column, string $table, int $length = 48) : string {
            $uniqueStringOptions = [
                "chars" => "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
                "result" => ""
            ];

            for($i = 0; $i < $length; $i++) { $uniqueStringOptions["result"] .= $uniqueStringOptions["chars"][rand(0, strlen($uniqueStringOptions["chars"]) - 1)]; }
            $checkUniqueString = $this->prepare("SELECT * FROM `$table` WHERE `$column` = :multipdoidstring");
            $checkUniqueString->bindValue(":multipdoidstring", $uniqueStringOptions["result"]);
            $checkUniqueString->execute();
            return $checkUniqueString->rowCount() === 0 ? $uniqueStringOptions["result"] : $this->generateRandomID($column, $table, $length);
        }
        
        /**
         * @method When you've finished, call this function to close all the connections.
         * Once called, all connections are reset ready for the class to be unloaded.
        **/
        public function finishAndClose() {
            foreach($this->pdoDatabases as &$pdo) { $pdo = null; }
            foreach($this->multiStatements as &$multiStatement) { $multiStatement = null; }
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
        public function __construct(array $pdoDatabases, string $query) {
            $this->originalPDODatabases = $pdoDatabases;
            foreach($pdoDatabases as $pdo) { $this->preparedStatements[] = $pdo->prepare($query); }
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
         * @method Bind multiple values to each prepared statement.
        **/
        public function bindValues(array $items) {
            foreach($items as $nameOrNumber => $value) {
                $this->bindValue($nameOrNumber, $value);
            }
        }

        /**
         * @method Execute this multi statement when it's been prepared and values have been binded.
         * If $insertMode is true, then:
         *   - Insert query will only be run once.
         *   - Data is inserted into the table which has the least amount of rows.
         *   - You will need to provide a table name with $table variable.
        **/
        public function execute(bool $insertMode = false, string $table = "") : array {
            $this->returnedRows = [];

            if($insertMode === true) {
                if($table === "") {
                    throw new Exception("Invalid table name, you must specify a table to insert a new row into (will only run once).");
                    exit;
                }

                //Get the table with the lowest row count in all the PDO databases.
                $lowestTableRowCountDatabase = 0;
                $lowestTableRowCount = PHP_INT_MAX;

                $pdoDatabaseCount = count($this->originalPDODatabases);
                for($i = 0; $i < $pdoDatabaseCount; $i++) {
                    $pdo = $this->originalPDODatabases[$i];
                    $check = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
                    $check->execute();

                    $amountOfRows = intval($check->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)["COUNT(*)"]);
                    if($amountOfRows < $lowestTableRowCount) {
                        $lowestTableRowCountDatabase = $i;
                        $lowestTableRowCount = $amountOfRows;
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
         * @method Returns the current row count.
        **/
        public function rowCount() : int {
            return count($this->returnedRows);
        }

        /**
         * @method Fetches next row and deletes it afterwards ready for the next.
        **/
        public function getNextRow() : ?array {
            $nextRow = isset($this->returnedRows[0]) ? $this->returnedRows[0] : null;
            if($nextRow !== null) { array_splice($this->returnedRows, 0, 1); }
            return $nextRow;
        }

        /**
         * @method Limits the returned rows to a specific amount, with an optional offset.
        **/
        public function limitTo(int $limit, int $offset = 0) {
            if(count($this->returnedRows) > $limit) {
                $this->returnedRows = array_slice($this->returnedRows, $offset, $limit === -1 ? PHP_INT_MAX : $limit);
            } else {
                $this->returnedRows = [];
            }
        }

        /**
         * @method Sort columns in the returned rows in a specific direction, either 'ASC' or 'DESC'.
        **/
        public function sortBy(string $column, string $direction) {
            if($direction === "ASC" || $direction === "DESC") {
                if(count($this->returnedRows) > 0) {
                    $columnDataType = gettype($this->returnedRows[0][$column]);
                    
                    //Sort whole numbers and doubles/floats.
                    if($columnDataType === "integer" || $columnDataType === "double") {
                        usort($this->returnedRows, function($a, $b) use ($column, $direction) {
                            return $direction === "ASC" ? ($a[$column] > $b[$column]) : ($a[$column] < $b[$column]);
                        });
                    } else {
                        //Sort strings, objects, null etc.
                        $rowSize = count($this->returnedRows);
                        for($i = 0; $i < $rowSize; $i++) {
                            $this->returnedRows[$i][$column] = strval($this->returnedRows[$i][$column]);
                        }

                        usort($this->returnedRows, function($a, $b) use ($column, $direction) {
                            $pos = strcmp($a[$column], $b[$column]);
                            return $direction === "ASC" ? $pos : -$pos;
                        });
                    }
                }
            } else {
                throw new Exception("Invalid sort direction, please use either 'ASC' or 'DESC'.");
            }
        }

    }
