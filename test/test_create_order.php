<?php
require_once dirname(__FILE__) . '/../inc_config.php';
require_once dirname(__FILE__) . '/../inc_arb_func.php';

// php ../htdocs/Arb_Trng_BIN_Console/test/test_create_order.php

$file_requestor = "test_create_order.php";
$info = $file_requestor." call: create_order()";

try {

    $exchange = new $exchange_id (array (
        'apiKey' => $key, 
        'secret' => $secret, 
        'enableRateLimit' => true, // https://github.com/ccxt/ccxt/wiki/Manual#rate-limi      
    ));

    // If you use set_sandbox_mode you have to set $ validate = 1; in the configuration file inc_config.phpinc_config.php
    // otherwise you will get this error: [Exchange Error] binance {"code": - 2015, "msg": "Invalid API-key, IP, or permissions for action."}
    $exchange->set_sandbox_mode(true); // sets up the call to the test server

    
    $symbol = 'BTC/USDT';
    $Order_Type = 'market'; // # or 'market', or 'Stop' or 'StopLimit'
    //$side = 'sell'; // or 'buy'    
    $side = 'buy';
    $amount = 0.007;
    $order = $exchange->create_order($symbol, $Order_Type, $side, $amount); // MARKET ORDER

    echo "\n "; 
    
    echo "\n orderId: ".$order["id"];
    echo "\n transactTime: ".$order["timestamp"];
    echo "\n symbol: ".$order["symbol"];
    echo "\n type: ".$order["type"];
    echo "\n side: ".$order["side"];
    echo "\n price: ".$order["price"];
    echo "\n amount: ".$order["amount"];
    //echo "\n executedQty: ".$order["info"]["executedQty"];
    echo "\n cost: ".$order["cost"];
    echo "\n average: ".$order["average"];
    echo "\n filled: ".$order["filled"];
    echo "\n remaining: ".$order["remaining"];
    echo "\n status: ".$order["status"];
    echo "\n status2: ".$order["info"]["status"];
    
    $ord_id = $order["id"];
    $data_a = $order["timestamp"];
    $current_pair = $order["symbol"];
    //echo "\n type: ".$response["type"];
    //echo "\n side: ".$response["side"];
    $price = $order["price"];
    $amount = $order["amount"];
    //echo "\n executedQty: ".$response["info"]["executedQty"];
    $cost = $order["cost"];
    //echo "\n average: ".$response["average"];
    $filled = $order["filled"];
    $remaining = $order["remaining"];
    $status = $order["status"];
    $status2 = $order["info"]["status"];
    $triangle_step = 0;
    $triangle = $current_pair." xxx yyy";
    $trng_status = 0;

    $info = array($data_a, $ord_id, $current_pair, $triangle_step, $side, $Order_Type, $price, $amount, $cost, $filled, $remaining, $status, $status2, $triangle, $trng_status);  

    log_order($conn, $info); // log the order to mysql

    //print_r ($order);

    $_SESSION["Bin_arb_Balance"] = null; // force the balance update
    

} catch (\ccxt\NetworkError $e) {
    echo '[Network Error] ' . $e->getMessage () . "\n ";
} catch (\ccxt\ExchangeError $e) {
    echo '[Exchange Error] ' . $e->getMessage () . "\n ";
} catch (Exception $e) {
    echo '[Error] ' . $e->getMessage () . "\n ";
}




