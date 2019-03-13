<?php
    /**
     * ----------------------------------------------------------------------------------------------
     * 
     *     You are using MultiDatabasePDO v1.0.5 - Copyright Liam Allen (WulfGamesYT), All Rights Reserved.
     *     Licence terms: https://github.com/WulfGamesYT/MultiDatabasePDO#licence
     * 
     * ----------------------------------------------------------------------------------------------
    **/

    namespace WulfGamesYT\MultiDatabasePDO;
    use PDO;

    require "MultiDatabasePDO-Statement.php";

    class MultiDatabasePDO {
        
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
            //error_reporting(0);

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
            $multiStatement = new \WulfGamesYT\MultiDatabasePDO\MultiDatabasePDOStatement($this->pdoDatabases, $query);
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
