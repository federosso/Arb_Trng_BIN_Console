<?PHP
/*
File: 	 	    inc_MK_db_tbl_all_pairs.php
Version:        R_1.1
Activities:		updates the tables: asset_pairs and asset_pairs_all_pairs with fresh data

!!! attention this is an include file do not call it directly. !!!
!!! attention this operation may take a few minutes !!!

*/
$info = $file_requestor." call: inc_MK_db_tbl_all_pairs.php";

echo "\n Update of the list of coins and triangles for arbitrage. This may take a few minutes";

// -------------------------------------------------------------------------------------------------------
//  - Retrieve the data with load_markets() for creating the table: asset_pairs_b_q
// -------------------------------------------------------------------------------------------------------
timer_ms4($conn, $Max_Time_Execution, $info); // limit calls to API

$markets = $exchange->load_markets(true); // true force to reload chache    

$data = "";
foreach ($markets as $pair_name => $arr_val) {
    // I only select the pairs on which it is possible to trade
    if(($arr_val["info"]["status"] == "TRADING")&&( $arr_val["spot"] = 1)){

        $base = $arr_val["base"];
        $quote = $arr_val["quote"];
        $ordermin = $arr_val["info"]["filters"][2]["minQty"];
        $prc_base = $arr_val["precision"]["base"];
        $prc_quote = $arr_val["precision"]["quote"];
        $prc_amount = $arr_val["precision"]["amount"];

        // 0:id_ap, 1:asset_name, 2:base, 3:quote, 4:ordermin                               
        $data = $data."('".$pair_name."', '".$base."', '".$quote."', '".$ordermin."', '".$prc_base."', '".$prc_quote."', '".$prc_amount."'),";						
    }    
}

// -------------------------------------------------------------------------------------------------------
//  - I put the data in a single query in the table: asset_pairs_b_q  
// -------------------------------------------------------------------------------------------------------
// I clear the asset_pairs_b_q table before inserting the new data
$sql = "TRUNCATE asset_pairs_b_q";
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
}
$data = substr($data, 0, strlen($data)-1); // I remove the comma at the end of the string
$data = $data.";"; // I enter the ";"
$sql = "INSERT INTO asset_pairs_b_q (asset_name, base, quote, ordermin, prc_base, prc_quote, prc_amount) VALUES ".$data;       
if(!$conn->query($sql) === TRUE) {            
    echo "\n Error: " . $sql . "\n " . $conn->error;  
    $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
    Log_system_error($conn, $info);   
}
// -------------------------------------------------------------------------------------------------------

// -------------------------------------------------------------------------------------------------------
// - I retrieve the AssetPairs from db and create the array: $arr_asset_pairs
// -------------------------------------------------------------------------------------------------------
$arr_asset_pairs = array();
$sql = "SELECT * FROM asset_pairs_b_q";
$result = $conn->query($sql);
if (!$result->num_rows > 0) {
    echo "Data not found in table: asset_pairs_all_pairs";
    exit;
}
while($row = mysqli_fetch_array($result, MYSQLI_NUM)){
    $arr_asset_pairs[] = $row;    
}

// -------------------------------------------------------------------------------------------------------
// - I create the triangulation relationships between the asset_pairs
// -------------------------------------------------------------------------------------------------------

// some passages have been translated from the work of ALEXEY ORESHKIN
// https://www.mql5.com/en/articles/3150

$arr_output = array();
$num_rec = count($arr_asset_pairs);

