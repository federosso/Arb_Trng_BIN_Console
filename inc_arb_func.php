<?php
/*
File: inc_arb_func.php
Version: R_1.3
*/

// check if there are triangles to profit from
function check_available_arbitrage($conn, $pairs_relations, $ticker_data, $arr_higher_balance, $fee, $min_profit, $offset_volume_order, $min_trans, $vol_ask_bid_check, $exchange){

    $best_candidate = array();
    $order_candidate = array();
    $output_pairs = array();
    $diff_vol_temp = 0;

    // I analyze the triangles and associate the price data to check if there are possibilities of operation
    $x =0;
    foreach ($pairs_relations as $id => $pair_row) {  // loop the array with the asset pair triangles
               
        // 0:id_ap, 1:base, 2:asset_name, 3:quote, 4:base_1, 5:asset_name_1, 6:quote_1, 7:base_2, 8:asset_name_2, 9:quote_2,
        // 10:status, 11:ordermin, 12:ordermin_1, 13:ordermin_2, 14:prc_base, 15:prc_base_1, 16:prc_base_2, 17:prc_quote, 
        // 18:prc_quote_1, 19:prc_quote_2, 20:prc_amount, 21:prc_amount_1, 22:prc_amount_2

        $pair_1 = $pair_row[2]; // I° pair 
        $base_1 = $pair_row[1];
        $quote_1 = $pair_row[3];
        $pair_2 = $pair_row[5]; // II° pair 
        $base_2 = $pair_row[4];
        $quote_2 = $pair_row[6];
        $pair_3 = $pair_row[8]; // III° pair 
        $base_3 = $pair_row[7];
        $quote_3 = $pair_row[9];
        $status = $pair_row[10];
        $order_min_1 = $pair_row[11];
        $order_min_2 = $pair_row[12];    
        $order_min_3 = $pair_row[13];
        $prc_amount_1 = $pair_row[20];
        $prc_amount_2 = $pair_row[21];    
        $prc_amount_3 = $pair_row[22];

        // check that the triangle contains my currency present in $my_coin_list -> $arr_higher_balance
        $ok_pair_chk = 0;
        foreach ($arr_higher_balance as $key => $row_val){ 
            // check that the pair name of the funds corresponds to the initial and final pairs of the triangles (ex: EUR == EUR)
            if((($base_1 == $row_val[0]) || ($quote_1 == $row_val[0])) && (($base_3 == $row_val[0]) || ($quote_3 == $row_val[0]))){                      
                $amount = $row_val[1]; // I check the amount corresponding to the balance
                $ok_pair_chk = 1;
                break;
            }
        }
        
        if($ok_pair_chk == 1){               
            $P1 = 0;
            $P2 = 0;
            $P3 = 0;
            $ok_pair_1 = 0;
            $ok_pair_2 = 0;
            $ok_pair_3 = 0;
            $pair_ask_1 = 0;
            $pair_bid_1 = 0;
            $pair_ask_2 = 0;
            $pair_bid_2 = 0;
            $pair_ask_3 = 0;
            $pair_bid_3 = 0;
            $error_min_vol = 0;
            $action_ord_1 = "";
            $action_ord_2 = "";
            $action_ord_3 = "";
            $V1 = 0;
            $V2 = 0;
            $V3 = 0;
            $Ord1 = 0;
            $Ord2 = 0;
            $Ord3 = 0;
            $diff_vol = 0;
                             
            // I assign the bid and ask prices to the various pairs   
            foreach ($ticker_data as $pair_name => $ticker_val) { // loop the array with the ticker data
           
                switch($pair_name) {

                    case $pair_1:  // I° pair                       
                        $pair_ask_1 = $ticker_val["ask"]; // ask price (demand) (buy price)
                        $pair_bid_1 = $ticker_val["bid"]; // bid price (offer) (sell price)
                        $trans_1 = $ticker_val["info"]["count"];// number of transactions in the last 24 hours
                        $vol_pair_ask_1 = $ticker_val["askVolume"]; // lot volume relative to the price of ASK
                        $vol_pair_bid_1 = $ticker_val["bidVolume"]; // lot volume relative to the BID price	                                                                                     
                        if(($pair_ask_1 == 0) || ($pair_bid_1 == 0) || ($trans_1 == 0) || ($vol_pair_ask_1 == 0) || ($vol_pair_bid_1 == 0)){                        
                           break;
                        }
                        else{
                            $ok_pair_1 = 1;
                        }
                    break;

                    case $pair_2:  // II° pair                       
                        $pair_ask_2 = $ticker_val["ask"]; 
                        $pair_bid_2 = $ticker_val["bid"]; 
                        $trans_2 = $ticker_val["info"]["count"];
                        $vol_pair_ask_2 = $ticker_val["askVolume"];
                        $vol_pair_bid_2 = $ticker_val["bidVolume"];                        
                        if(($pair_ask_2 == 0) || ($pair_bid_2 == 0) || ($trans_2 == 0) || ($vol_pair_ask_2 == 0) || ($vol_pair_bid_2 == 0)){                        
                           break;
                        }
                        else{
                            $ok_pair_2 = 1;
                        }
                    break;

                    case $pair_3: // III° pair 
                        $pair_ask_3 = $ticker_val["ask"];
                        $pair_bid_3 = $ticker_val["bid"];
                        $trans_3 = $ticker_val["info"]["count"];
                        $vol_pair_ask_3 = $ticker_val["askVolume"];
                        $vol_pair_bid_3 = $ticker_val["bidVolume"];
                        if(($pair_ask_3 == 0) || ($pair_bid_3 == 0) || ($trans_3 == 0) || ($vol_pair_ask_3 == 0) || ($vol_pair_bid_3 == 0)){
                           break;
                        }
                        else{
                            $ok_pair_3 = 1;
                        }
                    break;
                }
                
                // if I have found the data of the 3 pairs I interrupt the cycle.
                if(($ok_pair_1 == 1) && ($ok_pair_2 == 1) && ($ok_pair_3 == 1)){                                       
                    break;
                }
            }

            // if I have recovered all the necessary data I continue with the analysis of the triangles
            if(($ok_pair_1 == 1) && ($ok_pair_2 == 1) && ($ok_pair_3 == 1)){ // conrolli to avoid Warning: Division by zero
                $diff_vol = 0; 
                // I analyze the 4 possible combinations of pairs for the intent of the triangles
                //Status 1 // EUR/USD = GBP/USD * EUR/GBP   // 1:SELL, 2:BUY,  3:BUY     
                //Status 2 // EUR/USD =	EUR/GBP * GBP/USD   // 1:BUY,  2:SELL, 3:SELL
                //Status 3 // GBP/USD = EUR/USD / EUR/GBP   // 1:SELL, 2:BUY,  3:SELL 
                //Status 4 // EUR/GBP =	EUR/USD	/ GBP/USD   // 1:BUY,  2:SELL, 3:BUY
                switch($status) { 
                        //-----------------------------------------------------------------------------------------------------------------------------                      
                    case 1:  //Status 1  
                        //-----------------------------------------------------------------------------------------------------------------------------                      
                        // EURUSD = GBPUSD * EURGBP
                        // 1:SELL, 2:BUY, 3:BUY  // operations to be carried out on the base currency
                        // I associate the prices according to the status
                        $P1 = $pair_bid_1;
                        $P2 = $pair_ask_2;
                        $P3 = $pair_ask_3;
                        $vol_pair_a_b_1 = $vol_pair_bid_1;
                        $vol_pair_a_b_2 = $vol_pair_ask_2;
                        $vol_pair_a_b_3 = $vol_pair_ask_3;
                        // I calculate the price difference
                        $str = "P2*P3";
                        $P2_A_P3 = $P2 * $P3;                        
                        $diff_prz = $P1 - $P2_A_P3;
                        $diff_prz_perc = ((($P1 - $P2_A_P3) / $P2_A_P3) * 100); 

                        //----------------------------------------------------                        
                        // 1) Order: 1 // Status: 1 // SELL // BID
                        //----------------------------------------------------
                        $action_ord_1 = "sell"; //  V1 = V0 * P1
                        $V0_mf = ($amount - ($amount * $fee)); // $V0_mf // initial margin that will be used to pay the first fee
                        
                        //$V0_ofs = ($V0_mf - ($V0_mf * $offset_volume_order)/100); // V0_ofs // offset calculation to cushion the price fluctuation                        
                        //$V0_ofs_amt_1 = truncate($V0_ofs, $prc_amount_1); // format the volume dacimals // V0_ofs_amt_1 // I set the decimals with the exchange values
                        
                        $V0_ofs_amt_1 = truncate($V0_mf, $prc_amount_1); // format the volume dacimals // V0_ofs_amt_1 // I set the decimals with the exchange values

                        $V1 = $V0_ofs_amt_1 * $P1; // currency to the numerator " * "  // V1 = V0 * P1  // calculation V1
                        $V1_fee = ($V1 - ($V1 * $fee));  // V1 remaining after fees
                        $Ord1 = $V0_ofs_amt_1;  // setup Ordine 1
                        if($Ord1 < $order_min_1){ // check ordermin                                                   
                            $error_min_vol = 1; // I set the ERROR to discard the triangle                           
                            break; // I interrupt the execution of the code present in the switch case                           
                        } 
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord1 = 0;
                        if($vol_pair_a_b_1 > $Ord1){
                            $ok_ord1 = 1;
                        }                        
                        
                        //----------------------------------------------------                        
                        // 2) Order: 2 // Status: 1 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_2 = "buy"; // V2 = V1 / P2
                        $V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100);  // offset calculation to cushion the price fluctuation
                        $V2 = $V1_ofs / $P2; // currency in the denominator " / " // V2 = V1 / P2 // calculation V2
                        $V2_amt_2 = truncate($V2, $prc_amount_2); // format the volume dacimals
                        $V2_fee = ($V2_amt_2 - ($V2_amt_2 * $fee));  // V2 remaining after fees
                        $Ord2 = $V2_amt_2; // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1
                             $error_min_vol = 1; // set the ERROR to discard the triangle                            
                             break; // I interrupt the execution of the code present in the switch case                           
                         }                                                 
                        // I check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord2 = 0;
                        if($vol_pair_a_b_2 > $Ord2){
                            $ok_ord2 = 1;
                        }  
 
                        //----------------------------------------------------                        
                        // 3) Order: 3 // Status: 1 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_3 = "buy"; // V3 = V2 / P3
                        $V2_ofs = ($V2_fee - ($V2_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        $V3 = $V2_ofs / $P3; // currency in the denominator " / " // V3 = V2 / P3 // I calculate the price difference V3
                        $V3_amt_3 = truncate($V3, $prc_amount_3); // format the volume dacimals
                        $V3_fee = ($V3_amt_3 - ($V3_amt_3 * $fee));  // V3 remaining after fees
                        $Ord3 = $V3_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check ordermin_2                             
                             $error_min_vol = 1; // set the ERROR to discard the triangle                            
                             break; // I interrupt the execution of the code present in the switch case                           
                        }                              
                        // I check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord3 = 0;
                        if($vol_pair_a_b_3 > $Ord3){
                            $ok_ord3 = 1;
                        }                         
                        $diff_vol = $V3_fee - $amount; // volume analysis to estimate profits                                                                                           
                    break;

                        //-----------------------------------------------------------------------------------------------------------------------------                  
                    case 2: //Status 2       
                        //-----------------------------------------------------------------------------------------------------------------------------                  
                        // EUR/USD = EUR/GBP * GBP/USD
                        // 1:BUY, 2:SELL, 3:SELL      // operations to be performed on the base pair 
                        // I associate the prices according to the status
                        $P1 = $pair_ask_1;
                        $P2 = $pair_bid_2;
                        $P3 = $pair_bid_3;
                        $vol_pair_a_b_1 = $vol_pair_ask_1;
                        $vol_pair_a_b_2 = $vol_pair_bid_2;
                        $vol_pair_a_b_3 = $vol_pair_bid_3;
                        // I calculate the price difference
                        $str = "P2*P3";
                        $P2_A_P3 = $P2 * $P3;
                        $diff_prz = $P2_A_P3 - $P1;
                        $diff_prz_perc = ((($P2_A_P3 - $P1) / $P1) * 100); 
                                             
                        //----------------------------------------------------                        
                        // 1) Order: 1 // Status: 2 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_1 = "buy"; // V1 = V0 / P1                                            
                        $V0_mf = ($amount - ($amount * $fee)); // $V0_mf // initial margin that will be used to pay the first fee
                        $V0_ofs = ($V0_mf - ($V0_mf * $offset_volume_order)/100); // V0_ofs // offset calculation to cushion the price fluctuation
                        $V1 = $V0_ofs / $P1; // currency in the denominator " / "  // I calculate the price difference V1
                        $V1_amt_1 = truncate($V1, $prc_amount_1); // format the volume dacimals                        
                        $V1_fee = ($V1_amt_1 - ($V1_amt_1 * $fee)); // V1 remaining after fees
                        $Ord1 = $V1_amt_1; // setup order 1                        
                        if($Ord1 < $order_min_1){ // check ordermin
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        } 
                        // check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord1 = 0;
                        if($vol_pair_a_b_1 > $Ord1){
                            $ok_ord1 = 1;
                        }   

                        //----------------------------------------------------                        
                        // 2) Order: 2 // Status: 2 // SELL // BID
                        //----------------------------------------------------
                        $action_ord_2 = "sell"; // V2 = V1 * P2
                        
                        //$V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        //$V1_ofs_amt_2 = truncate($V1_ofs, $prc_amount_2); // format the volume dacimals                        
                        
                        $V1_ofs_amt_2 = truncate($V1_fee, $prc_amount_2); // format the volume dacimals                        

                        $V2 = $V1_ofs_amt_2 * $P2; // currency to the numerator " * " // V2 = V1 * P2 // I calculate the price difference V2                        
                        $V2_fee = ($V2 - ($V2 * $fee));  // V2 remaining after fees
                        $Ord2 = $V1_ofs_amt_2;  // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }   
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord2 = 0;
                        if($vol_pair_a_b_2 > $Ord2){
                            $ok_ord2 = 1;
                        }   
 
                        //----------------------------------------------------                        
                        // 3) Order: 3 // Status: 2 // SELL // BID
                        //----------------------------------------------------
                        $action_ord_3 = "sell";  // V3 = V2 * P3                                                
                        
                        //$V2_ofs = ($V2_fee - ($V2_fee * $offset_volume_order)/100);// offset calculation to cushion the price fluctuation
                        //$V2_ofs_amt_3 = truncate($V2_ofs, $prc_amount_3); // format the volume dacimals

                        $V2_ofs_amt_3 = truncate($V2_fee, $prc_amount_3); // format the volume dacimals
                        
                        $V3 = $V2_ofs_amt_3 * $P3; // currency to the numerator " * " // V3 = V2 * P3 // I calculate the price difference V3
                        $V3_fee = ($V3 - ($V3 * $fee)); // V3 remaining after fees                        
                        $Ord3 = $V2_ofs_amt_3;  // setup order 3
                        if($Ord3 < $order_min_3){ // check ordermin_2                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }  
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord3 = 0;
                        if($vol_pair_a_b_3 > $Ord3){
                            $ok_ord3 = 1;
                        }  

                        $diff_vol = $V3_fee - $amount; // volume analysis to estimate profits                            
                    break;

                        //-----------------------------------------------------------------------------------------------------------------------------                 
                    case 3: //Status 3       
                        //-----------------------------------------------------------------------------------------------------------------------------                 
                        // GBP/USD = EUR/USD /	EUR/GBP
                        // 1:SELL, 2:BUY, 3:SELL     // operations to be performed on the base pair
                        // I associate the prices according to the status                        
                        $P1 = $pair_bid_1;
                        $P2 = $pair_ask_2;
                        $P3 = $pair_bid_3;
                        $vol_pair_a_b_1 = $vol_pair_bid_1;
                        $vol_pair_a_b_2 = $vol_pair_ask_2;
                        $vol_pair_a_b_3 = $vol_pair_bid_3;
                        // calculate the price difference
                        $str = "P2/P3";                        
                        $P2_A_P3 = ($P2 / $P3);
                        //$diff_prz = $P2_A_P3 - $P1;
                        $diff_prz = $P1 - $P2_A_P3;   // not sure
                        $diff_prz_perc = ((($P1 - $P2_A_P3) / $P2_A_P3) * 100);                      
                         
                        //----------------------------------------------------                        
                        // 1) Order: 1 // Status: 3 // SELL // BID
                        //----------------------------------------------------
                        $action_ord_1 = "sell"; // V1 = V0 * P1
                        $V0_mf = ($amount - ($amount * $fee)); // $V0_mf // initial margin that will be used to pay the first fee                        
                        
                        //$V0_ofs = ($V0_mf - ($V0_mf * $offset_volume_order)/100); // V0_ofs // offset calculation to cushion the price fluctuation
                        //$V0_ofs_amt_1 = truncate($V0_ofs, $prc_amount_1); // format the volume dacimals

                        $V0_ofs_amt_1 = truncate($V0_mf, $prc_amount_1); // format the volume dacimals
                        
                        $V1 = $V0_ofs_amt_1 * $P1; // currency to the numerator " * " // V1 = V0 * P1  // price difference V1
                        $V1_fee = ($V1 - ($V1 * $fee)); // V1 remaining after fees                        
                        $Ord1 = $V0_ofs_amt_1; // setup order 1
                        if($Ord1 < $order_min_1){ // check ordermin                                  
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        } 
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord1 = 0;
                        if($vol_pair_a_b_1 > $Ord1){
                            $ok_ord1 = 1;
                        }   

                        //----------------------------------------------------                        
                        // 2) Order: 2 // Status: 3 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_2 = "buy"; // V2 =  V1 / P2
                        $V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                        
                        $V2 = $V1_ofs / $P2; // currency in the denominator " / " // V2 = V1 / P2 // I calculate the price difference V2
                        $V2_amt_2 = truncate($V2, $prc_amount_2); // format the volume dacimals                        
                        $V2_fee = ($V2_amt_2 - ($V2_amt_2 * $fee)); // V2 remaining after fees
                        $Ord2 = $V2_amt_2; // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                                   
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }  
                        // I check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord2 = 0;
                        if($vol_pair_a_b_2 > $Ord2){
                            $ok_ord2 = 1;
                        }   

                        //----------------------------------------------------                                 
                        // 3) Order: 3 // Status: 3 // SELL // BID
                        //----------------------------------------------------
                        $action_ord_3 = "sell"; // V3 = V2 * P3
                        
                        //$V2_ofs = ($V2_fee - ($V2_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        //$V2_ofs_amt_3 = truncate($V2_ofs, $prc_amount_3); // format the volume dacimals                        

                        $V2_ofs_amt_3 = truncate($V2_fee, $prc_amount_3); // format the volume dacimals                        
                        
                        $V3 = $V2_ofs_amt_3 * $P3; // currency to the numerator " * "  // V3 = V2 * P3 // I calculate the price difference V3
                        $V3_fee = ($V3 - ($V3 * $fee));  // V3 remaining after fees                        
                        $Ord3 = $V2_ofs_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check ordermin_2                                                  
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }                       
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord3 = 0;
                        if($vol_pair_a_b_3 > $Ord3){
                            $ok_ord3 = 1;
                        }  

                        $diff_vol = $V3_fee - $amount; // volume analysis to estimate profits
                    break;

                        //-----------------------------------------------------------------------------------------------------------------------------                
                    case 4: //Status 4        
                        //-----------------------------------------------------------------------------------------------------------------------------                
                        // EUR/GBP = EUR/USD /	GBP/USD
                        // 1:BUY, 2:SELL, 3:BUY     // operations to be performed on the base pair
                        // I associate the prices according to the status                        
                        $P1 = $pair_ask_1;
                        $P2 = $pair_bid_2;
                        $P3 = $pair_ask_3;
                        $vol_pair_a_b_1 = $vol_pair_ask_1;
                        $vol_pair_a_b_2 = $vol_pair_bid_2;
                        $vol_pair_a_b_3 = $vol_pair_ask_3;
                        // I calculate the price difference
                        $str = "P2/P3";                        
                        $P2_A_P3 = ($P2 / $P3);
                        //$diff_prz = $P1 - $P2_A_P3;
                        $diff_prz = $P2_A_P3 - $P1; // not sure
                        $diff_prz_perc = ((($P2_A_P3 - $P1) / $P1) * 100);

                        //----------------------------------------------------
                        // 1) Order: 1 // Status: 4 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_1 = "buy"; // V1 =  V0 / P1                        
                        $V0_mf = ($amount - ($amount * $fee)); // $V0_mf // initial margin that will be used to pay the first fee
                        $V0_ofs = ($V0_mf - ($V0_mf * $offset_volume_order)/100); // V0_ofs // offset calculation to cushion the price fluctuation
                        $V1 = $V0_ofs / $P1; // currency in the denominator " / "  // V1 = V0 / P1   // I calculate the price difference V1
                        $V1_amt_1 = truncate($V1, $prc_amount_1); // format the volume dacimals                                                                        
                        $V1_fee = ($V1_amt_1 - ($V1_amt_1 * $fee)); // V1 remaining after fees
                        $Ord1 = $V1_amt_1; // setup order 1
                        if($Ord1 < $order_min_1){ // check ordermin                                                     
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }                                                  
                        // I check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord1 = 0;
                        if($vol_pair_a_b_1 > $Ord1){
                            $ok_ord1 = 1;
                        }   

                        //----------------------------------------------------
                        // 2) Order: 2 // Status: 4 // SELL // BID                        
                        //----------------------------------------------------
                        $action_ord_2 = "sell"; // V2 = V1 * P2
                        
                        //$V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        //$V1_ofs_amt_2 = truncate($V1_ofs, $prc_amount_2); // format the volume dacimals                                                                        

                        $V1_ofs_amt_2 = truncate($V1_fee, $prc_amount_2); // format the volume dacimals                                                                        
                        
                        $V2 = $V1_ofs_amt_2 * $P2; // currency to the numerator " * " // V2 = V1 * P2  // I calculate the price difference V2                        
                        $V2_fee = ($V2 - ($V2 * $fee)); // V2 remaining after fees
                        $Ord2 = $V1_ofs_amt_2;  // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }                                                
                        // I check if the volume available at the bid price is greater than the volume of the order I want to set
                        $ok_ord2 = 0;
                        if($vol_pair_a_b_2 > $Ord2){
                            $ok_ord2 = 1;
                        }   

                        //----------------------------------------------------                        
                        // 3) Order: 3 // Status: 4 // BUY // ASK
                        //----------------------------------------------------
                        $action_ord_3 = "buy"; // V3 = V2 / P3
                        $V2_ofs = ($V2_fee - ($V2_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        $V3 = $V2_ofs / $P3; // currency in the denominator " / " // V3 = V2 / P3 // I calculate the price difference V3
                        $V3_amt_3 = truncate($V3, $prc_amount_3); // format the volume dacimals                        
                        $V3_fee = ($V3_amt_3 - ($V3_amt_3 * $fee)); // V3 remaining after fees
                        $Ord3 = $V3_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check ordermin_2                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                            break; // I interrupt the execution of the code present in the switch case                           
                        }                         
                        // I check if the volume available at the ask price is greater than the volume of the order I want to set
                        $ok_ord3 = 0;
                        if($vol_pair_a_b_3 > $Ord3){
                            $ok_ord3 = 1;
                        }  

                        $diff_vol = $V3_fee - $amount; // volume analysis to estimate profits
                    break;
                }
                
                if($error_min_vol == 0){ // no ERROR related to minimum volumes
                                                            
                    $diff_prz = truncate($diff_prz_perc, 8); // format the volume dacimals
                    $diff_vol_perc = ((($V3_fee - $amount) / $amount) * 100);                    
                    $diff_vol = $diff_vol_perc;                    
                    
                    // I check the number of transactions made in the last 24 hours
                    $chk_min_trans = "KO";
                    if(($trans_1 >= $min_trans) && ($trans_2 >= $min_trans) && ($trans_3 >= $min_trans) ){
                        $chk_min_trans = "OK";                            
                    }
                    $str_log_min_trans = "min:$min_trans, $chk_min_trans, T1:$trans_1, T2:$trans_2, T3:$trans_3";

                    // I check the volumes associated with the bid and ask price to see if they fulfill my order
                    if($vol_ask_bid_check == true){
                        $chk_vol_a_b = "KO";
                        if(($ok_ord1 == 1) && ($ok_ord2 == 1) && ($ok_ord3 == 1)){
                            $chk_vol_a_b = "OK";
                        }
                    }
                    else{
                        $chk_vol_a_b = "NO CHECK";  // volume control disabled (see file inc_config.php , $vol_ask_bid_check = false)
                    }
                    $str_log_vol_check = "chk:$chk_vol_a_b, V1:$vol_pair_a_b_1, V2:$vol_pair_a_b_2, V3:$vol_pair_a_b_3";      

                    // I store the pair with the highest profit. 
                    if(!empty($best_candidate)){                        
                        if($diff_vol > $diff_vol_temp){                                                        
                            $best_candidate = array($Ord1, $Ord2, $Ord3, $diff_vol, $status, $pair_1, $pair_2, $pair_3, $P1, $P2, $P3, $str, $P2_A_P3, $diff_prz, $str_log_min_trans, $str_log_vol_check);
                            $diff_vol_temp = $diff_vol;                                
                        }
                    }else{                                         
                        $best_candidate = array($Ord1, $Ord2, $Ord3, $diff_vol, $status, $pair_1, $pair_2, $pair_3, $P1, $P2, $P3, $str, $P2_A_P3, $diff_prz, $str_log_min_trans, $str_log_vol_check);
                        $diff_vol_temp = $diff_vol;                            
                    }                   

                    // if the profit is greater than the minimum profit then I proceed with the arbitrage process                                                   
                    if(($diff_vol > $min_profit) && (($trans_1 > $min_trans) && ($trans_2 > $min_trans) && ($trans_3 > $min_trans)) ){                                                 
                        $ok_output = 0;
                        if($vol_ask_bid_check == true){ // I check if the volume control is set in the configuration file (see file inc_config.php , $vol_ask_bid_check = true)
                            if(($ok_ord1 == 1) && ($ok_ord2 == 1) && ($ok_ord3 == 1)){ // I verify that the volumes set in the order are lower than the volumes related to the price
                                $ok_output = 1;
                            }
                        }
                        else{
                            $ok_output = 1; // the control is not set, I proceed with the execution of the script
                        }
                    
                        if($ok_output == 1){
                            // I memorize the triangle with the highest profit.
                            if(!empty($output_pairs)){                                
                                if($diff_vol > $diff_vol_temp ){                                                                                                                                       
                                    $output_pairs = array(array($diff_vol, $Ord1, $Ord2, $Ord3, $action_ord_1, $action_ord_2, $action_ord_3, $P1, $P2, $P3), array($pair_row));                                                                               
                                    $diff_vol_temp  = $diff_vol;                                          
                                    $order_candidate = array($Ord1, $Ord2, $Ord3, $diff_vol, $status, $pair_1, $pair_2, $pair_3, $P1, $P2, $P3, $str, $P2_A_P3, $diff_prz, $str_log_min_trans, $str_log_vol_check);
                                }
                            }else{                                                                                                                 
                                $output_pairs = array(array($diff_vol, $Ord1, $Ord2, $Ord3, $action_ord_1, $action_ord_2, $action_ord_3, $P1, $P2, $P3), array($pair_row));                                                                                                                             
                                $diff_vol_temp  = $diff_vol;                            
                                $order_candidate = array($Ord1, $Ord2, $Ord3, $diff_vol, $status, $pair_1, $pair_2, $pair_3, $P1, $P2, $P3, $str, $P2_A_P3, $diff_prz, $str_log_min_trans, $str_log_vol_check);
                            }                                                        
                        }           
                    }
                }                                                    
            }   
        }            
    }
    // I store the triangle with the highest profit in the db
    if(!empty($order_candidate)){
        log_best_candidate($conn, $order_candidate);         
        //echo "\n order_candidate: $order_candidate[5] $order_candidate[6] $order_candidate[7], Ord1: $order_candidate[0] Ord2: $order_candidate[1] Ord3: $order_candidate[2] , profit: $order_candidate[3]%";     
        echo " ORDER: $order_candidate[5] $order_candidate[6] $order_candidate[7], Ord1: $order_candidate[0] Ord2: $order_candidate[1] Ord3: $order_candidate[2] , profit: ".truncate($best_candidate[3], 3)."%";
    }
    elseif(!empty($best_candidate)){
        log_best_candidate($conn, $best_candidate);          
        //echo "\n best_candidate: $best_candidate[5] $best_candidate[6] $best_candidate[7], profit: $best_candidate[3]%"; 
        echo " BC: $best_candidate[5] $best_candidate[6] $best_candidate[7], profit: ".truncate($best_candidate[3], 3)."%";                         
    }
