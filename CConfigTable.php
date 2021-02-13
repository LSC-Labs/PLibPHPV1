<?php

    class CConfigTable {

        protected $DB;
        protected $TableName;

        function __construct(CDatabase $DB) {
            $this->DB           = $DB;
            $this->TableName    = $DB->getTableNamePrefix()."ApplConfig";
            $this->createTable();
        } 

        function isConnectionValid() : bool {
            return(isset($this->$DB) && null != $this->DB->getConnection());
        }

        public function setApplConfigValue($Section,$Key,$Value) {
            $this->setValue("@Appl",$Section,$Key,$Value);
        }

        public function getApplConfigValue($Section,$Key,$Default) {
            return($this->getValue("@Appl",$Section,$Key,$Default));
        }

        public function setUserConfigValue($Section,$Key,$Value) {
            $oUser = getAccessToken();
            $this->setValue($oUser->CurrentUserID,$Section,$Key,$Value);
        }

        public function getUserConfigValue($Section,$Key,$Default) {
            $oUser = getAccessToken();
            return($this->getValue($oUser->CurrentUserID,$Section,$Key,$Default));
        }
        

        /**
         * Set a value into the database.
         * If the value exists -> no change
         * If the value is null -> delete the key
         */
        public function setValue($Context, $Section, $Key,$Value) {
            if($this-isConnectionValid()) {
                $LowerSection = empty($Section) ? "*" : strtolower($Section);
                $LowerKey = strtolower($Key);
                $strOldValue = $this->getValue($Section,$Key);
                $strQuery = "";
                if(!isset($strOldValue) & isset($Value)) {
                    $strQuery = "INSERT INTO " . $this->TableName . " ('Context', 'Section', Key, Value) VALUES(:Context, :Section, :Key, :Value)";
                } else if(isset($strOldValue) & isset($Value)) {
                    if($strOldValue != $Value) {
                        $strQuery = "UPDATE INTO  " . $this->TableName . "  (Value) VALUES(:Value) WHERE Context=:Context AND Section=:Section AND Key=:Key";
                    }
                } else if(isset($strOldValue) & !isset($Value)) {
                    $strQuery = "DELETE FROM  " . $this->TableName . "  WHERE Context=:Context AND Section=:Section AND Key=:Key";
                }
                if(isset($strQuery)) {
                    try {
                        $stmt = $this->DB->GetConnection()->prepare($strQuery);
                        $stmt->execute(array(   ':Context'  => $Context,
                                                ':Section'  => $LowerSection,
                                                ':Key'      => $LowerKey,
                                                ':Value'    => $Value
                                                ));
                        logVerbose("Config Value set # %s : [%s] %s=%s",$Context, $LowerSection, $LowerKey,$Value);
                    } catch( Exception $ex) {
                        logException($ex,"CConfigTable::setValue('%s', '%s','%s','%s')",$Context, $Section,$Key,$Value);
                    }
                }
            } 
        }

        /**
         * Get the value from the database if available
         */
        public function getValue($Context, $Section, $Key, $Default = null ) {
            $oResult = $Default;
            if($this->isConnectionValid()) {
                $LowerSection = empty($Section) ? "*" : strtolower($Section);
                $LowerKey = strtolower($Key);
                $strQuery = "SELECT * from  " . $this->TableName . "  
                                WHERE Context = :Context AND Section = :Section AND Key = :Key";
                $stmt = $this->DB->getConnection()->prepare($strQuery);
                $stmt->execute(array( ':Context' => $Context,
                                      ':Section' => $LowerSection,
                                      ':Key'     =>  $LowerKey));
                
                $oRow = $stmt->fetch();
                if($oRow) {
                    $oResult = $oRow['Value'];
                }
            }
            return(isset($oResult) ? $oResult : $Default);
        }

        protected function createTable() {
            if($this->isConnectionValid() && !$this->DB-TableExists($this->$TableName)) {
         
                $strSelect = "CREATE TABLE ".$this->Tablename." (
                                    Context     VARCHAR,
                                    Section 	VARCHAR PRIMARY KEY,
                                    Key			VARCHAR NOT NULL,
                                    Value 		VARCHAR NOT NULL,
                                    CONSTRAINT ".$this->TableName."_UN UNIQUE (Section)
                                );
                                INSERT  INTO ".$this->TableName." 
                                              (Context, Section, Key, Value) 
                                        VALUES('@Appl', 'config', 'version', '0.2');";
                $this->DB->execute($strSelect);
            }
        }
    }

?>