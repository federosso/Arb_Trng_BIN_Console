<?PHP
/*
File: 	 	Arbitrage.php
Version:    R_1.1
*/
include "inc_config.php";
include "inc_arb_func.php";
$file_requestor = "Arbitrage.php";

while(true){

    $time_top_page = Check_time();
    //echo "\n \n Start processing: ".$time_top_page;
    echo "\n".$time_top_page;

    if($Sim_Bal == 0){ // (use real balance)
        // -------------------------------------------------------------------------------------------------------
        // check whether to refresh the Balance
        // -------------------------------------------------------------------------------------------------------
        //$_SESSION["Bin_arb_Balance"] = null; // force the balance update // TEST TEST TEST
        if(!isset($_SESSION["Bin_arb_Balance"])){ // check whether to update the data in session
            Arb_Refresh_Balance($conn, $Max_Time_Execution, $exchange, $key, $secret, $validate, $file_requestor);
            if(!empty($_SESSION["Bin_arb_Balance"])){
                $x = 0;
                foreach ($_SESSION["Bin_arb_Balance"] as $arr_key => $val) { 
                    if($val > 0){

                        echo "\n Pair Balance: ".$arr_key.": ".$val;

                        if($x == 0){
                            $info = $arr_key.":".$val;
                        }
                        else{
                            $info = $info.", ".$arr_key.":".$val;
                        }                
                        $x = $x + 1;
                    }            
                }      
                echo "\n";   
                Log_balance($conn, $info);
            }
        }
        if(!empty($_SESSION["Bin_arb_Balance"])){
            $arr_higher_balance = array();
            foreach ($_SESSION["Bin_arb_Balance"] as $arr_key => $val) {		
                foreach ($my_coin_list as $pair_name => $max_equity){ 
                    if($arr_key == $pair_name){ // recover the balance of $my_coin_list (inc_config.php)    
                        if($val > $max_equity){                                                                            
                            // 0:pair name , 1:equiry value
                            $arr_higher_balance[] = array($arr_key, $max_equity); 
                            //echo "\n In use Pair Balance: ".$arr_key.": ".$max_equity;                                         
                        }else{
                            $arr_higher_balance[] = array($arr_key, $val);  
                            //echo "\n In use Pair Balance: ".$arr_key.": ".$val;  
                        } 
                        break;    
                    }
                }
            }    
            if(empty($arr_higher_balance)){
                $info = "Check the variables in the configuration file: my_coin_list and max_coin_usage and the account balance";
                echo "\n ".$info;    
                $info = $file_requestor.", ".$info;
                Log_system_error($conn, $info);
                exit;
            }
        }
        else{
            $info = "Insufficient balance in the account. or session variable not initialized func: Arb_Refresh_Balance()";
            echo "\n ".$info;    
            $info = $file_requestor.", ".$info;
            Log_system_error($conn, $info);
            $_SESSION["Bin_arb_Balance"] = null;
            //header("Refresh:$Max_Time_Execution; url=".$_SERVER['PHP_SELF']);
            exit;
        }  
    }
    else{ // $Sim_Bal = 1; // Enabled (use the data from $ my_coin_list as a simulation of the funds)
        $arr_higher_balance = array();
        echo "\n In use Pair Balance: ";
        foreach ($my_coin_list as $pair_name => $max_equity){             
            $arr_higher_balance[] = array($pair_name, $max_equity); 
            echo "\n ".$pair_name.": ".$max_equity;                                         
        }
    }
    // -------------------------------------------------------------------------------------------------------

    // -------------------------------------------------------------------------------------------------------
    // - I verify that the data of the db are updated (the updating of the triangles takes 1 min)
    // -------------------------------------------------------------------------------------------------------
    $data_already_updated = 1;
    if(!isset($_SESSION["Bin_arb_asset_date_check"])){ // check if the session exists

        // check that the data in the db is up to date, if I don't update the db
        check_Asset_Pairs($conn, $Max_Time_Execution, $file_requestor, $exchange);
        $_SESSION["Bin_arb_asset_date_check"] = Get_Current_Date_mysql(); // assign the current date and time to the session variable  
        $data_already_updated = 0;
    }else{
        // I check the difference between the current date and that in session
        $date_diff_sess = time_diff_now_date_sec($_SESSION["Bin_arb_asset_date_check"])/86400; 
        if($date_diff_sess > 1){ // every day I check if it is necessary to update the data of de currency pairs.
            // check that the data in the db is up to date, if I don't update the db
            check_Asset_Pairs($conn, $Max_Time_Execution, $file_requestor, $exchange);
            $_SESSION["Bin_arb_asset_date_check"] = Get_Current_Date_mysql(); // assign the current date and time to the session variable
            $data_already_updated = 0;
        }
    }
    // -------------------------------------------------------------------------------------------------------

    // -------------------------------------------------------------------------------------------------------
    // - Retrieve the array with the triangulations between assets.
    // -------------------------------------------------------------------------------------------------------
    //$_SESSION["Bin_sess_asset_pairs_all_pairs"] = null;
    if(($data_already_updated == 0) || (!isset($_SESSION["Bin_sess_asset_pairs_all_pairs"]))){
        $sql = "SELECT * FROM asset_pairs_all_pairs";
        $_SESSION["Bin_sess_asset_pairs_all_pairs"] = get_pairs_relations($conn, $sql, "Bin_sess_asset_pairs_all_pairs");        
    }
    $pairs_relations = $_SESSION["Bin_sess_asset_pairs_all_pairs"]; // I use the session to avoid db calls
    // -------------------------------------------------------------------------------------------------------

    // -------------------------------------------------------------------------------------------------------
    // - I take data from Tiker (24hr ticker price change statistics)
    // -------------------------------------------------------------------------------------------------------
    $ticker_data = get_all_tickers_data($conn, $file_requestor, $Max_Time_Execution, $exchange, $validate);
    if(count($ticker_data) > 0){ // check that the ticker has returned an output with the data.   
        // -------------------------------------------------------------------------------------------------------
        // I analyze prices to find profitable triangles
        // -------------------------------------------------------------------------------------------------------
        $arb_av = check_available_arbitrage($conn, $pairs_relations, $ticker_data, $arr_higher_balance, $fee, $min_profit, $offset_volume_order, $min_trans, $vol_ask_bid_check, $exchange);
        if(empty($arb_av)){ 
            //echo"\n No profitable triangles found";
        }
        else{ // $arb_av contains the candidate for arbitration       
            $arr_data = $arb_av[0];
            $arr_relation = $arb_av[1][0];
            $pair_1= $arr_relation[2];
            $pair_2 = $arr_relation[5];
            $pair_3 = $arr_relation[8];
            $triangle_pairs = $pair_1." ".$pair_2." ".$pair_3;
            $ord_vol_1 = $arr_data[1];
            $ord_vol_2 = $arr_data[2];
            $ord_vol_3 = $arr_data[3];
            $action_1 = $arr_data[4];
            $action_2 = $arr_data[5];
            $action_3 = $arr_data[6];
            $pair_price_1 = $arr_data[7];
            $pair_price_2 = $arr_data[8];
            $pair_price_3 = $arr_data[9];        
            $profit = $arr_data[0];
            echo"\n profit: $profit";
            echo"\n Status: ".$arr_relation[10];   
            echo"\n Order 1: $action_1, $pair_1, $ord_vol_1, $pair_price_1";
            echo"\n Order 2: $action_2, $pair_2, $ord_vol_2, $pair_price_2";
            echo"\n Order 3: $action_3, $pair_3, $ord_vol_3, $pair_price_3";
            echo"\n ";
            // -------------------------------------------------------------------------------------------------------
            // I carry out the orders
            // -------------------------------------------------------------------------------------------------------
            $triangle_step = 1;        
            $order_1 = Arb_Manage_Order($conn, $exchange, $key, $secret, $action_1, $pair_1, $ord_vol_1, $triangle_step, $arr_data, $arr_relation, $validate, $triangle_pairs, $fee, $offset_volume_order, $Max_Time_Execution);        
            if($order_1[0] == 1){  // ok order 1
                $triangle_step = 2;                        
                $order_2 = Arb_Manage_Order($conn, $exchange, $key, $secret, $action_2, $pair_2, $order_1[1], $triangle_step, $arr_data, $arr_relation, $validate, $triangle_pairs, $fee, $offset_volume_order, $Max_Time_Execution);                                
                if($order_2[0] == 1){  // ok order 2               
                    $triangle_step = 3;                                
                    $order_3 = Arb_Manage_Order($conn, $exchange, $key, $secret, $action_3, $pair_3, $order_2[1], $triangle_step, $arr_data, $arr_relation, $validate, $triangle_pairs, $fee, $offset_volume_order, $Max_Time_Execution);                                                        
                    if($order_3[0] == 1){ // ok order 3
                        echo "\n Orders successful";
                        $_SESSION["Bin_arb_Balance"] = null; // force the balance update
                        sleep(10); // seconds of waiting
                    }
                    else{
                        echo "\n KO Order 3";
                        $_SESSION["Bin_arb_Balance"] = null; // force the balance update                    
                        exit;
                    }
                }
                else{
                    echo "\n KO Order 2";
                    $_SESSION["Bin_arb_Balance"] = null; // force the balance update                
                    exit;
                }
            }
            else{
                echo "\n KO Order 1";
            }
            $_SESSION["Bin_arb_Balance"] = null; // force the balance update
        }
        sleep($Max_Time_Execution); // seconds of waiting
    }
    else{
        $msg = " Ticker API Error: An array of data was not returned.";
        echo "\n ".$msg;
        $info = $file_requestor.$msg;
        Log_system_error($conn, $info);
        sleep($Max_Time_Execution); // seconds of waiting
    }
    // -------------------------------------------------------------------------------------------------------

    //echo "\n End processing: ".Check_time();
    //$end_process = Check_time();
    //echo "\n Processing time: ".get_date_diff_ms($time_top_page, $end_process);
}
?>