/*

se un ordine di tipo limit viene eseguito il suo status è : NEW

$symbol = 'BNB/USDT';
$type = 'market'; 
$side = 'buy';
$amount = 0.65;

OUTPUT ORDINE:
(
    [info] => Array
        (
            [symbol] => BNBUSDT
            [orderId] => 2642716
            [orderListId] => -1
            [clientOrderId] => MqIC38t8UqoDpzZMycWhVw
            [transactTime] => 1613351473599
            [price] => 0.00000000
            [origQty] => 0.65000000
            [executedQty] => 0.65000000
            [cummulativeQuoteQty] => 86.98889600
            [status] => FILLED
            [timeInForce] => GTC
            [type] => MARKET
            [side] => BUY
            [fills] => Array
                (
                    [0] => Array
                        (
                            [price] => 133.78080000
                            [qty] => 0.12000000
                            [commission] => 0.00000000
                            [commissionAsset] => BNB
                            [tradeId] => 153372
                        )

                    [1] => Array
                        (
                            [price] => 133.84000000
                            [qty] => 0.53000000
                            [commission] => 0.00000000
                            [commissionAsset] => BNB
                            [tradeId] => 153373
                        )

                )

        )

    [id] => 2642716
    [clientOrderId] => MqIC38t8UqoDpzZMycWhVw
    [timestamp] => 1613351473599
    [datetime] => 2021-02-15T01:11:13.599Z
    [lastTradeTimestamp] => 
    [symbol] => BNB/USDT
    [type] => market
    [timeInForce] => GTC
    [postOnly] => 
    [side] => buy
    [price] => 133.82907076923
    [stopPrice] => 
    [amount] => 0.65
    [cost] => 86.988896
    [average] => 133.82907076923
    [filled] => 0.65
    [remaining] => 0
    [status] => closed
    [fee] => Array
        (
            [cost] => 0
            [currency] => BNB
        )

    [trades] => Array
        (
            [0] => Array
                (
                    [info] => Array
                        (
                            [price] => 133.78080000
                            [qty] => 0.12000000
                            [commission] => 0.00000000
                            [commissionAsset] => BNB
                            [tradeId] => 153372
                        )

                    [timestamp] => 
                    [datetime] => 
                    [symbol] => BNB/USDT
                    [id] => 
                    [order] => 
                    [type] => 
                    [side] => 
                    [takerOrMaker] => 
                    [price] => 133.7808
                    [amount] => 0.12
                    [cost] => 16.053696
                    [fee] => Array
                        (
                            [cost] => 0
                            [currency] => BNB
                        )

                )

            [1] => Array
                (
                    [info] => Array
                        (
                            [price] => 133.84000000
                            [qty] => 0.53000000
                            [commission] => 0.00000000
                            [commissionAsset] => BNB
                            [tradeId] => 153373
                        )

                    [timestamp] => 
                    [datetime] => 
                    [symbol] => BNB/USDT
                    [id] => 
                    [order] => 
                    [type] => 
                    [side] => 
                    [takerOrMaker] => 
                    [price] => 133.84
                    [amount] => 0.53
                    [cost] => 70.9352
                    [fee] => Array
                        (
                            [cost] => 0
                            [currency] => BNB
                        )

                )

        )

)





$symbol = 'BTC/USDT';
$Order_Type = 'market';
$side = 'sell';
$amount = 0.011;

(
    [info] => Array
        (
            [symbol] => BTCUSDT
            [orderId] => 70346
            [orderListId] => -1
            [clientOrderId] => uJwrtRYqkDjZIydxOciScS
            [transactTime] => 1613561331835
            [price] => 0.00000000
            [origQty] => 0.01100000
            [executedQty] => 0.01100000
            [cummulativeQuoteQty] => 528.00000000
            [status] => FILLED
            [timeInForce] => GTC
            [type] => MARKET
            [side] => SELL
            [fills] => Array
                (
                    [0] => Array
                        (
                            [price] => 48000.00000000
                            [qty] => 0.01100000
                            [commission] => 0.00000000
                            [commissionAsset] => USDT
                            [tradeId] => 4145
                        )

                )

        )

    [id] => 70346
    [clientOrderId] => uJwrtRYqkDjZIydxOciScS
    [timestamp] => 1613561331835
    [datetime] => 2021-02-17T11:28:51.835Z
    [lastTradeTimestamp] => 
    [symbol] => BTC/USDT
    [type] => market
    [timeInForce] => GTC
    [postOnly] => 
    [side] => sell
    [price] => 48000
    [stopPrice] => 
    [amount] => 0.011
    [cost] => 528
    [average] => 48000
    [filled] => 0.011
    [remaining] => 0
    [status] => closed
    [fee] => Array
        (
            [cost] => 0
            [currency] => USDT
        )

    [trades] => Array
        (
            [0] => Array
                (
                    [info] => Array
                        (
                            [price] => 48000.00000000
                            [qty] => 0.01100000
                            [commission] => 0.00000000
                            [commissionAsset] => USDT
                            [tradeId] => 4145
                        )

                    [timestamp] => 
                    [datetime] => 
                    [symbol] => BTC/USDT
                    [id] => 
                    [order] => 
                    [type] => 
                    [side] => 
                    [takerOrMaker] => 
                    [price] => 48000
                    [amount] => 0.011
                    [cost] => 528
                    [fee] => Array
                        (
                            [cost] => 0
                            [currency] => USDT
                        )

                )

        )

)




*/

?>