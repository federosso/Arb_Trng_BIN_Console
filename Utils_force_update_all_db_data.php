<?PHP
/*
File: 	 	Utils_force_update_all_db_data.php
Version:    R_1.0
Purpose:    Forces the update of db data
Activity:   Recall the inc_MK_db_tbl_all_pairs.php file which contains the generation of the triangles and population of the tables
!!! attention this operation may take a few minutes !!!
*/
include "inc_config.php";
include "inc_arb_func.php";

$file_requestor = "Utils_force_update_all_db_data.php";
$start_process = Check_time();
echo "Start processing: ".$start_process;

try {
    include "inc_MK_db_tbl_all_pairs.php"; // updates the tables: asset_pairs and asset_pairs_all_pairs with fresh data

} catch (\ccxt\NetworkError $e) {
    echo '[Network Error] ' . $e->getMessage () . "\n";
    } catch (\ccxt\ExchangeError $e) {
    echo '[Exchange Error] ' . $e->getMessage () . "\n";
    } catch (Exception $e) {
    echo '[Error] ' . $e->getMessage () . "\n";
    }

$end_process = Check_time();
echo "\n Processing time: ".get_date_diff_ms($start_process, $end_process);
?>