for ($num_1=0; $num_1<$num_rec; ++$num_1){    // Use the first symbol in the list in the first loop
    // 0:id_ap, 1:asset_name, 2:base, 3:quote, 4:ordermin

    $pair_1 = $arr_asset_pairs[$num_1][1]; // currency pair
    $base_1 = $arr_asset_pairs[$num_1][2]; // base, numerator, (above)
    $quote_1 = $arr_asset_pairs[$num_1][3]; // quote, denominator, (below)

    for ($num_2=0; $num_2<$num_rec; ++$num_2){ // second loop 

        $pair_2 = $arr_asset_pairs[$num_2][1]; // currency pair
        $base_2 = $arr_asset_pairs[$num_2][2]; // base, numerator, (above)
        $quote_2 = $arr_asset_pairs[$num_2][3]; // quote, denominator, (below)

        if((($base_1==$base_2) || ($base_1==$quote_2) || ($quote_1==$base_2) || ($quote_1==$quote_2)) && ($pair_1 != $pair_2)){
                
            for ($num_3=0; $num_3<$num_rec; ++$num_3){   // third loop               

                $pair_3 = $arr_asset_pairs[$num_3][1]; // currency pair
                $base_3 = $arr_asset_pairs[$num_3][2]; // base, numerator, (above)
                $quote_3 = $arr_asset_pairs[$num_3][3]; // quote, denominator, (below)

                if((($base_3==$base_1) || ($base_3==$quote_1) || ($base_3==$base_2) || ($base_3==$quote_2)) && ($pair_3 != $pair_2) && ($pair_3 != $pair_1)){
                    
                    if(($quote_3==$base_1) || ($quote_3==$quote_1) || ($quote_3==$base_2) || ($quote_3==$quote_2)){
                                                                    
                        $arr_output[] = array($base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3);
                        break;
                    }                   
                }
            }
        }            
    }
}

//echo "\n arr_output: ".count($arr_output);

// -------------------------------------------------------------------------------------------------------
// - I take the triangles and do the permutations to add more relations
// -------------------------------------------------------------------------------------------------------

$_SESSION["Bin_sess_arr_triangle"] = array(); // I initialize the array which will contain the new triangles

foreach($arr_output as $key_p => $out_val) {

    $_SESSION["Bin_sess_arr_triangle"][] = $out_val; // I assign the triangles created earlier with the $arr_output array

    add_new_triangle($out_val[0], $out_val[1], $out_val[2], $out_val[3], $out_val[4], $out_val[5], $out_val[6], $out_val[7], $out_val[8]);
}
unset($arr_output); // memory recovery

//echo "\n num triangles: ".count($_SESSION["Bin_sess_arr_triangle"]);

$arr_good_triangle = array();
$arr_bad_triangle = array();

// check which triangles are invalid
foreach($_SESSION["Bin_sess_arr_triangle"] as $key_p => $out_val) {

    $status_triangle =  check_triangle_status($out_val[0], $out_val[2], $out_val[3], $out_val[5], $out_val[6], $out_val[8]);

    if($status_triangle > 0 ){ // all statuses > 0 are valid

        $arr_good_triangle[] = array($out_val[0], $out_val[1], $out_val[2], $out_val[3], $out_val[4], $out_val[5], $out_val[6], $out_val[7], $out_val[8]);
    }
    else{ // the status == 0 are not valid, I permute again the triangles and try again to check if they can be used.

        $arr_bad_triangle[] = array($out_val[0], $out_val[1], $out_val[2], $out_val[3], $out_val[4], $out_val[5], $out_val[6], $out_val[7], $out_val[8]);
    }
}
$count_good_triangle = count($arr_good_triangle);
$count_bad_triangle = count($arr_bad_triangle);
//echo "\n first num good triangles: ".$count_good_triangle; 
//echo "\n num bad triangles: ".$count_bad_triangle; 
// -------------------------------------------------------------------------------------------------------