return($output_pairs);
}

function get_arr_asset_pairs_b_q($conn, $file_requestor){
    $_SESSION["Bin_arb_arr_asset_pairs_b_q"] = array(); // array with the list of assets from the database
    $info = $file_requestor." call: get_arr_asset_pairs_b_q()";
    // I recover the list of asset_pairs from the db
    $sql = "SELECT * FROM asset_pairs_b_q";
    $result = $conn->query($sql);
    if ($conn->error) {
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = $file_requestor.", ERROR: " . $sql . " " . $conn->error;
        Log_system_error($conn, $info);  
        exit;
    }
    while($row = mysqli_fetch_array($result, MYSQLI_NUM)){     
        $_SESSION["Bin_arb_arr_asset_pairs_b_q"][] = $row; // I fill the array with the elements of the db
    }   
    //echo "aaa";
    return $_SESSION["Bin_arb_arr_asset_pairs_b_q"];
}

// I verify that the data in the table: asset_pairs_b_q of the db match the data of the exchange
function check_Asset_Pairs($conn, $Max_Time_Execution, $file_requestor, $exchange){
    $KO_DB_DATA = 0; // if = 1 it means that the database is not updated with the latest exchange data
    $info = $file_requestor." call: check_Asset_Pairs()";
    // I recover the list of asset_pairs from the db
    $sql = "SELECT * FROM asset_pairs_b_q";
    $result = $conn->query($sql);
    if ($conn->error) {
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = $file_requestor.", ERROR: " . $sql . " " . $conn->error;
        Log_system_error($conn, $info);  
        exit;
    }
    
    $arr_asset_pairs_db = array(); // array with the list of assets from the database   
    while($row = mysqli_fetch_array($result, MYSQLI_NUM)){     
        $arr_asset_pairs_db[] = $row[1]; // I fill the array with the asset name (ex: ETH / BTC)
    }    
    timer_ms4($conn, $Max_Time_Execution, $info); // I limit API calls
    try {             
        // recall the asset data from the exchange
        $markets = $exchange->load_markets(true); // (true) force to reload chache        
        //print_r ($markets);
        foreach ($markets as $pair_name => $arr_val) {        
            // I only select the pairs on which it is possible to trade
            if(($arr_val["info"]["status"] == "TRADING")&&( $arr_val["spot"] = 1)){
                // I check that the pairs name of the db and of the exchange correspond
                $ok_pair = 0;
                foreach ($arr_asset_pairs_db as $pair_db_name){ 
                    if($pair_db_name == $pair_name){
                        $ok_pair = 1;
                        break;
                    }
                }
                if($ok_pair == 0){ // pairs names do not match
                    $KO_DB_DATA = 1; // the data of the db are not updated.
                    break;
                }    
            }    
        }    
  
        // if there is no data in the db I will restart the creation of the tables with the pairs and their relationships (minimum 1 minute)
        if($KO_DB_DATA == 1){        
            // the $ arr_AssetPairs_K array is used in the include file
            //require "inc_MK_db_tbl_all_pairs.php"; // updates the data of the tables: asset_pairs and asset_pairs_all_pairs
            include "inc_MK_db_tbl_all_pairs.php"; // updates the data of the tables: asset_pairs and asset_pairs_all_pairs
             // cancel the session variable which contains the data of the relationships between the asset pairs
             // will be recreated later by calling the function: get_pairs_relations
            $_SESSION["Bin_sess_asset_pairs_all_pairs"] = null; 
        }               
    
    } catch (\ccxt\NetworkError $e) {
        $info = '[Network Error] ' . $e->getMessage();
        $info = "check_Asset_Pairs(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (\ccxt\ExchangeError $e) {
        $info = '[Exchange Error] ' . $e->getMessage();
        $info = "check_Asset_Pairs(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (Exception $e) {
        $info = '[Error] ' . $e->getMessage();
        $info = "check_Asset_Pairs(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    }   
}

// speed up the process of retrieving relationship data
// execute the db call once and store the data in the session variable: sess_asset_pairs_all_pairs
// added field with result of check_triangle_status function to check whether to multiply or divide the pairs of triangles
function get_pairs_relations($conn, $sql, $sess_name){
    //$_SESSION[$sess_name] = null; // I reset the session so the new data of the tbl asset_pairs_all_pairs will be loaded
    if(!isset($_SESSION[$sess_name])){ // the session var is not present I create it and the people
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $_SESSION[$sess_name] = array();
            while($pair_row = mysqli_fetch_array($result, MYSQLI_NUM)){ 
                //$id_ap, $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3, $status";
                // inserisco in sessione i dati provenienti dal db
                $_SESSION[$sess_name][] = $pair_row; 
            }
        }else{                       
            $info =  "No data available for the table: asset_pairs_all_pairs. func: get_pairs_relations($sql, $sess_name)";            
            echo "\n ".$info;                
            Log_system_error($conn, $info);
            exit;
        }  
        mysqli_free_result($result); 
    }
    return $_SESSION[$sess_name];
}

