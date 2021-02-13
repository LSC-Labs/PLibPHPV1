<?php
    //  Class DB
    //  (c) 2020 P.Liebl
    //
    // Represents a database access.
    // Needs the dbaccess to get the credentials

    define('DB_SQLITE','SQLite');

    include_once(dirname(__FILE__)."/CApplication.php");

    /** Liefert die aktuelle Datenbank */
    function getDatabase() : CDatabase {
        global $_DB;
        if(!isset($_DB)) {
            $TablePrefix    = getApplIniConfigValue("Database","TableNamePrefix");
            $DBDriver       = getApplIniConfigValue("Database","Driver");
            $_DB = new CDatabase($DBDriver,$TablePrefix);
            if($_DB->isOpen()) {
                if(!$_DB->getUserTable()->doesUserExists('admin')) {
                    logTrace("Creating default admin account...");
                    $_DB->getUserTable()->createUser('admin','password4'.$TablePrefix."Tables","Super","User");
                }
            }
        } 
        return($_DB);
    }

    /* global shortcut to get the Database connection (or null) */
    function getDatabaseCon() {
        $oResult = null;
        return(getDatabase()->isOpen() ? getDatabase()->getConnection() : null);
    }

    /* global shortcut to get the UserTable Object */
    function getUserTable() {
        return(getDatabase()->getUserTable());
    }

    /*
    function getDBConfigValue($Key,$Default) {
        $oTable = getConfigTable();
        $oResult = isset($oTable) ? getConfigTable()->getValue($Key) : null;
        if(!isset($oResult)) $oResult = $Default;
        return($oResult);
    }
*/

    /// The Database
    class CDatabase {
        protected $ConfigTable;
        protected $TableNamePrefix;
        protected $DBDriver;
        public    $dbConn;

        function __construct($Driver,$TableNamePrefix) {
            $this->TableNamePrefix = isset($TableNamePrefix) ? $TableNamePrefix : "";
            $this->DBDriver = $Driver;
            logTrace("Opening Database Type: %s",$Driver);
            switch($Driver) {
                case DB_SQLITE : $this->initSQLite(getApp()->Config[DB_SQLITE]); break;
                default: logWarning("Driver not known: ".$Driver);
            }
            $this->createApplTablesIfNotExist();
        }

        function __destruct() {
            if($this->isOpen()) $dbConn = null;
        }

        // Gets the tablename Prefix of this Database.
        public function getTableNamePrefix() { return($this->TableNamePrefix); }

        /**
         * Specialized initialisation of an SQLite database
         */
        function initSQLite($Config) {
            $FileName = $Config['DataBase'];
            logTrace("Opening/Creating SQLite database (%s)",$FileName);
            try {
                $this->dbConn = new PDO('sqlite:' . $FileName);
            } catch(Exception $ex) {
                logException($ex,"Opening SQLite Database : %s",$FileName);
            }
            // $FileName->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        /**
         * Create all Tables for the Application as defined in the config file
         */
        function createApplTablesIfNotExist() {
            if($this->isOpen()) {
                $DBConfig = getApp()->Config['Database'];
                $nNumTables = $DBConfig['Table.0'];
                for($nIdx = 0; $nIdx < $nNumTables; $nIdx++) {
                    $TableStemIndex = 'Table.'.($nIdx +1);
                    if(isset($DBConfig[$TableStemIndex])) {
                        $TableSectionName = $DBConfig[$TableStemIndex];
                        if(isset($TableSectionName)) {
                            $TableName = $this->TableNamePrefix.getApp()->getApplIniConfigValue($TableSectionName,"Name");
                            if(!$this->doesTableExists($TableName)) {
                                logVerbose("Table ".$TableName." does not exists... creating");  
                                $this->createTable($TableName);
                            } else {
                                logVerbose("Table ".$TableName." exists...");
                            }
                        }
                    }
                }
            } else {
                logWarning("Cannot create tables (if not exist) - database is not available..");
            }
        }

        /**
         * Get a Config Value from the Database.
         * As it is in application context, the section name is always "@app".
         * If no value is found in the databas (or null) the Default will be returned
         */
        public function getApplConfigValue($Section, $Key,$Default = null) {
            return($this->getConfigTable()->getApplConfigValue($Section, $Key,$Default));
        }

        /**
         * Stores a Config Value into the Database
         * As it is for application context, the Section Name is always "@app"
         */
        public function setApplConfigValue($Section,$Key,$Value) {
            return($this->getConfigTable()->setApplConfigValue($Section,$Key,$Value));
        }

        /**
         * Get die Config Tabe of the Database as an object.
         */
        function getConfigTable() : CConfigTable {
            if(!isset($this->ConfigTable)) {
                $dbCon = $this->getConnection();
                if(isset($dbCon)) $this->ConfigTable = new CConfigTable($this);
            }
            return($this->ConfigTable);
        }

        /**
         * Get the User Table as object for
         * Authentication / Authorisation 
         */
        public function getUserTable() : CUserTable {
            if(!isset($this->UserTable)) {
                $this->UserTable = new CUserTable($this);
            }
            return($this->UserTable);
        }

        /**
         * Create a Table.
         * If a specialized version is available, use this.
         * If a drivername is specified, use the driver as a prefix for the filename
         */
        function createTable($TableName) {
            if($this->isOpen()) {
                $FileName = $TableName.".sql";
                $BasePath = getApp()->ConfigPath."sql/";
                $BaseFileName = $BasePath.$FileName;
                $FullFileName = $BaseFileName;
                
                if(isset($this->DBDriver)) {               
                    $DriverBasedFileName = $BasePath.$this->DBDriver."/".$FileName;
                    logTrace("Searching for specialized create statement : %s",$DriverBasedFileName);
                    if(file_exists($DriverBasedFileName)) {
                        $FullFileName = $DriverBasedFileName;
                    } 
                }
                if(file_exists($FullFileName)) {
                    $strSelect = file_get_contents($FullFileName);
                    logTrace("using create statement : \n%s",$strSelect);
                    $this->dbConn->exec($strSelect);
                } else {
                    logWarning("File does not exist: ".$FullFileName);
                }
            } else {
                logWarning("Cannot create table [%s] - database is not available.",$TableName);
            }
        }

        /**
         * Check if a Table exists in the database
         */
        public function doesTableExists($TableName) : bool {
            $bResult = false;
            if($this->isOpen()) {
                $Select = "SHOW TABLE STATUS FROM db_name LIKE '".$TableName."'";
                switch($this->DBDriver) {
                    case DB_SQLITE : $Select = "SELECT name 
                                                FROM sqlite_master 
                                                WHERE type='table' AND name='".$TableName."'";
                                    break;
                }
                logTrace(" checking if table exists with: ".$Select);
                $oResult = $this->dbConn->query($Select);
                // TODO Implement counter for Maria DB...
                $row=$oResult->fetch();
                if($row != false) $bResult = true;
            }  else {
                logError("Database is not open and cannot be used...");
            }
            return($bResult);
        }
       
        /**
         * Check if the Database is open and useable
         */
        public function isOpen() : bool {
            return(isset($this->dbConn));
        }

        /**
         * Get the Connection of the Database
         */
        public function getConnection() : PDO {
            return($this->dbConn);
        }

    }


?>
