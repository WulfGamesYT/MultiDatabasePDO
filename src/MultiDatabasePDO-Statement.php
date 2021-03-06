<?php
    namespace WulfGamesYT\MultiDatabasePDO;
    use PDO, Exception;

    class MultiDatabasePDOStatement {

        private $originalPDODatabases = [];
        private $preparedStatements = [];
        private $returnedRows = [];

        /**
         * @method Here we can create a new statement for all PDO databases.
        **/
        public function __construct(array $pdoDatabases, string $query) {
            $this->originalPDODatabases = $pdoDatabases;
            foreach($pdoDatabases["instances"] as $pdo) {
                $this->preparedStatements[] = $pdo->prepare($query);
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
         * @method Bind multiple values to each prepared statement.
        **/
        public function bindValues(array $items) {
            foreach($items as $nameOrNumber => $value) {
                $this->bindValue($nameOrNumber, $value);
            }
        }

        /**
         * @method Execute this multi statement when it's been prepared and values have been binded.
         * Returns true if all statements were executed, if one fails then the next statements don't execute.
         * If $insertMode is true, then:
         *   - Insert query will only be run once.
         *   - Data is inserted into the table which has the least amount of rows.
         *   - You will need to provide a table name with $table variable.
        **/
        public function execute(bool $insertMode = false, string $table = "") : bool {
            $this->returnedRows = [];

            if($insertMode === true) {
                if($table === "") {
                    throw new Exception("Invalid table name, you must specify a table to insert a new row into (will only run once).");
                    exit;
                }

                //Get the table with the lowest row count in all the PDO databases.
                $lowestTableRowCountDatabase = 0;
                $lowestTableRowCount = PHP_INT_MAX;

                $pdoDatabaseCount = count($this->originalPDODatabases["instances"]);
                for($i = 0; $i < $pdoDatabaseCount; $i++) {
                    $pdo = $this->originalPDODatabases["instances"][$i];
                    $check = $pdo->prepare("SELECT COUNT(*) FROM `$table`");
                    $check->execute();

                    $amountOfRows = $check->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)["COUNT(*)"];
                    if($amountOfRows < $lowestTableRowCount) {
                        $lowestTableRowCountDatabase = $i;
                        $lowestTableRowCount = $amountOfRows;
                    }
                }

                $statementToExecute = $this->preparedStatements[$lowestTableRowCountDatabase];
                return $statementToExecute->execute();
            } else {
                $preparedStatementsSize = count($this->preparedStatements);
                for($i = 0; $i < $preparedStatementsSize; $i++) {
                    $statement = $this->preparedStatements[$i];
                    $wasOkay = $statement->execute();
                    if(!$wasOkay) { return false; }
                    while($row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                        $this->returnedRows[] = ["MultiDatabasePDO-RowInfo" => [
                            "DatabaseFetchedFrom" => ($this->originalPDODatabases["names"][$i]),
                            "ColumnCount" => (count($row))
                        ]] + $row;
                    }
                }
                return true;
            }
        }

        /**
         * @method Returns the current row count.
        **/
        public function rowCount() : int {
            return count($this->returnedRows);
        }

        /**
         * @method Fetches all the current rows.
        **/
        public function getAllRows() : array {
            return $this->returnedRows;
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
            if(count($this->returnedRows) >= $limit) {
                $this->returnedRows = array_slice($this->returnedRows, $offset, $limit === -1 ? PHP_INT_MAX : $limit);
            }
        }

        /**
         * @method Sort columns in the returned rows in a specific direction, either 'ASC' or 'DESC'.
        **/
        public function sortBy(string $column, string $direction) {
            if($direction === "ASC" || $direction === "DESC") {
                if(count($this->returnedRows) > 0) {
                    $columnDataType = gettype($this->returnedRows[0][$column]);
                    
                    //Sort integers, doubles & floats. Else sort strings, objects etc.
                    if($columnDataType === "integer" || $columnDataType === "double") {
                        usort($this->returnedRows, function($a, $b) use ($column, $direction) {
                            return $direction === "ASC" ? ($a[$column] > $b[$column]) : ($a[$column] < $b[$column]);
                        });
                    } else {
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
