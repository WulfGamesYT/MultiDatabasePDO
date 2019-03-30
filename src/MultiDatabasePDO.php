<?php
    /**
     * ----------------------------------------------------------------------------------------------
     * 
     *     You are using MultiDatabasePDO v1.0.7 - Copyright Liam Allen (WulfGamesYT), All Rights Reserved.
     *     Licence terms: https://github.com/WulfGamesYT/MultiDatabasePDO#licence
     * 
     * ----------------------------------------------------------------------------------------------
    **/

    namespace WulfGamesYT\MultiDatabasePDO;
    use PDO;

    require "MultiDatabasePDO-Statement.php";

    class MultiDatabasePDO {
        
        private $failedConnections = [];
        private $pdoDatabases = ["instances" => [], "names" => []];
        private $multiStatements = [];
        private $mdguidGenList = [];
        
        /**
         * @method Here we add all the connections and create all the PDO instances.
        **/
        public function __construct(array $connectionParamsList) {
            $errorLoggingLevel = error_reporting();
            error_reporting(0);

            if(count($connectionParamsList) === 0) {
                throw new Exception("You must connect to at least 1 database.");
                exit;
            }

            //Loop through each connection init array.
            foreach($connectionParamsList as $paramList) {
                $dsn = $paramList[0] . ":host=" . $paramList[1] . ";dbname=" . $paramList[2] . ";charset=utf8mb4";

                try {
                    $this->pdoDatabases["instances"][] = new PDO($dsn, $paramList[3], $paramList[4]);
                    $this->pdoDatabases["names"][] = $paramList[2];
                } catch(Exception $f) {
                    $this->failedConnections[] = $dsn;
                }
            }

            //Create MDGUID queue table.
            $queueTableCreate = $this->pdoDatabases["instances"][0]->prepare("CREATE TABLE IF NOT EXISTS `QueueSystemForEveryMDGUID` (`MDGUID` varchar(364) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $queueTableCreate->execute();

            //Delete all MDGUID's from the queue table.
            $queueTableTruncate = $this->pdoDatabases["instances"][0]->prepare("TRUNCATE TABLE `QueueSystemForEveryMDGUID`");
            $queueTableTruncate->execute();

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
         * If there is an error, it will close all PDO connections as you will be exiting the page or showing an error page anyway.
        **/
        public function hasAnyErrors() : bool {
            $hasAnyErrors = count($this->failedConnections) > 0;
            if($hasAnyErrors) { $this->finishAndClose(); }
            return $hasAnyErrors;
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
            foreach($this->pdoDatabases["instances"] as $pdo) {
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
         * @method Generates a new, truly unique MDGUID and inserts it into the queue table.
         * Returns the MDGUID as a string.
        **/
        public function generateMDGUID() : string {
            $theMDGUID = "";
            if(function_exists("com_create_guid") === true) { $theMDGUID = md5(time()) . "-" . trim(com_create_guid(), "{}"); }
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            $theMDGUID = md5(time()) . "-" . vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4));

            $mdguidInsert = $this->pdoDatabases["instances"][0]->prepare("INSERT INTO QueueSystemForEveryMDGUID VALUES (:mdguid)");
            $mdguidInsert->bindValue(":mdguid", $theMDGUID);
            $isUniqueMDGUID = $mdguidInsert->execute();
            if($isUniqueMDGUID !== true) { return $this->generateMDGUID(); }

            $this->mdguidGenList[] = $theMDGUID;
            return $theMDGUID;
        }
        
        /**
         * @method When you've finished, call this function to close all the connections.
         * Once called, all connections are reset ready for the class to be unloaded.
        **/
        public function finishAndClose() {
            foreach($this->mdguidGenList as $mdguid) {
                $delMDGUID = $this->pdoDatabases["instances"][0]->prepare("DELETE FROM QueueSystemForEveryMDGUID WHERE MDGUID = :mdguid");
                $delMDGUID->bindValue(":mdguid", $mdguid);
                $delMDGUID->execute();
            }
            foreach($this->pdoDatabases as &$item) { $item = null; }
            foreach($this->multiStatements as &$multiStatement) { $multiStatement = null; }
            $this->mdguidGenList = [];
            $this->pdoDatabases = ["instances" => [], "names" => []];
            $this->multiStatements = [];
            $this->latestPreparedStatements = [];
            $this->latestReturnedRows = [];
            $this->failedConnections = [];
        }
        
    }