// -------------------------------------------------------------------------------------------------------
// - I take the bad triangles I permute them further to recover possible new relationships.
// -------------------------------------------------------------------------------------------------------
$_SESSION["Bin_sess_arr_triangle"] = array(); // I empty the session to use it with the new triangles created by the bad_triangle permutation
if($count_bad_triangle > 0){
    foreach ($arr_bad_triangle as $id => $pair_row) {
        // First, let's determine what's in third place.
         // This is a pair with the base currency that doesn't match two other base currencies
        $base_1 = $pair_row[0];
        $pair_1 = $pair_row[1]; // I° pair 
        $quote_1 = $pair_row[2];
        
        $base_2 = $pair_row[3];
        $pair_2 = $pair_row[4]; // II° pair 
        $quote_2 = $pair_row[5];
        
        $base_3 = $pair_row[6];
        $pair_3 = $pair_row[7]; // III° pair 
        $quote_3 = $pair_row[8];     

        // If the base currency of symbols 1 and 2 is the same,
         // skip this step. Otherwise, swap the positions of the pairs
        if($base_1 != $base_2){
                    
            if($base_1 == $base_3){ // I invert positions 2 and 3

                // I assign position 1 the variables 2
                $tmp_pair = $pair_2;
                $tmp_base = $base_2;
                $tmp_quote = $quote_2;

                // I assign position 2 the variables 3
                $pair_2 = $pair_3; 
                $base_2 = $base_3;
                $quote_2 = $quote_3;

                // I assign position 3 the variables 2
                $pair_3 = $tmp_pair; 
                $base_3 = $tmp_base;
                $quote_3 = $tmp_quote;   
            }
            
            if($base_2 == $base_3){
            
                // I assign position 2 to the temporary variables which I will pass to 3
                $tmp_pair = $pair_1;
                $tmp_base = $base_1;
                $tmp_quote = $quote_1;

                // I assign position 1 the variables 3 
                $pair_1 = $pair_3; 
                $base_1 = $base_3;
                $quote_1 = $quote_3;

                // I assign position 3 the the temporary variables
                $pair_3 = $tmp_pair; 
                $base_3 = $tmp_base;
                $quote_3 = $tmp_quote; 
            }
        }
         // Now, let's define the first and second place.
         // Second place takes the pair with the currency
         // of the profit that corresponds to the base currency of the third party.
         // In this case, we always use multiplication.
         // Swap the positions of the first and second pair.       
        if($base_3!=$base_2){
        
            // I assign position 1 to the temporary variables that I will pass to 2
            $tmp_pair = $pair_1;
            $tmp_base = $base_1;
            $tmp_quote = $quote_1;

            // I assign the variables of 2 to position 1
            $pair_1 = $pair_2; 
            $base_1 = $base_2;
            $quote_1 = $quote_2;

            // I assign the variables of 1 to position 2
            $pair_2 = $tmp_pair; 
            $base_2 = $tmp_base;
            $quote_2 = $tmp_quote; 
        }
        
        // I pass the repositioned triangle to the permutation
        add_new_triangle($base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3);
    }
}
unset($count_bad_triangle); // memory recovery

//echo "\n num triangles in session: ".count($_SESSION["Bin_sess_arr_triangle"]);

// - I check if the permutation of bad triangles has generated valid pairs and put them in the array: $ arr_good_triangle
foreach($_SESSION["Bin_sess_arr_triangle"] as $key_p => $out_val) {
    
    $status_triangle =  check_triangle_status($out_val[0], $out_val[2], $out_val[3], $out_val[5], $out_val[6], $out_val[8]);    
    
    if($status_triangle > 0){ // if the triangles are valid I put them in the $ arr_good_triangle array

        $arr_good_triangle[] = array($out_val[0], $out_val[1], $out_val[2], $out_val[3], $out_val[4], $out_val[5], $out_val[6], $out_val[7], $out_val[8]);
    }
}
unset($_SESSION["Bin_sess_arr_triangle"]); //  memory recovery


$num_good_triangle = count($arr_good_triangle);
//echo "\n last num good triangles: ".$num_good_triangle."\n "; 

