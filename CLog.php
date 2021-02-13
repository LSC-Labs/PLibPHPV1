<?php


 

/**
 * Modules for logging...
 */

    class CLogEntry {
        public $Type;
        public $Msg;
        public $TS;

        function __construct($Type,$Msg) {
            $this->TS   = new DateTime();
            $this->Type = $Type;
            $this->Msg  = $Msg;
        }
    }

    abstract class CLog {
        public $LogLevel    = "IWEX";
        public $TimeFormat  = "c";
        public function flush($tLogEntries) { /** Default == mach nix  **/ }

        public function isLogEntryTypeMatch($oLogEntry) {
            $nPos = strpos($this->LogLevel,$oLogEntry->Type);
            $bIsMatch = strpos($this->LogLevel,$oLogEntry->Type) > -1 ? true : false; 
            return($bIsMatch);
        }

        function getLogEntriesToWrite($tLogEntries) : array {
            $oResult = array();
            foreach($tLogEntries as $oLogEntry);
            if($this->isLogEntryTypeMatch($oLogEntry)) $oResult[] = $oLogEntry;
            return($oResult);
        }
        
    }

    class CLogFile extends CLog {
        function __construct($LogFile , $LogLevel = "IWE") {
            $this->LogLevel = $LogLevel;
            $this->FileName = $LogFile;
        }
        /**
         * Schreibe alle Logeinträge aus dem Array in das Log
         * Aber nur, wenn der Type des Entry im LogLevel enthalten ist.
         * LogLevel = "IXE", LogType = W >> dann nicht
         * LogLevel = "IXE", LogType = I >> dann ja
         * 
         */
        public function flush($tLogEntries) {
            $tLogEntriesToWrite = $this->getLogEntriesToWrite($tLogEntries);
            if(count($tLogEntriesToWrite) > 0) {
                $fp = fopen($this->FileName,"a");
                foreach($tLogEntriesToWrite as $oLogEntry) {
                    $IP = $_SERVER['REMOTE_ADDR'];
                    fprintf($fp,"%s [%s] (%s) %s\n",
                                $oLogEntry->TS->format($this->TimeFormat),
                                $oLogEntry->Type,
                                $IP,
                                $oLogEntry->Msg);
                }
                fclose($fp);
            }
        }
    }
    /**
     * Der LogHandler verwaltet die unterschiedlichen Logger (CLog)
     * Jeder der Logger kann dabei einen eigenen LogLevel haben.
     * Messages können zurück gehalten werden, wenn eine Transaktion läuft.
     */
    class CLogHandler {
        public $ListOfLogger   = array();
        private $ListOfEntries  = array();

        private $LoggingIsActiv = true;

        public function stopLogging()    { $LoggingIsActiv = false;  }
        public function restartLogging() { $LoggingIsActiv = true;   $this-> flush(); }
        public function resetLogging()   { $ListOfEntries = array(); $LoggingIsActive = true; }

        public function addLog(CLog $pLog) { 
            $this->ListOfLogger[] = $pLog; 
        }

        public function logEntry($Type, $Text, ...$Parms) {
            $strMsg = sprintf($Text,...$Parms);
            $this->ListOfEntries[] = new CLogEntry($Type,$strMsg);
            if($this->LoggingIsActiv) $this->flush();
        }

        public function flush() {
            foreach($this->ListOfLogger as $oLog) {
                $oLog->flush($this->ListOfEntries);
            } 
            $this->ListOfEntries = array();
        }
    }