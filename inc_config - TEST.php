<?php
session_start(); // initialize session
date_default_timezone_set("Europe/Rome"); // time zone
//date_default_timezone_set("UTC"); // time zone
set_time_limit(0); //no time limits for the scriprt

//**************************************************************************************************************** */
// CURRENCIES IN THE PORTFOLIO TO BE USED FOR ARBITRAGE
//**************************************************************************************************************** */
// suggest: check the Minimum Order Size: https://www.binance.com/en/trade-rule
//$my_coin_list = array("USDT" => 49); 
$my_coin_list = array("USDT"=>100, "BTC"=>0.01, "ETH"=>0.1, "XRP"=>150); // currency => amount
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// SIMULATED BALANCE
//**************************************************************************************************************** */
//$Sim_Bal = 0; // Disabled (Warning use real balance)
$Sim_Bal = 1; // Enabled (use the data from $my_coin_list as a simulation of the funds) 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// ORDER SETUP
//**************************************************************************************************************** */
//$validate = 0; // !!!! CAUTION !!!! EXECUTE REAL ORDERS !!!! CAUTION !!!!
//$validate = 1; // use the Binance testnet server. In the testnet server there are not all the coins so there may not be triangles to operate on
$validate = 2; // use real data but orders are simulated (the output is only for tests to run the program)
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// SETUP MySQL
//**************************************************************************************************************** */
$S_MySQL = 0; // LOCAL - Local Mysql Server Configuration
// $S_MySQL = 1; // REMOTE - Remote Mysql Server Configuration 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// INTERVAL BETWEEN ONE API CALL AND THE OTHER
//**************************************************************************************************************** */
//it is not recommended to lower the waiting time in order not to incur the blockage of API
$Max_Time_Execution = 5; // seconds and milliseconds - minimum script execution time.
// RATE LIMITS
// https://github.com/binance/binance-spot-api-docs/blob/master/rest-api.md#limits
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// COSTS FOR EVERY ORDER (FEES)
//**************************************************************************************************************** */
$fee = 0.1/100; //0.1% exchance fee taker;
//$fee = 0.075/100; // 0.075% IF YOU USE BNB TO PAY FEE
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// MINIMUM PERCENTAGE PROFIT FOR THE EXECUTION OF THE ARBITRATION
//**************************************************************************************************************** */
// the comparison with this value is done with the diff_vol field in the arb_log_best_candidate table
$min_profit = 0.1; // ABOVE THIS VALUE ENTERS THE MARKET (very high risk of losing money)
//$min_profit = 2; // ABOVE THIS VALUE ENTERS THE MARKET (real chance of profit but it rarely happens)
//$min_profit = 1000; // ABOVE THIS VALUE ENTERS THE MARKET (will never do 1000% ^_^ good for testing)
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// REDUCE EVERY ORDER TO HAVE A PRICE RANGE
//**************************************************************************************************************** */
// percentage of order reduction calculated on equity. 
// (useful for managing price fluctuations without the order being canceled due to lack of funds.)
$offset_volume_order = 0.02; 
//$offset_volume_order = 0.05; 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// MINIMUM TRANSACTIONS TAKEN IN THE LAST 24 HOURS
//**************************************************************************************************************** */
// necessary in order not to enter markets where there is no liquidity
//$min_trans = 1000; 
$min_trans = 100; 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// CHECK IF THE PRICE VOLUME OF ASK OR BID IS GREATER THAN THE VOLUME OF MY OPERATION
//**************************************************************************************************************** */
// it enters the market only if the entire operation can be executed with the first ask / bid volume
//$vol_ask_bid_check = true; // volume control Enabled
$vol_ask_bid_check = false; // volume control Disabled
// many profitable trades actually have a minimum volume at a competitive price so I close the trade at a loss
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// API KEY
//**************************************************************************************************************** */
if($validate == 1){ 
    // API KEYS Binance Spot Test Network:    
    // https://testnet.binance.vision/
    $key = 'INSERT YOUR TESTNET KEY'; 
    $secret = 'INSERT YOUR TESTNET SECRET';        
}else{     
    // REAL API KEYS:    
    $key = 'INSERT YOUR TESTNET KEY'; 
    $secret = 'INSERT YOUR TESTNET SECRET';  
}
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// CCXT CONFIG
//**************************************************************************************************************** */
include "ccxt/ccxt.php"; //  ccxt.php PATH
$exchange_id = '\\ccxt\\binance'; // EXCHANGE ON WHICH TO OPERATE
$exchange = new $exchange_id (array (              
    'verbose' => false,
    'timeout' => 30000, 
    'apiKey' => $key,
    'secret' => $secret          
)); 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// CONNECTION TO MYSQL
//**************************************************************************************************************** */
if($S_MySQL){ // REMOTE MYSQL SERVER
    $servername = "";           // INSERT MYSQL SERVER NAME
    $username = "";             // INSERT MYSQL USERNAME
    $password = "";             // INSERT MYSQL PASSWORD
    $dbname = "";               // INSERT MYSQL DATABASE NAME
}
else{ // LOOCAL MYSQL SERVER
    $servername = "";           // INSERT MYSQL SERVER NAME
    $username = "";             // INSERT MYSQL USERNAME
    $password = "";             // INSERT MYSQL PASSWORD
    $dbname = "";               // INSERT MYSQL DATABASE NAME
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("file: inc_config.php: MYSQL Connection failed: ".$conn->connect_error);
} 
//**************************************************************************************************************** */

//**************************************************************************************************************** */
// ORDER MANAGEMENT
//**************************************************************************************************************** */
// Additional order configuration parameters:
// File: inc_arb_func.php function: Arb_add_order()
//**************************************************************************************************************** */
?>