function get_all_tickers_data($conn, $file_requestor, $Max_Time_Execution, $exchange, $validate){    
    $result = null;
    $info = $file_requestor." call: get_all_tickers_data()";
    timer_ms4($conn, $Max_Time_Execution, $info); // limit calls to bees
    try { 
        if($validate == 1){
            // ATTENTION OFTEN THE TESTNET HAS NO PAIR AND VOLUMES SUITABLE FOR TRIANGLES ..
            $exchange->set_sandbox_mode(true); // sets the call to the simulation server
        }
        $result = $exchange->fetch_tickers(); // I recall the data from the exchange   
        //print_r ($result);
    
    } catch (\ccxt\NetworkError $e) {
        $info = '[Network Error] ' . $e->getMessage();
        $info = "get_all_tickers_data(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (\ccxt\ExchangeError $e) {
        $info = '[Exchange Error] ' . $e->getMessage();
        $info = "get_all_tickers_data(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (Exception $e) {
        $info = '[Error] ' . $e->getMessage();
        $info = "get_all_tickers_data(), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    }
    return $result;
}

function get_ticker_data($conn, $file_requestor, $Max_Time_Execution, $pairs_name, $exchange){
    $result = null;
    $info = $file_requestor." call: get_ticker_data()";
    timer_ms4($conn, $Max_Time_Execution, $info); // I limit API calls
    try {
        $result = $exchange->fetch_ticker($pairs_name);    
    } catch (\ccxt\NetworkError $e) {
        $info =  '[Network Error] ' . $e->getMessage();
        $info = "get_ticker_data($pairs_name), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (\ccxt\ExchangeError $e) {
        $info =  '[Exchange Error] ' . $e->getMessage();
        $info = "get_ticker_data($pairs_name), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    } catch (Exception $e) {
        $info =  '[Error] ' . $e->getMessage();
        $info = "get_ticker_data($pairs_name), ".$info;
        echo $info;
        Log_system_error($conn, $info); 
    }    
    return $result;
}        

function check_available_arbitrage_step_2($conn, $Max_Time_Execution, $exchange, $key, $secret, $validate, $offset_volume_order, $arr_data, $arr_relation, $amount, $num_loop, $fee){
    //-------------------------------------------------------------------------------------
    // recovery of data related to pair_2 (step 2)
    //-------------------------------------------------------------------------------------          
    //$arr_data: 0:$diff_prz, 1:$V1, 2:$V2, 3:$V3, 4:$action_ord_1, 5:$action_ord_2, 6:$action_ord_3, 7:$P1, 8:$P2, 9:$P3                 
    //$arr_relation: 0:id_ap, 1:base, 2:asset_name, 3:quote, 4:base_1, 5:asset_name_1, 6:quote_1, 
    // 7:base_2, 8:asset_name_2, 9:quote_2, 10:status, 11:ordermin, 12:ordermin_1, 13:ordermin_2

    //$Ord2_Sim = $arr_data[2]; // ordine 2  best_candidate
    $output_step_2 = array(); // array di output
    $ko_arb_step_2  = 0; // ERROR of check_available_arbitrage_step_2 () function
    $Ord2 = 0; // output volume to set for the operation           
    $status = $arr_relation[10];
    $prc_amount_2 = $arr_relation[21];        
    $pair_2 = $arr_relation[5];        
    $order_min_2 = $arr_relation[12];
    $error_min_vol = 0; // ERROR insufficient minimum volume
    $file_requestor = "check_available_arbitrage_step_2()";

     if($amount > 0){ // variable received from Arb_Manage_Order () in step 1
        if(($status != 2) && ($status != 4)){ // I sell the volume purchased in step 1 there is no need to reI calculate the price difference of prices and volumes
            //-------------------------------------------------------------------------------------
            // Ticker data update
            //-------------------------------------------------------------------------------------                       
            $arr_ticker = get_ticker_data($conn, $file_requestor, $Max_Time_Execution, $pair_2, $exchange);
            if(isset($arr_ticker["symbol"])){                
                $pair_ask_2 = $arr_ticker["ask"]; // ask price (demand) (buy price)
                $pair_bid_2 = $arr_ticker["bid"]; // bid price (offer) (sell price)                                    
            }
            else{
                // KO TICKER
                $ko_arb_step_2  = 3;
                $info = "check_available_arbitrage_step_2($pair_2), Error: 3, arr_ticker produced no results";
                echo "\n ".$info;
                Log_system_error($conn, $info);                               
            }                                            
            if($ko_arb_step_2 == 0){  
                // I analyze the 4 possible combinations of pairs for the intent of the triangles
                switch($status) { 

                    case 1:  //Status 1  
                        //-----------------------------------------------------------------------------------------------------------------------------                      
                        // I associate the prices according to the status                        
                        $P2 = $pair_ask_2;                        
                        //----------------------------------------------------
                        // 2) BUY  // Status:1  Order:2
                        //----------------------------------------------------
                        $V1_fee  = ($amount - ($amount * $fee));  // amount remaining after fees
                        $V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                        
                        $V2 = $V1_ofs / $P2; // currency in the denominator " / " // V2 = V1 / P2   // I calculate the price difference V2
                        $V2_amt_2 = truncate($V2, $prc_amount_2); // format the volume dacimals
                        $Ord2 = $V2_amt_2;// setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1
                                $error_min_vol = 1; // set the ERROR to discard the triangle                                                                                        
                        }                                            
                    break;

                    case 2: //Status 2       
                        //-----------------------------------------------------------------------------------------------------------------------------                                          
                        $P2 = $pair_bid_2;  // I associate the prices according to the status
                        //----------------------------------------------------
                        // 2) SELL  // Status:2  Order:2
                        //----------------------------------------------------                        
                        $V1_fee = ($amount - ($amount * $fee));  // amount remaining after fees    

                        //$V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                       
                        //$V1_ofs_amt_2 = truncate($V1_ofs, $prc_amount_2); // format the volume dacimals

                        $V1_ofs_amt_2 = truncate($V1_fee, $prc_amount_2); // format the volume dacimals

                        $V2 = $V1_ofs_amt_2 * $P2; // currency to the numerator " * " // V2 = V1 * P2  // I calculate the price difference V2                        
                        $Ord2 = $V1_ofs_amt_2; // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                        }   
                    break;

                    case 3: //Status 3       
                        //-----------------------------------------------------------------------------------------------------------------------------                                         
                        $P2 = $pair_ask_2; // I associate the prices according to the status                        
                        //----------------------------------------------------
                        // 2) BUY  // Status:3  Order:2
                        //----------------------------------------------------                        
                        $V1_fee = ($amount - ($amount * $fee)); // amount remaining after fees
                        $V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        $V2 = $V1_ofs / $P2; // currency in the denominator " / " // V2 = V1 / P2   // I calculate the price difference V2
                        $V2_amt_2 = truncate($V2, $prc_amount_2); // format the volume dacimals
                        $Ord2 = $V2_amt_2; // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                                   
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                        }                             
                    break;

                    case 4: //Status 4        
                        //-----------------------------------------------------------------------------------------------------------------------------                
                        $P2 = $pair_bid_2; // I associate the prices according to the status                        
                        //----------------------------------------------------
                        // 2) SELL  // Status:4  Order:2
                        //----------------------------------------------------                        
                        $V1_fee = ($amount - ($amount * $fee)); // amount remaining after fees
                        
                        //$V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
                        //$V1_ofs_amt_2 = truncate($V1_ofs, $prc_amount_2); // format the volume dacimals   
                        
                        $V1_ofs_amt_2 = truncate($V1_fee, $prc_amount_2); // format the volume dacimals
                        
                        $V2 = $V1_ofs_amt_2 * $P2; // currency to the numerator " * " // V2 = V1 * P2  // I calculate the price difference V2
                        $Ord2 = $V1_ofs_amt_2;  // setup order 2
                        if($Ord2 < $order_min_2){ // check ordermin_1                            
                            $error_min_vol = 1; // set the ERROR to discard the triangle                            
                        }  
                    break;
                }   
            }
            if($error_min_vol == 1){   // KO minimum volume                       
                $ko_arb_step_2 = 4;
                $info = "check_available_arbitrage_step_2($pair_2), Error: 4, the available volume is less than the minimum established";
                echo "\n ".$info;   
                Log_system_error($conn, $info);                  
            }                        
        }
        else{ // status = 2 o 4
            $V1_fee = ($amount - ($amount * $fee));  // amount remaining after fees
            
            //$V1_ofs = ($V1_fee - ($V1_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation
            //$V1_ofs_amt_2 = truncate($V1_ofs, $prc_amount_2); // format the volume dacimals

            $V1_ofs_amt_2 = truncate($V1_fee, $prc_amount_2); // format the volume dacimals
            
            $Ord2 = $V1_ofs_amt_2; // setup order 2
            if($Ord2 < $order_min_2){ // check ordermin_1                            
                $error_min_vol = 1; // set the ERROR to discard the triangle
                $ko_arb_step_2 = 4; // KO minimum volume
                $info = "check_available_arbitrage_step_2($pair_2), Error: 4, the available volume is less than the minimum established";
                echo "\n ".$info;   
                Log_system_error($conn, $info);                            
            }  
        }
    }
    else{
        $ko_arb_step_2  = 2; // KO amount
        $info = "check_available_arbitrage_step_2($pair_2), Error: 2, amount = 0, variable setting error. check Arb_Manage_Order()";
        echo "\n ".$info;
        Log_system_error($conn, $info);             
    }           
    $output_step_2 = array($Ord2, $ko_arb_step_2);   
   
