<?PHP
/*
File: 	 		Utils_clear_session.php
Purpose:		Removes all sessions and forces data to be reinitialized
*/
include "inc_config.php";
session_destroy(); // FORCE THE PROGRAM TO RECREATE ALL SESSIONS
echo "Sessions Destroyed.";
?>