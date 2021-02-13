<?php

    include_once(dirname(__FILE__)."/CLog.php");
    include_once(dirname(__FILE__)."/CAccessToken.php");

   /**
     * Main Request Dispatcher
     */
    function dispatchRequest($bRedirectToLogin) {
        if(!getAccessToken()->isAuthenticated()) {
            $LoginPage = getApplIniConfigValue("Dispatch","LoginPage");
            logInfo("Redirecting to login page : %s",$LoginPage);
            header('Location: '.$LoginPage);
            exit;
        } else {
            logVerbose("User ".getAccessToken()->CurrentUserID." is requesting page : ".$_SERVER['REQUEST_URI']);
        }
    }

    function getNavigationUrlToPage($PageName) {
        // Für später die entsprechende dispatch Funktion vorschalten..
        return(getApplIniConfigValue("Dispatch",$PageName));
    }

    /**
     * Get the Main Application Object
     * The Application is stored in the global object "$Appl".
     */
    function getApp() : CApplication {
        global $Appl;
        if(empty($Appl)) { $Appl = new CApplication(); }
        return($Appl);
    }

    function getApplIniConfigValue($SectionName, $KeyName, $Default = "") {
        return(getApp()->getApplIniConfigValue($SectionName,$KeyName,$Default));
    }

       /**
     * Log a Info message
     */
    function logInfo($Text, ...$Params) {
        getApp()->LogHandler->logEntry("I", $Text,...$Params);      
    }

    function logVerbose($Text, ...$Params) {
        getApp()->LogHandler->logEntry("V", $Text,...$Params);
    }

    function logWarning($Text, ...$Params) {
        getApp()->LogHandler->logEntry("W", $Text,...$Params);
    }

    function logError($Text, ...$Params) {
        getApp()->LogHandler->logEntry("E", $Text,...$Params);
    }

    function logTrace($Text, ...$Params) {
        getApp()->LogHandler->logEntry("T", $Text, ...$Params);
    }

    function logException(Exception $ex,$Text = null, ...$Params) {
        getApp()->LogHandler->logEntry("X",$Text, ...$Params);
        getApp()->LogHandler->logEntry("X"," -> %s",$ex->getMessage());
    }



    /**
     * The Application Class
     * 
     * (c) 2020 P. Liebl
     * 
     * Contains the application Object with Logging, Configuration ...
     * 
     */
    class CApplication {
        public $ConfigPath = "../cfg/";
        public $Config;
        public $LogHandler;
        public $ApplVersion = "0.0";
        public $ApplName = "";
 
        function __construct() {
            $ConfigFile = $this->ConfigPath."AppConfig.ini";
            $this->Config = parse_ini_file($ConfigFile, true);
            $this->LogHandler = new CLogHandler();
            $this->__initialize();
        }

        function __initialize() {
            // Add the Logfiles as defined in the config file...
            $nLogCounter= $this->getApplIniConfigValue("Logging","LogFile.0","0");
            for($nIdx = 1; $nIdx <= $nLogCounter; $nIdx++) {
                $LogFile  = $this->getApplIniConfigValue('Logging','LogFile.'.$nIdx,null);
                $LogLevel = $this->getApplIniConfigValue('Logging','LogFile.'.$nIdx.".LogLevel",'IWEX');
                if(isset($LogFile)) { 
                    $this->LogHandler->addLog( new CLogFile($LogFile,$LogLevel)); 
                }
            } 
            $strApplVersion = $this->getApplIniConfigValue("Application","Version",null);
            if(!isset($strApplVersion)) $this->ApplVersion = $strApplVersion;
            $this->ApplName = $this->getApplIniConfigValue("Application","Name");
        }

        public function getApplIniConfigValue($SectionName, $KeyName, $Default = null) {
            $oResult = $Default;
            try {
                if(isset($this->Config[$SectionName])) {
                    $ConfigSection = $this->Config[$SectionName];
                    if(isset($ConfigSection[$KeyName])) {
                        $oResult = $ConfigSection[$KeyName];
                    } 
                }
                // Substituiere die Werte
                // Achtung !!! keine Logeinträge hierhin -> Recursion Problem..
            } catch (Exception $ex) {
                logException($ex,"getApplIniConfigValue(%s, %s, %s)",$SectionName,$KeyName,$Default);
            }
            return(isset($oResult) ? $oResult : $Default);
        }
    }