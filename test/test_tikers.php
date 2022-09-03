<?php
require_once dirname(__FILE__) . '/../inc_config.php';

// php ../htdocs/Arb_Trng_BIN_Console/test/test_tikers.php

try {
    $exchange = new $exchange_id (array (      
        'verbose' => false,
        'timeout' => 30000,
        'enableRateLimit' => true,
    ));
    
    // WARNING !!!
    // DO NOT CALL THIS MORE THAN ONCE IN 2 MINUTES OR YOU WILL GET BANNED BY BINANCE!
    // https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md#limits

    $result = $exchange->fetch_tickers();
    //print_r ($result);
    
    foreach ($result as $pair_name => $arr_val) {

        $bid = $arr_val["bid"]; // prezzo bid (offerta) (prezzo di vendita)
        $bidVolume = $arr_val["bidVolume"]; // volume lotto relativo al prezzo di  BID	             
        $ask = $arr_val["ask"]; // prezzo ask (domanda) (prezzo di acquisto)
        $askVolume = $arr_val["askVolume"]; // volume lotto relativo al prezzo di ASK
        $count = $arr_val["info"]["count"];// numero di transazioni nelle ultime 24 ore 
        
        echo "\n $pair_name: ask: $ask, askVolume: $askVolume, bid: $bid, bidVolume: $bidVolume, count: $count";

    }


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
    [ETH/BTC] => Array
        (
            [symbol] => ETH/BTC
            [timestamp] => 1611688582854
            [datetime] => 2021-01-26T19:16:22.854Z
            [high] => 0.0423
            [low] => 0.040074
            [bid] => 0.041766
            [bidVolume] => 31.641
            [ask] => 0.041767
            [askVolume] => 3.6
            [vwap] => 0.04119944
            [open] => 0.040994
            [close] => 0.041766
            [last] => 0.041766
            [previousClose] => 0.040989
            [change] => 0.000772
            [percentage] => 1.883
            [average] => 
            [baseVolume] => 426684.173
            [quoteVolume] => 17579.14825428
            [info] => Array
                (
                    [symbol] => ETHBTC
                    [priceChange] => 0.00077200
                    [priceChangePercent] => 1.883
                    [weightedAvgPrice] => 0.04119944
                    [prevClosePrice] => 0.04098900
                    [lastPrice] => 0.04176600
                    [lastQty] => 0.08600000
                    [bidPrice] => 0.04176600
                    [bidQty] => 31.64100000
                    [askPrice] => 0.04176700
                    [askQty] => 3.60000000
                    [openPrice] => 0.04099400
                    [highPrice] => 0.04230000
                    [lowPrice] => 0.04007400
                    [volume] => 426684.17300000
                    [quoteVolume] => 17579.14825428
                    [openTime] => 1611602182854
                    [closeTime] => 1611688582854
                    [firstId] => 221099882
                    [lastId] => 221590412
                    [count] => 490531
                )

        )

    [LTC/BTC] => Array
        (

*/


?>