<?PHP
/*
File: 	 		Utils_clear_log.php
Purpose:		Remove log data from mysql db
*/
include "inc_config.php";
include "inc_arb_func.php";

$file_requestor = "Utils_clear_log.php";
$start_process = Check_time();
echo "Start processing: ".$start_process;

$sql = "TRUNCATE arb_log_best_candidate";
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
}

$sql = "TRUNCATE arb_log_errors";
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
}

$sql = "TRUNCATE arb_log_orders";
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
}

$sql = "TRUNCATE arb_log_timer_ms4";
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
} 
echo "\n All done logs removed";   
$end_process = Check_time();
echo "\n Processing time: ".get_date_diff_ms($start_process, $end_process);
?>