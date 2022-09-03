<?php
// php ../htdocs/Arb_Trng_BIN_Console/test/test_balance.php

require_once dirname(__FILE__) . '/../inc_config.php';
require_once dirname(__FILE__) . '/../inc_arb_func.php';


$file_requestor = "test_balance.php";

$info = $file_requestor." call: fetch_balance()";

try {
    //print_r (new $exchange_id ()); // PHP
    //exit;

    $exchange = new $exchange_id (array (
        'apiKey' => $key, // ←------------ replace with your keys
        'secret' => $secret,   
    ));

    // if you want to use the demo account:
    //$exchange->set_sandbox_mode(true); // sets the call to the Binance test server
    // If you use set_sandbox_mode you have to set $ validate = 1; in the configuration file inc_config.phpinc_config.php
    // otherwise you will get this error: [Exchange Error] binance {"code": - 2015, "msg": "Invalid API-key, IP, or permissions for action."}

    timer_ms4($conn, $Max_Time_Execution, $info); // I limit API calls

    $balance = $exchange->fetch_balance();
    //print_r ($balance);

    foreach ($balance["info"]["balances"] as $id => $val) {

        echo "\n".$val["asset"].": ".$val["free"];
    }


    echo "\n"; 

} catch (\ccxt\NetworkError $e) {
    echo '[Network Error] ' . $e->getMessage () . "\n";
} catch (\ccxt\ExchangeError $e) {
    echo '[Exchange Error] ' . $e->getMessage () . "\n";
} catch (Exception $e) {
    echo '[Error] ' . $e->getMessage () . "\n";
}


/*
Array
(
    [info] => Array
        (
            [makerCommission] => 0
            [takerCommission] => 0
            [buyerCommission] => 0
            [sellerCommission] => 0
            [canTrade] => 1
            [canWithdraw] => 
            [canDeposit] => 
            [updateTime] => 1610561780992
            [accountType] => SPOT
            [balances] => Array
                (
                    [0] => Array
                        (
                            [asset] => BNB
                            [free] => 1000.00000000
                            [locked] => 0.00000000
                        )

                    [1] => Array
                        (
                            [asset] => BTC
                            [free] => 1.00000000
                            [locked] => 0.00000000
                        )

                    [2] => Array
                        (
                            [asset] => BUSD
                            [free] => 10000.00000000
                            [locked] => 0.00000000
                        )

                    [3] => Array
                        (
                            [asset] => ETH
                            [free] => 100.00000000
                            [locked] => 0.00000000
                        )

                    [4] => Array
                        (
                            [asset] => LTC
                            [free] => 500.00000000
                            [locked] => 0.00000000
                        )

                    [5] => Array
                        (
                            [asset] => TRX
                            [free] => 500000.00000000
                            [locked] => 0.00000000
                        )

                    [6] => Array
                        (
                            [asset] => USDT
                            [free] => 10000.00000000
                            [locked] => 0.00000000
                        )

                    [7] => Array
                        (
                            [asset] => XRP
                            [free] => 50000.00000000
                            [locked] => 0.00000000
                        )

                )

            [permissions] => Array
                (
                    [0] => SPOT
                )

        )

    [BNB] => Array
        (
            [free] => 1000
            [used] => 0
            [total] => 1000
        )

    [BTC] => Array
        (
            [free] => 1
            [used] => 0
            [total] => 1
        )

    [BUSD] => Array
        (
            [free] => 10000
            [used] => 0
            [total] => 10000
        )

    [ETH] => Array
        (
            [free] => 100
            [used] => 0
            [total] => 100
        )

    [LTC] => Array
        (
            [free] => 500
            [used] => 0
            [total] => 500
        )

    [TRX] => Array
        (
            [free] => 500000
            [used] => 0
            [total] => 500000
        )

    [USDT] => Array
        (
            [free] => 10000
            [used] => 0
            [total] => 10000
        )

    [XRP] => Array
        (
            [free] => 50000
            [used] => 0
            [total] => 50000
        )

    [free] => Array
        (
            [BNB] => 1000
            [BTC] => 1
            [BUSD] => 10000
            [ETH] => 100
            [LTC] => 500
            [TRX] => 500000
            [USDT] => 10000
            [XRP] => 50000
        )

    [used] => Array
        (
            [BNB] => 0
            [BTC] => 0
            [BUSD] => 0
            [ETH] => 0
            [LTC] => 0
            [TRX] => 0
            [USDT] => 0
            [XRP] => 0
        )

    [total] => Array
        (
            [BNB] => 1000
            [BTC] => 1
            [BUSD] => 10000
            [ETH] => 100
            [LTC] => 500
            [TRX] => 500000
            [USDT] => 10000
            [XRP] => 50000
        )

)
*/
?>