// check that there are no duplicates in the array: $arr_ultimate_good_triangle
if($num_good_triangle > 0){
    $arr_ultimate_good_triangle = array(); // will contain all valid, non-duplicated triangles
    foreach($arr_good_triangle as $key_p => $out_val) { // check for duplicate rows        
        $x = 0;
        foreach($arr_ultimate_good_triangle as $key => $row) {
            if(($out_val[0] ==  $row[0]) && ($out_val[1] ==  $row[1]) && ($out_val[2] ==  $row[2]) && ($out_val[3] ==  $row[3]) && ($out_val[4] ==  $row[4]) && 
                ($out_val[5] ==  $row[5]) && ($out_val[6] ==  $row[6]) && ($out_val[7] ==  $row[7]) && ($out_val[8] ==  $row[8])){
                $x = 1;
                break;
            }  
        }
        if($x == 0){ // not duplicate array I insert it            
            $status_triangle =  check_triangle_status($out_val[0], $out_val[2], $out_val[3], $out_val[5], $out_val[6], $out_val[8]);            
            $arr_ultimate_good_triangle[] = array($out_val[0], $out_val[1], $out_val[2], $out_val[3], $out_val[4], $out_val[5], $out_val[6], $out_val[7], $out_val[8], $status_triangle);
        }        
    }
    $num_ultimate_good_triangle = count($arr_ultimate_good_triangle);
    //echo "\n num ultimate good triangles: ".$num_ultimate_good_triangle."\n "; 
    // -------------------------------------------------------------------------------------------------------

    // -------------------------------------------------------------------------------------------------------
    // I add the additional information necessary for the creation of the orders
    // -------------------------------------------------------------------------------------------------------
    $arr_pair_1 = array();
    $arr_pair_2 = array();
    $arr_pair_3 = array();
    $goal_arr_triangle = array();
    foreach($arr_ultimate_good_triangle as $key_p => $out_val) { // loop the array with the triangles
        // array(0:$base_1, 1:$pair_1, 2:$quote_1, 3:$base_2, 4:$pair_2, 5:$quote_2, 6:$base_3, 7:$pair_3, 8:$quote_3, 9:$status_triangle);    
        $ok_pair_1 = 0;
        $ok_pair_2 = 0;
        $ok_pair_3 = 0;
        foreach($arr_asset_pairs as $key => $row_val) { // I cycle the array of the asset_pairs_b_q table and assign the fields relative to the pairs of the triangles
            switch($row_val[1]) { // asset_name

                case $out_val[1]: // pair_1

                    $arr_pair_1 = array($row_val[4]);
                    $arr_prc_base = array($row_val[5]);
                    $arr_prc_quote = array($row_val[6]);
                    $arr_prc_amount = array($row_val[7]);
    
                    $ok_pair_1 = 1;
                break;

                case $out_val[4]: // pair_2
                    
                    $arr_pair_2 = array($row_val[4]);
                    $arr_prc_base_1 = array($row_val[5]);
                    $arr_prc_quote_1 = array($row_val[6]);
                    $arr_prc_amount_1 = array($row_val[7]);

                    $ok_pair_2 = 1;
                break;

                case $out_val[7]: // pair_3

                    $arr_pair_3 = array($row_val[4]);
                    $arr_prc_base_2 = array($row_val[5]);
                    $arr_prc_quote_2 = array($row_val[6]);
                    $arr_prc_amount_2 = array($row_val[7]);

                    $ok_pair_3 = 1;
                break;
            }
            if(($ok_pair_1 == 1) && ($ok_pair_2 == 1) && ($ok_pair_3 == 1)){
                // if I have found the data of the 3 pairs I interrupt the cycle.                
                $goal_arr_triangle[] = array_merge($out_val, $arr_pair_1, $arr_pair_2, $arr_pair_3, $arr_prc_base
                                                                                                    , $arr_prc_base_1
                                                                                                    , $arr_prc_base_2
                                                                                                    , $arr_prc_quote
                                                                                                    , $arr_prc_quote_1
                                                                                                    , $arr_prc_quote_2
                                                                                                    , $arr_prc_amount
                                                                                                    , $arr_prc_amount_1
                                                                                                    , $arr_prc_amount_2);             
                break;
            }
        }
    }

    $num_goal_arr_triangle = count($goal_arr_triangle);
    echo "\n num goal arr triangle: ".$num_goal_arr_triangle."\n "; 

    // I undo the asset_pairs_all_pairs table before inserting the new data
    $sql = "TRUNCATE asset_pairs_all_pairs";
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
        Log_system_error($conn, $info);   
    }

    /* 

    Got a packet bigger than \'max_allowed_packet\' bytes')
    MySQL server has gone away

    //  I put the data in a single query in the table: asset_pairs_all_pairs
    $data = "";
    foreach($goal_arr_triangle as $key_p => $out_val) {
        // 0:base, 1:asset_name, 2:quote, 3:base_1, 4:asset_name_1, 5:quote_1, 6:base_2, 
        // 7:asset_name_2, 8:quote_2, 9:status, 10:ordermin, 11:ordermin_1, 12:ordermin_2
        $data = $data."('".$out_val[0]."', '".$out_val[1]."', '".$out_val[2]."', '".$out_val[3]."', '".$out_val[4]."', '".$out_val[5]."', '".$out_val[6]."',";        
        $data = $data." '".$out_val[7]."', '".$out_val[8]."', '".$out_val[9]."', '".$out_val[10]."', '".$out_val[11]."', '".$out_val[12]."', '".$out_val[13]."'
        , '".$out_val[14]."', '".$out_val[15]."', '".$out_val[16]."', '".$out_val[17]."', '".$out_val[18]."', '".$out_val[19]."', '".$out_val[20]."', '".$out_val[21]."'),";
    }
    $data = substr($data, 0, strlen($data)-1); // I remove the comma at the end of the string
    $data = $data.";"; // I enter the ;

    
    $sql = "INSERT INTO asset_pairs_all_pairs (base, asset_name, quote, base_1, asset_name_1, quote_1, base_2, asset_name_2, quote_2, status, 
    ordermin, ordermin_1, ordermin_2, prc_base, prc_base_1, prc_base_2, prc_quote, prc_quote_1, prc_quote_2, prc_amount, prc_amount_1, prc_amount_2) VALUES ".$data;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
        Log_system_error($conn, $info);   
    }
    */

    //  put the data in a single query in the table: asset_pairs_all_pairs
    foreach($goal_arr_triangle as $key_p => $out_val) {
        // 0:base, 1:asset_name, 2:quote, 3:base_1, 4:asset_name_1, 5:quote_1, 6:base_2, 
        // 7:asset_name_2, 8:quote_2, 9:status, 10:ordermin, 11:ordermin_1, 12:ordermin_2
        
        $data = "('".$out_val[0]."', '".$out_val[1]."', '".$out_val[2]."', '".$out_val[3]."', '".$out_val[4]."', '".$out_val[5]."', '".$out_val[6]."',";        
        $data = $data." '".$out_val[7]."', '".$out_val[8]."', '".$out_val[9]."', '".$out_val[10]."', '".$out_val[11]."', '".$out_val[12]."', '".$out_val[13]."'
        , '".$out_val[14]."', '".$out_val[15]."', '".$out_val[16]."', '".$out_val[17]."', '".$out_val[18]."', '".$out_val[19]."', '".$out_val[20]."', '".$out_val[21]."');";

        $sql = "INSERT INTO asset_pairs_all_pairs (base, asset_name, quote, base_1, asset_name_1, quote_1, base_2, asset_name_2, quote_2, status, 
        ordermin, ordermin_1, ordermin_2, prc_base, prc_base_1, prc_base_2, prc_quote, prc_quote_1, prc_quote_2, prc_amount, prc_amount_1, prc_amount_2) VALUES ".$data;
        if(!$conn->query($sql) === TRUE) {            
            echo "\n Error: " . $sql . "\n " . $conn->error;  
            $info = $file_requestor.", ERRORE: " . $sql . "\n " . $conn->error;
            Log_system_error($conn, $info);   
        }
    
    }

}else{
    echo "\n No triangle present in arr_good_triangle";
    exit;
}

// I reset the sessions so the new data will be loaded
$_SESSION["Bin_sess_asset_pairs_all_pairs"] = null; 
$_SESSION["Bin_arb_pairs_relations"] = null;
$_SESSION["Bin_arb_asset_pairs_b_q_str"] = null;
?>