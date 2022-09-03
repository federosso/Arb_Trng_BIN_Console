<?php
require_once dirname(__FILE__) . '/../inc_config.php';

// php ../htdocs/Arb_Trng_BIN_Console/test/test_tiker_single_pair.php

try {
    
    $exchange = new $exchange_id (array (
        'timeout' => 30000,  
        'enableRateLimit' => true,  
    ));

    $symbol = 'ETH/BTC';
    $result = $exchange->fetch_ticker($symbol);
    //echo "\n result:\n".print_r($result);
              
    $pair_name = $result["symbol"];
    $bid = $result["bid"]; 
    $bidVolume = $result["bidVolume"]; 
    $ask = $result["ask"]; 
    $askVolume = $result["askVolume"]; 
    $count = $result["info"]["count"];
    
    echo "\n $pair_name: ask: $ask, askVolume: $askVolume, bid: $bid, bidVolume: $bidVolume, count: $count";


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
    [symbol] => ETH/BTC
    [timestamp] => 1612958218641
    [datetime] => 2021-02-10T11:56:58.641Z
    [high] => 0.0398
    [low] => 0.036942
    [bid] => 0.038466
    [bidVolume] => 40.048
    [ask] => 0.038467
    [askVolume] => 15.468
    [vwap] => 0.03823498
    [open] => 0.037729
    [close] => 0.038466
    [last] => 0.038466
    [previousClose] => 0.03773
    [change] => 0.000737
    [percentage] => 1.953
    [average] => 
    [baseVolume] => 479148.343
    [quoteVolume] => 18320.22845112
    [info] => Array
        (
            [symbol] => ETHBTC
            [priceChange] => 0.00073700
            [priceChangePercent] => 1.953
            [weightedAvgPrice] => 0.03823498
            [prevClosePrice] => 0.03773000
            [lastPrice] => 0.03846600
            [lastQty] => 0.12700000
            [bidPrice] => 0.03846600
            [bidQty] => 40.04800000
            [askPrice] => 0.03846700
            [askQty] => 15.46800000
            [openPrice] => 0.03772900
            [highPrice] => 0.03980000
            [lowPrice] => 0.03694200
            [volume] => 479148.34300000
            [quoteVolume] => 18320.22845112
            [openTime] => 1612871818641
            [closeTime] => 1612958218641
            [firstId] => 228965388
            [lastId] => 229490401
            [count] => 525014
        )

)
*/

?>