return($output_step_2);   
}
                  
function check_available_arbitrage_step_3($conn, $Max_Time_Execution, $exchange, $key, $secret, $validate, $offset_volume_order, $arr_data, $arr_relation, $amount, $num_loop, $fee){

    //-------------------------------------------------------------------------------------
    // recovery of data related to pair_3 (step 3)
    //-------------------------------------------------------------------------------------    
    //$arr_data: 0:$diff_prz, 1:$V1, 2:$V2, 3:$V3, 4:$action_ord_1, 5:$action_ord_2, 6:$action_ord_3, 7:$P1, 8:$P2, 9:$P3 
    // $arr_relation: 0:id_ap, 1:base, 2:asset_name, 3:quote, 4:base_1, 5:asset_name_1, 6:quote_1, 7:base_2, 8:asset_name_2, 9:quote_2,
    // 10:status, 11:ordermin, 12:ordermin_1, 13:ordermin_2, 14:prc_base, 15:prc_base_1, 16:prc_base_2, 17:prc_quote, 
    // 18:prc_quote_1, 19:prc_quote_2, 20:prc_amount, 21:prc_amount_1, 22:prc_amount_2 
    
    //$Ord3_Sim = $arr_data[3]; // ordine 3  best_candidate
    $output_step_3 = array(); // array di output   
    $ko_arb_step_3  = 0; // ERROR function check_available_arbitrage_step_3()
    $Ord3 = 0; // volume to set for the order 3          
    $status = $arr_relation[10];
    $prc_amount_3 = $arr_relation[22];    
    $error_min_vol = 0; // ERROR insufficient minimum volume
    $pair_3 = $arr_relation[8];                  
    $order_min_3 = $arr_relation[13];             
    $file_requestor = "check_available_arbitrage_step_3()";

    if($amount > 0){ // variable received from Arb_Manage_Order () in step 2
        if(($status != 2) && ($status != 3)){ // I sell the volume purchased in step 2 there is no need to recalculate prices and volumes
            //-------------------------------------------------------------------------------------
            // Ticker data update
            //-------------------------------------------------------------------------------------                       
            $arr_ticker = get_ticker_data($conn, $file_requestor, $Max_Time_Execution, $pair_3, $exchange);
            if(isset($arr_ticker["symbol"])){                                    
                $pair_ask_3 = $arr_ticker["ask"]; // ask price (demand) (buy price)
                $pair_bid_3 = $arr_ticker["bid"]; // bid price (offer) (sell price)                                      
            }
            else{                
                $ko_arb_step_3 = 3; // KO TICKER
                $info = "check_available_arbitrage_step_3($pair_3), Error: 3, arr_ticker produced no results";
                echo "\n ".$info;
                Log_system_error($conn, $info);                               
            }                                               
            if($ko_arb_step_3 == 0){  // if the tiker did not give any errors I proceed with the data analysis
                // I analyze the 4 possible combinations of pairs for the intent of the triangles
                switch($status) { 

                    case 1:  //Status 1  
                        //-----------------------------------------------------------------------------------------------------------------------------                      
                        $P3 = $pair_ask_3;
                        //----------------------------------------------------
                        // 3) BUY  // Status:1  Order:3
                        //----------------------------------------------------                                                
                        $amount_fee = ($amount - ($amount * $fee)); // amount remaining after fees                        
                        $V2_ofs = ($amount_fee - ($amount_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                        
                        $V3 = $V2_ofs / $P3; // currency in the denominator " / "  //  calculate the price difference V2
                        $V3_amt_3 = truncate($V3, $prc_amount_3); // format the volume dacimals                                                
                        $Ord3 = $V3_amt_3; // setup order 3

                        if($Ord3 < $order_min_3){ // check order_min_3
                                //echo " ERROR Order Min 3 ";                             
                                $error_min_vol = 1; // set the ERROR to discard the triangle                                                                                        
                        }                                            
                    break;

                    case 2: //Status 2                              
                        //-----------------------------------------------------------------------------------------------------------------------------                  
                        $P3 = $pair_bid_3;
                        //----------------------------------------------------
                        // 3) SELL  // Status:2  Order:3
                        //----------------------------------------------------                                               
                        $amount_fee = ($amount - ($amount * $fee)); // amount remaining after fees                        
                        
                        //$V2_ofs = ($amount_fee - ($amount_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                        
                        //$V2_ofs_amt_3 = truncate($V2_ofs, $prc_amount_3); // format the volume dacimals 
                        
                        $V2_ofs_amt_3 = truncate($amount_fee, $prc_amount_3); // format the volume dacimals  
                        
                        $Ord3 = $V2_ofs_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check order_min_3                                                             
                                 $error_min_vol = 1; // set the ERROR to discard the triangle                                                                                        
                        }  
                    break;

                    case 3: //Status 3       
                        //-----------------------------------------------------------------------------------------------------------------------------                 
                        $P3 = $pair_bid_3;
                        //----------------------------------------------------         
                        // 3) SELL  // Status:3  Order:3
                        //----------------------------------------------------                                               
                        $amount_fee = ($amount - ($amount * $fee)); // amount remaining after fees                       
                        
                        //$V2_ofs = ($amount_fee - ($amount_fee * $offset_volume_order)/100);  // offset calculation to cushion the price fluctuation                       
                        //$V2_ofs_amt_3 = truncate($V2_ofs, $prc_amount_3); // format the volume dacimals 
                        
                        $V2_ofs_amt_3 = truncate($amount_fee, $prc_amount_3); // format the volume dacimals  

                        $Ord3 = $V2_ofs_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check order_min_3                                                        
                            $error_min_vol = 1; // set the ERROR to discard the triangle                                                                                        
                        }                                 
                    break;

                    case 4: //Status 4        
                        //-----------------------------------------------------------------------------------------------------------------------------                
                        $P3 = $pair_ask_3;
                        //----------------------------------------------------
                        // 3) BUY  // Status:4  Order:3
                        //----------------------------------------------------                                               
                        $amount_fee = ($amount - ($amount * $fee)); // amount remaining after fees                        
                        $V2_ofs = ($amount_fee - ($amount_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation                        
                        $V3 = $V2_ofs / $P3;  // currency in the denominator " / "
                        $V3_amt_3 = truncate($V3, $prc_amount_3); // format the volume dacimals                        
                        $Ord3 = $V3_amt_3; // setup order 3
                        if($Ord3 < $order_min_3){ // check order_min_3
                            //echo " ERROR Order Min 3 ";                             
                            $error_min_vol = 1; // set the ERROR to discard the triangle                                                                                        
                        }  
                    break;
                }   
            }

            if($error_min_vol == 1){   // KO minimum volume                                  
                $ko_arb_step_3 = 4;
                $info = "recheck_available_arbitrage_step_3($pair_3), Error: 4, the available volume is less than the minimum established";
                echo "\n ".$info;   
                Log_system_error($conn, $info);                  
            }                                    
        }
        else{ // status = 2 o 3            
            $amount_fee = ($amount - ($amount * $fee)); // amount remaining after fees            
            
            //$V2_ofs = ($amount_fee - ($amount_fee * $offset_volume_order)/100); // offset calculation to cushion the price fluctuation            
            //$V2_ofs_amt_3 = truncate($V2_ofs, $prc_amount_3); // format the volume dacimals  

            $V2_ofs_amt_3 = truncate($amount_fee, $prc_amount_3); // format the volume dacimals  

            $Ord3 = $V2_ofs_amt_3; // setup order 3
            if($Ord3 < $order_min_3){ // check order_min_3                          
                $error_min_vol = 1; // set the ERROR to discard the triangle                
                $ko_arb_step_3 = 4; // KO minimum volume
                $info = "recheck_available_arbitrage_step_3($pair_3), Error: 4, the available volume is less than the minimum established";
                echo "\n ".$info;   
                Log_system_error($conn, $info);                            
            }               
        }
    }
    else{
        // KO amount
        $ko_arb_step_3  = 2;            
        $info = "recheck_available_arbitrage_step_3($pair_3), Error: 2, amount = 0, variable setting error. check Arb_Manage_Order()";
        echo "\n ".$info;
        Log_system_error($conn, $info);             
    }           
    $output_step_3 = array($Ord3, $ko_arb_step_3);   
    
return($output_step_3);   
}

// check the arrangements of the pairs
function check_triangle_status($base_1, $quote_1, $base_2, $quote_2, $base_3, $quote_3){
    $status = 0;
    //Status 1   // EURUSD =	Gbpusd	Х	EURGBP
    if(($base_1 == $base_3) && ($quote_1 == $quote_2) && ($base_2 == $quote_3)){
        $status = 1; // operator =  moltiplication        
    }else{
        //Status 2    // EURUSD =	EURGBP	Х	Gbpusd
        if(($base_1 ==  $base_2) && ($quote_1 == $quote_3) && ($quote_2 == $base_3)){
            $status = 2; // operator =  moltiplication
        }else{
            //Status 3    // GBPUSD =	Eurusd	 /	EURGBP
            if(($base_1 ==  $quote_3) && ($quote_1 == $quote_2) && ($base_2 == $base_3)){
                $status = 3; //  $operator = division
            }else{
                //Status 4   // EURGBP =	Eurusd	 /	Gbpusd
                if(($base_1 ==  $base_2) && ($quote_1 == $base_3) && ($quote_2 == $quote_3)){
                    $status = 4; //  $operator = division       
                }
            }
        }
    }
return $status;
}

// add new triangles
function add_new_triangle($base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3){
    //Status 1   // EURUSD =	Gbpusd	Х	EURGBP
    if(($base_1 == $base_3) && ($quote_1 == $quote_2) && ($base_2 == $quote_3)){
        //$status = 1;   //$operator = "*"; // moltiplication
        $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("1", "2", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
        $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("1", "3", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
        $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("1", "4", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3);          
    }else{
        //Status 2     // EURUSD =	EURGBP	Х	Gbpusd
        if(($base_1 ==  $base_2) && ($quote_1 == $quote_3) && ($quote_2 == $base_3)){
            $status = 2;
            //$operator = "*"; // moltiplication
            $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("2", "1", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
            $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("2", "3", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
            $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("2", "4", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3);  
        }else{
            //Status 3  // GBPUSD =	Eurusd	 /	EURGBP
            if(($base_1 ==  $quote_3) && ($quote_1 == $quote_2) && ($base_2 == $base_3)){
                $status = 3;
                //$operator = "/"; // division
                $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("3", "1", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
                $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("3", "2", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
                $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("3", "4", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
            }else{
                //Status 4     // EURGBP =	Eurusd	 /	Gbpusd
                if(($base_1 ==  $base_2) && ($quote_1 == $base_3) && ($quote_2 == $quote_3)){
                    $status = 4;
                    //$operator = "/"; // division
                    $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("4", "1", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
                    $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("4", "2", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
                    $_SESSION["Bin_sess_arr_triangle"][] = build_new_triangle("4", "3", $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3); 
                }
            }
        }
    }
}

// builds the new triangles is called by the add_new_triangle () function
function build_new_triangle($status_start, $status_end, $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3){
    $output = array();
    $status = $status_start.$status_end;
    switch($status) {
        case "12":
            // START Status 1      // EURUSD =	Gbpusd	Х	EURGBP
            // END   Status 2      // EURUSD =	EURGBP	Х	Gbpusd
            $output = array($base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3, $base_2, $pair_2, $quote_2);
            break;
        case "13":
            // START Status 1     // EURUSD =	Gbpusd	Х	EURGBP
            // END  Status 3      // GBPUSD =	Eurusd	 /	EURGBP
            $output = array($base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3);
            break;
        case "14":
            // START Status 1     // EURUSD =	Gbpusd	Х	EURGBP            
            // END   Status 4     // EURGBP =	Eurusd	 /	Gbpusd
            $output = array($base_3, $pair_3, $quote_3, $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2);            
            break;
        case "21":
            // START Status 2     // EURUSD =	EURGBP	Х	Gbpusd
            // END Status 1       // EURUSD =	Gbpusd	Х	EURGBP
            $output = array($base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3, $base_2, $pair_2, $quote_2);
            break;   
        case "23":
            // START Status 2     // EURUSD =	EURGBP	Х	Gbpusd           
            // END  Status 3      // GBPUSD =	Eurusd	 /	EURGBP
            $output = array($base_3, $pair_3, $quote_3, $base_1, $pair_1, $quote_1, $base_2, $pair_2, $quote_2);            
            break; 
        case "24":
            // START Status 2    // EURUSD =	EURGBP	Х	Gbpusd
            // END Status 4      // EURGBP =	Eurusd	 /	Gbpusd
            $output = array($base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3);            
            break; 
        case "31":
            // START Status 3    // GBPUSD =	Eurusd	 /	EURGBP
            // END Status 1      // EURUSD =	Gbpusd	Х	EURGBP
            $output = array($base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3);            
            break;   
        case "32":
            // STARTStatus 3     // GBPUSD =	Eurusd	 /	EURGBP
            // END Status 2      // EURUSD =	EURGBP	Х	Gbpusd
            $output = array($base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3, $base_1, $pair_1, $quote_1);            
            break;   
        case "34":
            // START Status 3    // GBPUSD =	Eurusd	 /	EURGBP
            // END Status 4      // EURGBP =	Eurusd	 /	Gbpusd
            $output = array($base_3, $pair_3, $quote_3, $base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1);            
            break;  
        case "41":
            // START Status 4    // EURGBP =	Eurusd	 /	Gbpusd            
            // END Status 1      // EURUSD =	Gbpusd	Х	EURGBP
            $output = array($base_2, $pair_2, $quote_2, $base_3, $pair_3, $quote_3, $base_1, $pair_1, $quote_1);            
            break;          
        case "42":
            // START Status 4    // EURGBP =	Eurusd	 /	Gbpusd            
            // END Status 2      // EURUSD =	EURGBP	Х	Gbpusd
            $output = array($base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1, $base_3, $pair_3, $quote_3);            
            break; 
        case "43":
            // START Status 4    // EURGBP =	Eurusd	 /	Gbpusd            
            // END Status 3      // GBPUSD =	Eurusd	 /	EURGBP
            $output = array($base_3, $pair_3, $quote_3, $base_2, $pair_2, $quote_2, $base_1, $pair_1, $quote_1);            
            break; 
    }
return $output;
}

function Arb_Refresh_Balance($conn, $Max_Time_Execution, $exchange, $key, $secret, $validate, $file_requestor){
    $_SESSION["Bin_arb_Balance"] = array();
    try {
        if($validate == 1){ // you want to use the demo account:            
            $exchange->set_sandbox_mode(true); // sets the call to the simulation server
        }
        $info = $file_requestor.", Arb_Refresh_Balance()";
        timer_ms4($conn, $Max_Time_Execution, $info); // limit the time to API calls
        $balance = $exchange->fetch_balance();
        foreach ($balance["info"]["balances"] as $id => $val) {
            $_SESSION["Bin_arb_Balance"][$val["asset"]] = $val["free"];	 // assign the name of the currency and the balance of the funds to the session
        }       
    
    } catch (\ccxt\NetworkError $e) {
        $info =  '[Network Error] '.$e->getMessage();
        $info = "Arb_Refresh_Balance(), ".$info;
        echo $info;
        Log_system_error($conn, $info);
    
    } catch (\ccxt\ExchangeError $e) {
        $info =  '[Exchange Error] '.$e->getMessage();
        $info = "Arb_Refresh_Balance(), ".$info;
        echo $info;
        Log_system_error($conn, $info);
    
    } catch (Exception $e) {
        $info =  '[Error] '.$e->getMessage();
        $info = "Arb_Refresh_Balance(), ".$info;
        echo $info;
        Log_system_error($conn, $info);    
    }
    return $_SESSION["Bin_arb_Balance"];
}

function Arb_Manage_Order($conn, $exchange, $key, $secret, $action_to_do, $current_pair, $Volume, $triangle_step, $arr_data, $arr_relation, $validate, $triangle_pairs, $fee, $offset_volume_order, $Max_Time_Execution){       
 
    $Min_Time_Execution = 0.00001; // I cancel waiting times to speed up the order process    
    $max_attempts_orders = 10; // maximum number of loops before activating sleep: $ loop_sleep_time
    $loop_sleep_time = 6; // Seconds of waiting between one loop and another
    $error_loop = 30; // maximum number of loops allowed for each single order
    $Order_Type = "market";    
    $ok_operation = 0; // set the output by setting the failure   
    $volume_filled = 0;
    $trng_status = $arr_relation[10];
 
    switch($triangle_step) {
        case 1: // order for the first pair
            if($validate == 2){
                // TEST TEST TEST
                $response = Arb_add_order_test($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $Volume, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution, $arr_data);                  
            }
            else{
                // execute the order
                $response = Arb_add_order($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $Volume, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution);  
            }
            if((!is_null($response)) && (is_array($response))){ // I check if the order has been successful
                if(isset($response["info"]["status"])){
                    echo "\n Ord.1 status: ".$response["info"]["status"];
                    if(($response["info"]["status"] == "FILLED") && ($response["status"] == "closed") && ($response["remaining"] == 0) && ($response["filled"] > 0)){
                        $ok_operation = 1; // the order has been successful
                        if($action_to_do == "buy"){ // I assign the volume of the destination pair to the variable
                            $volume_filled = $response["amount"];
                        }
                        else{
                            $volume_filled = $response["cost"];
                        }                      
                    }
                    else
                        {
                        $info = "Arb_Manage_Order($current_pair, Status $trng_status, Step 1, $action_to_do, $Order_Type), ERROR: UNKNOW";
                        echo "\n $info";
                        Log_system_error($conn, $info);                             
                    }                                        
                }               
            }
        break;

        case 2: // order for the second pair          
            $x = 0;
            $counter = 0;
            while(($ok_operation == 0) && ($x == 0)){
                if($counter > 0){
                    // starting from the second loop I increase the offset
                    $offset_volume_order = $offset_volume_order + $offset_volume_order;
                }
                // I check the prices again before placing the order
                $output = check_available_arbitrage_step_2($conn, $Min_Time_Execution, $exchange, $key, $secret, $validate, $offset_volume_order, $arr_data, $arr_relation, $Volume, $counter, $fee);
                $vol_ord_2 = $output[0];
                $error_step_2 = $output[1];
                if(($vol_ord_2  > 0) && ($error_step_2 == 0)){ // I check that the recovery of the new volume is correct and that there are no errors.
                    if($validate == 2){
                        // TEST TEST TEST
                        $response = Arb_add_order_test($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $vol_ord_2, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution, $arr_data);                        
                    }
                    else{
                        // I resend the order with the new volume
                        $response = Arb_add_order($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $vol_ord_2, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution);
                    }
                    if((!is_null($response)) && (is_array($response))){ // if it is not set, the network could be gone
                        if(isset($response["info"]["status"])){
                            echo "\n Ord.2 LOOP status: ".$response["info"]["status"];
                            if($response["info"]["status"] == "FILLED"){                                    
                                $ok_operation = 1;
                                if($action_to_do == "buy"){
                                    $volume_filled = $response["amount"]; 
                                }
                                else{
                                    $volume_filled = $response["cost"];
                                }
                            }
                        }               
                    }                                                     
                } 
                else{   
                    if($counter >= $error_loop){ // I have exceeded the maximum number of attempts to block the application 
                        $info = "Arb_Manage_Order($current_pair, Status $trng_status, Step 2, $action_to_do, $Order_Type), ERROR: LOOP > $error_loop";
                        echo "\n $info";
                        Log_system_error($conn, $info);   
                        exit;    
                    }
                    if($error_step_2 != 3){                                                 
                        $x = 1; // I leave the cycle because I can not do anything I have to check by hand what happened
                    }
                    else{ 
                        if($counter >= $max_attempts_orders){
                            sleep($loop_sleep_time); // I have exceeded the maximum number of "fast" attempts, I stay in sleep much longer to avoid the API error
                        } 
                        else{
                            sleep($Min_Time_Execution); // I am within the maximum number of "fast" attempts, I stay in sleep for a very short time
                        }                          
                    }                    
                }
                $counter = $counter + 1;
            }                                    
        break;

        case 3: // order for the third pair
            $x = 0;
            $counter = 0;
            while(($ok_operation == 0) && ($x == 0)){
                if($counter > 0){
                    // starting from the second loop I increase the offset
                    $offset_volume_order = $offset_volume_order + $offset_volume_order;
                }     
                // I check the prices again before placing the order                          
                $output = check_available_arbitrage_step_3($conn, $Min_Time_Execution, $exchange, $key, $secret, $validate, $offset_volume_order, $arr_data, $arr_relation, $Volume, $counter, $fee);                                
                $vol_ord_3 = $output[0];
                $error_step_3 = $output[1];
                if(($vol_ord_3  > 0) && ($error_step_3 == 0)){ // I check that the recovery of the new volume is correct and that there are no errors.
                    if($validate == 2){
                        // TEST TEST TEST
                        $response = Arb_add_order_test($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $vol_ord_3, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution, $arr_data);                        
                    }
                    else{
                        // I resend the order with the new volume
                        $response = Arb_add_order($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $vol_ord_3, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution);                    
                    }
                    if((!is_null($response)) && (is_array($response))){               
                        if(isset($response["info"]["status"])){
                            echo "\n Ord.3 status: ".$response["info"]["status"];
                            if($response["info"]["status"] == "FILLED"){
                                $ok_operation = 1;
                                if($action_to_do == "buy"){
                                    $volume_filled = $response["amount"];
                                }
                                else{
                                    $volume_filled = $response["cost"];
                                }
                            }
                        }               
                    }               
                } 
                else{
                    if($counter >= $error_loop){ // I have exceeded the maximum number of attempts to block the application
                        $info = "Arb_Manage_Order($current_pair, Status $trng_status, Step 3, $action_to_do, $Order_Type), ERROR: LOOP > $error_loop";
                        echo "\n $info";
                        Log_system_error($conn, $info);   
                        exit;    
                    }
                    if($error_step_3 != 3){                                                 
                        $x = 1; // I leave the cycle because I can not do anything I have to check by hand what happened
                    }
                    else{ 
                        if($counter >= $max_attempts_orders){
                            sleep($loop_sleep_time); // I have exceeded the maximum number of "fast" attempts, I stay in sleep much longer to avoid the API error
                        } 
                        else{
                            sleep($Min_Time_Execution); // I am within the maximum number of "fast" attempts, I stay in sleep for a very short time
                        }                          
                    }                  
                }
                $counter = $counter + 1;
            }                                               
        break;
    }
    $output = array($ok_operation, $volume_filled);
return $output;
}

// Simulation of order execution
function Arb_add_order_test($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $Volume, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution, $arr_data){
    $response = null;
    $info = "Arb_add_order($triangle_pairs, $current_pair, Status $trng_status, Step $triangle_step, $action_to_do, $Order_Type, $Volume)";
    timer_ms4($conn, $Min_Time_Execution, $info); 
    $P1 = $arr_data[7];
    $P2 = $arr_data[8];
    $P3 = $arr_data[9];   
    $ord_id = rand();
    $data_a = microtime(true);           
    $amount = $Volume;            
    $filled = $Volume;
    $remaining = 0;
    $status = "closed";
    $status2 = "FILLED";                                  

    switch ($triangle_step) {
        case 1: // Triangle Step 1
            $price = add_remove_rnd($P1);         
            break;
        case 2:  // Triangle Step 2
            $price = add_remove_rnd($P2);
            break;
        case 3:  // Triangle Step 3
            $price = add_remove_rnd($P3);            
            break;
    }    
    $cost = $Volume * $price;

    $response = array("id" => $ord_id, "timestamp" => $data_a, "price" => $price, "amount" => $amount, "cost" => $cost, "filled" => $filled, "remaining" => $remaining, "status" => $status, "info" => Array("status" => $status2));                 
    $info = array($data_a, $ord_id, $current_pair, $triangle_step, $action_to_do, $Order_Type, $price, $amount, $cost, $filled, $remaining, $status, $status2, $triangle_pairs, $trng_status);  
    log_order($conn, $info);
return $response;
}

function add_remove_rnd($input){
    $num = rand(1000, 9999);
    //$x = $num/100000; // 0.0xxxx
    $x = $num/10000; // 0.xxxx
    $x_perc = (($input * $x)/100);
    $value = rand(0,1);
    if($value == 0){    
      $output = $input - $x_perc;
    }
    else{
      $output = $input + $x_perc;
    }
    return $output;
}

function Arb_add_order($conn, $exchange, $key, $secret, $current_pair, $action_to_do, $Order_Type, $Volume, $validate, $triangle_step, $triangle_pairs, $trng_status, $Min_Time_Execution){
    $response = null;
    $info = "Arb_add_order($triangle_pairs, $current_pair, Status $trng_status, Step $triangle_step, $action_to_do, $Order_Type, $Volume)";
    timer_ms4($conn, $Min_Time_Execution, $info); // restricting API calls
    try {
        if($validate == 1){
            $exchange->set_sandbox_mode(true); // sets the call to the test server
        }
        $response = $exchange->create_order($current_pair, $Order_Type, $action_to_do, $Volume);
    
    } catch (\ccxt\NetworkError $e) {
        $info = '[Network Error] '.$e->getMessage ();        
        $info = "Arb_add_order($triangle_pairs, $current_pair, Status $trng_status, Step $triangle_step, $action_to_do, $Order_Type, $Volume), ".$info;
        echo " Error Arb_add_order() ";
        Log_system_error($conn, $info);
    } catch (\ccxt\ExchangeError $e) {
        $info = '[Exchange Error] ' . $e->getMessage();
        $info = "\n "."Arb_add_order($triangle_pairs, $current_pair, Status $trng_status, Step $triangle_step, $action_to_do, $Order_Type, $Volume), ".$info;
        echo " Error Arb_add_order() ";
        Log_system_error($conn, $info);      
    } catch (Exception $e) {
        $info = '[Error] ' . $e->getMessage();
        $info = "\n "."Arb_add_order($triangle_pairs, $current_pair, Status $trng_status, Step $triangle_step, $action_to_do, $Order_Type, $Volume), ".$info;
        echo " Error Arb_add_order() ";
        Log_system_error($conn, $info);
    }    
    $data_a = 0;
    $ord_id = 0;
    $price = 0;
    $amount = 0;
    $cost = 0;
    $filled = 0;
    $remaining = 0;
    $status = "";
    $status2 = "";    
    if((!is_null($response)) && (is_array($response))){ // i just fetch the order information
        if(isset($response["info"]["status"])){
            $ord_id = $response["id"];
            $data_a = $response["timestamp"];
            $price = $response["price"];
            $amount = $response["amount"];
            $cost = $response["cost"];
            $filled = $response["filled"];
            $remaining = $response["remaining"];
            $status = $response["status"];
            $status2 = $response["info"]["status"];
        }               
    } 
    $info = array($data_a, $ord_id, $current_pair, $triangle_step, $action_to_do, $Order_Type, $price, $amount, $cost, $filled, $remaining, $status, $status2, $triangle_pairs, $trng_status);  
    log_order($conn, $info); // I insert the data in the db
return $response;
}

function truncate($number, $precision) {
    $shift = pow(10, $precision);  
    $output = (intval($number * $shift)/$shift);
    return $output;
}

//************************************************/
// MYSQL FUNCTION
//************************************************/
// I catch the errors and log them in the db table arb_log_errors
function Log_system_error($conn, $err_msg){
    $time = microtime(true);
	$data_a = convert_data_unix_ms($time);
    $err_msg = addslashes($err_msg); 
    $dati_sql = "('".$data_a."', '".$err_msg."')";	
    $sql = "INSERT INTO arb_log_errors (data_a, info) VALUES ".$dati_sql;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = "ERROR: " . $sql . " " . $conn->error;
        Log_system_error_to_file($info);   
    }   
}

function Log_balance($conn, $info){ // record the wallet data
    $time = microtime(true);
	$data_a = convert_data_unix_ms($time);
    $info = addslashes($info); 
    $dati_sql = "('".$data_a."', '".$info."')";	
    $sql = "INSERT INTO arb_log_balance (data_a, info) VALUES ".$dati_sql;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $err_msg = "Log_balance(), ERROR: " . $sql . " " . $conn->error;
        Log_system_error($conn, $err_msg);   
    }   
}

function log_order($conn, $info){
    // array(0:$data_a, 1:$ord_id, 2:$current_pair, 3:$triangle_step, 4:$side, 5:$Order_Type, 6:$price, 7:$amount, 8:$cost, 9:$filled, 10:$remaining, 11:$status, 12:$status2, 13:$triangle_pairs, 14:$trng_status)
    //$data_a = convert_data_unix_ms($data_a); the date provided by the server does not seem correct
    $time = microtime(true);
	$data_a = convert_data_unix_ms($time);
    $dati_sql = "('".$data_a."', '".$info[13]."',  '".$info[14]."', '".$info[1]."' ,'".$info[2]."','".$info[3]."','".$info[4]."','".$info[5]."','".$info[6]."',
                            '".$info[7]."', '".$info[8]."','".$info[9]."','".$info[10]."','".$info[11]."','".$info[12]."')";	
    $sql = "INSERT INTO arb_log_orders (data_a, triangle, trng_status, ord_id,	pair, step,	side, order_type, price, amount, cost, filled, remaining, status, status2)  VALUES ".$dati_sql;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = "log_order(): ERROR: " . $sql . " " . $conn->error;
        Log_system_error($conn, $info);   
    }
}

function log_timer_ms4($conn, $data_a, $requestor, $time_set, $time_left){
    $requestor = addslashes($requestor); // evito errori per gli apici
    $dati_sql = "('".$data_a."', '".$requestor."', '".$time_set."', '".$time_left."')";	
    $sql = "INSERT INTO arb_log_timer_ms4 (data_a, requestor, time_set, time_left)  VALUES ".$dati_sql;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = $requestor.", ERROR: " . $sql . "\n " . $conn->error;
        Log_system_error($conn, $info);   
    }
}

function log_best_candidate($conn, $best_candidate){
    // 0:$V1, 1:$V2, 2:$V3, 3:$diff_vol, 4:$status, 5:$pair_1, 6:$pair_2, 7:$pair_3, 
    // 8:$P1, 9:$P2, 10:$P3, 11:$str, 12:$P2_A_P3, 13:$diff_prz, 14:$str_log_min_trans, 15:$str_log_vol_check
    $time = microtime(true);
    $data = convert_data_unix_ms($time);
    $dati_sql = "('".$data."', '".$best_candidate[0]."', '".$best_candidate[1]."', '".$best_candidate[2]."', '".$best_candidate[3]."', '".$best_candidate[4]."',
                 '".$best_candidate[5]."', '".$best_candidate[6]."', '".$best_candidate[7]."', '".$best_candidate[8]."', '".$best_candidate[9]."', '".$best_candidate[10]."',
                 '".$best_candidate[11]."', '".$best_candidate[12]."', '".$best_candidate[13]."', '".$best_candidate[14]."', '".$best_candidate[15]."')";
    $sql = "INSERT INTO arb_log_best_candidate (data_bc, ord_vol_1, ord_vol_2, ord_vol_3, diff_vol, status, pair_1, pair_2, pair_3,
                         pair_price_1, pair_price_2, pair_price_3, str, x_2_3, diff_prz, min_trans, vol_check)  VALUES ".$dati_sql;
    if(!$conn->query($sql) === TRUE) {            
        echo "\n Error: " . $sql . "\n " . $conn->error;  
        $info = "ERROR: " . $sql . " " . $conn->error;
        Log_system_error($conn, $info);   
    }
}

//************************************************/
// LOG FILES
//************************************************/
function clear_log($nome_file){
	$log = fopen($nome_file,"w");
	fclose($log);
	chmod($nome_file, 0600);
return true;
}

function appendi_log($nome_file, $info){
	$log = fopen($nome_file,"a");
	fwrite($log, $info);
	fclose($log);
	//chmod($nome_file, 0600);
	/*
	r	Open a file for read only. File pointer starts at the beginning of the file
	w	Open a file for write only. Erases the contents of the file or creates a new file if it doesn't exist. File pointer starts at the beginning of the file
	a	Open a file for write only. The existing data in file is preserved. File pointer starts at the end of the file. Creates a new file if the file doesn't exist
	x	Creates a new file for write only. Returns FALSE and an error if file already exists
	r+	Open a file for read/write. File pointer starts at the beginning of the file
	w+	Open a file for read/write. Erases the contents of the file or creates a new file if it doesn't exist. File pointer starts at the beginning of the file
	a+	Open a file for read/write. The existing data in file is preserved. File pointer starts at the end of the file. Creates a new file if the file doesn't exist
	x+	Creates a new file for read/write. Returns FALSE and an error if file already exists
	*/
	return true;
}

// I catch errors and log them in the log file
function Log_system_error_to_file($requestor){
	$error_log="Log_system_error.txt";
	$time = microtime(true);
	$info = convert_data_unix_ms($time).", ";
	$info = $info.$requestor." \n";
	appendi_log($error_log, $info); 
}

//************************************************/
// TIME FUNCTION
//************************************************/
function Check_time() { // returns the current time including milliseconds
	$mt = microtime(true);
	$pieces_ux = explode(".", $mt);
	$pieces_ux[0]; // date hour minutes seconds
	$pieces_ux[1]; // milliseconds
	$new_date = new DateTime( date("Y-m-d H:i:s.".$pieces_ux[1], $pieces_ux[0]) );		
	$formatted_date = $new_date->format("H:i:s.u");	
	return $formatted_date;
}

function time_diff_now_date_sec($data) { // get the date and time and subtract it from today's date
	$date2 = new DateTime($data);
	$diff = time() - $date2->getTimestamp();
	return $diff;
}

function convert_data_unix_ms($dataunix){
    $pos = strpos($dataunix, ".");
    if ($pos !== false) {
        $pieces_ux = explode(".", $dataunix);
        $pieces_ux[0]; // date hour minutes seconds
        $pieces_ux[1]; // milliseconds
        $new_date = new DateTime( date("Y-m-d H:i:s.".$pieces_ux[1], $pieces_ux[0]) );
    }
    else{
        $new_date = new DateTime( date("Y-m-d H:i:s.u", $dataunix) );
    }	
	$data = $new_date->format("Y-m-d H:i:s.u");	
return $data;
}

function get_date_diff_ms($date1, $date2) {
	$d1 = new DateTime($date1);
	$new_d1 = $d1->format('U.u');
	$d2 = new DateTime($date2);
	$new_d2 = $d2->format('U.u');
	$diff = abs($new_d1 - $new_d2);
	return $diff;
}

function Get_Current_Date_mysql(){
	$new_date = new DateTime( date("Y-m-d H:i:s", time()) );	
	$data = $new_date->format("Y-m-d H:i:s");
	return $data;	
}

// check that the time limit is respected
// otherwise it puts the script execution to sleep
function timer_ms4($conn, $time_limit, $requestor) {
    $ok = false;
	$output = null;
    $time_inizio = microtime(true);
	$data_a = convert_data_unix_ms($time_inizio);        
    if(isset($_SESSION['Bin_arb_time_chk'])){
    	$timeleft = microtime(true) - $_SESSION['Bin_arb_time_chk']; // the last request made	 	
		if($timeleft >= $time_limit){					
			$ok = true; // the time limit has elapsed I can make the request to the API
		}
		else{			
			$sleep_before = microtime(true);				
			$sec_diff = ($time_limit - $timeleft)*1000000; //  put the process on standby for the time it takes to reach the time limit
			usleep($sec_diff);				
			$sleep_after = microtime(true);
			$avg_sleep = abs($sleep_after - $sleep_before);
			$ok = true;	
		}
	    $timeleft = microtime(true) - $_SESSION['Bin_arb_time_chk'];				
    }
	else { // the session file does not exist so no requests were made
		$ok = true;
		$timeleft = 0;
	}		
	$_SESSION['Bin_arb_time_chk'] = microtime(true);
	log_timer_ms4($conn, $data_a, $requestor, $time_limit, $timeleft);		
	return $output;	
}
?>