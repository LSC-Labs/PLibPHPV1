<?php
    include_once(dirname(__FILE__)."/CUser.php");
    include_once(dirname(__FILE__)."/CDatabase.php");

    class CUserTable {

        protected $TableName;
        protected $DB;

        function __construct(CDatabase $DB) {
            $this->DB = $DB;
            $this->TableName = $DB->getTableNamePrefix()."Users";
            $this->createUserTable();
        } 

        function isConnectionValid() : bool {
            return(isset($this->$DB) && null != $this->DB->getConnection());
        }

        function createUser($UserID,$Passwd,$FirstName, $LastName) {
            if($this->isConnectionValid()) {
                if(!$this->doesUserExists($UserID)) {
                    $strQuery = "INSERT INTO ".$this->TableName." (UserID, Passwd, FirstName, LastName, Confirmed )
                                        VALUES(:UserID, :Passwd, :FirstName, :LastName, 0)";
                    $EncPasswd = sha1($Passwd);
                    $LowerUser = strtolower($UserID);
                    $stmt = $this->DB->getConnection()->prepare($strQuery);
                    $stmt->execute(array(   ':UserID' => $LowerUser, 
                                            ':Passwd' => $EncPasswd, 
                                            ':FirstName' => $FirstName,
                                            ':LastName'  => $LastName)
                        );
                    logInfo("User : %s created...",$UserID);
                } else {
                    logWarning("User : %s already exists...",$UserID);
                }
            }
        }

        function getUserByID($UserID) : CUser {
            $oUser = new CUser();
            if($this->isConnectionValid()) {
                $LowerUser = strtolower($UserID);
                $strQuery = "SELECT * from ".$this->TableName." WHERE UserID = ?";
                $stmt = $this->DB->getConnection()->prepare($strQuery);
                $stmt->execute(array($LowerUser));
                
                $oRow = $stmt->fetch();
                if($oRow) {
                    $oUser->Exists    = true;
                    $oUser->UserID    = $oRow['UserID'];
                    $oUser->FirstName = $oRow['FirstName'];
                    $oUser->LastName  = $oRow['LastName'];
                    $oUser->Active    = $oRow['Activ'];
                    $oUser->Confirmed = $oRow['Confirmed'];
                    $oUser->InvalidLogonCounter = $oRow['InvalidLogonCounter'];
                }
            }
            return($oUser);
        }

        public function doesUserExists($UserID) : bool {
            $oUser = $this->getUserByID($UserID);
            return($oUser->Exists);
        }

        public function authenticateUser($UserID,$Passwd) : bool {
            $bAuthenticated = false;
            if($this->isConnectionValid()) {
                $strPasswd           = sha1($Passwd);
                // Query the database for a valid user
                $strQuery = "SELECT * FROM ".$this->TableName." WHERE UserID = ? AND Password = ? ";
                $stmt = $this->DB->getConnection()->prepare($strQuery);
                logTrace("Checking authentication of user : %s",$UserID);
                $stmt->bindParam(1, $UserID, PDO::PARAM_STR);
                $stmt->bindParam(2, $Passwd, PDO::PARAM_STR);
                $stmt->execute();

                // $tResult = getDatabase()->getConnection()->query($strQuery);
                if($stmt->num_rows > 0) {
                    $bAuthenticated = true;
                }
            }
            return($bAuthenticated);
        }

        protected function createUserTable() {
            if($this->isConnectionValid() && !$this->DB->doesTableExists($strTableName)){
                $strSelect =   "CREATE TABLE ".$this->TableName." (
                                    ID 			INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                                    UserID 		TEXT 	NOT NULL,
                                    FirstName 	TEXT,
                                    LastName 	TEXT,
                                    Passwd 		TEXT,
                                    Confirmed   BOOLEAN,
                                    Activ       BOOLEAN,
                                    LastLogon   DateTime, 
                                    InvalidLogonCounter INTEGER,
                                    CONSTRAINT eventUsers_UN UNIQUE (UserID)
                                );
                                INSERT INTO ".$this->TableName.
                                     " ( UserID,    FirstName, LastName, Confirmed, Activ) 
                                       VALUES	  ('Default', 'Default', 'User', 1, 0	);";
                $this->DB->getConnection()->exec($strSelect);
            }

        }
    }

?>