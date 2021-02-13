<?php
    // User Access Token
    include_once(dirname(__FILE__)."/CDatabase.php");
    

    define('AUTH_USER_ID','UserID');
    define('AUTH_IS_AUTHENTICATED','isAuthenticated');


    // Always get the current token via this call.
    // First the session will be started / initialized
    function getAccessToken() {
        global $_UserAccessToken;
        if(!isset($_UserAccessToken)) {
            $_UserAccessToken = new CAccessToken();
        }
        return($_UserAccessToken);
    }

    /**
     * Access Token for the current User
     * Initialize a session and stores the values inside
     * 
     */
     class CAccessToken {

        public $CurrentUserID   = "";

        function __construct()
        {
            session_start();
            $this->CurrentUserID = null;
            if(isset($_SESSION) && isset($_SESSION[AUTH_USER_ID])) { 
                $this->CurrentUserID = $_SESSION[AUTH_USER_ID];
            }
        }

        public function isAuthenticated() {
            return(boolval( $_SESSION[AUTH_IS_AUTHENTICATED]));
        }
        
        public function signIn($UserID, $Passwd) {
            $oUserTable = getUserTable();
            $bAuthenticated = isset($oUserTable) ? $oUserTable->authenticateUser($UserID,$Passwd) : false;
            if($bAuthenticated) {
                $_SESSION[AUTH_IS_AUTHENTICATED] = true;
                $this->CurrentUserID = $UserID;
            }
            else {
                $_SESSION[AUTH_IS_AUTHENTICATED] = false;
            }
        }

        public function signOut() {
            if(isset($_SESSION[AUTH_USER_ID])) {
                unset($_SESSION[AUTH_USER_ID]);
                $_SESSION[AUTH_IS_AUTHENTICATED] = false;
            }
        }

    }
?>