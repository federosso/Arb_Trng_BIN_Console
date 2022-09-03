<?php
require_once dirname(__FILE__) . '/../inc_config.php';

// php ../htdocs/Arb_Trng_BIN_Console/test/test_load_markets.php

try {

    $exchange = new $exchange_id (array (
        'verbose' => false,
        'timeout' => 30000,
        'enableRateLimit' => true, // I limit API calls
    ));

    $markets = $exchange->load_markets(true); // return a locally cached version, no reload
    //print_r ($markets);

    $x = 0;    
    echo "<br>B: base, Q: quote, A: amount, P: price";
    foreach ($markets as $arr_key => $arr_val) {

        // I only select the pairs on which it is possible to trade
        if(($arr_val["info"]["status"] == "TRADING")&&( $arr_val["spot"] = 1)){
            
            echo "\n $x) ".$arr_key.", ".$arr_val["base"].", ".$arr_val["quote"].", B:".$arr_val["precision"]["base"].", Q:".$arr_val["precision"]["quote"].", A:".$arr_val["precision"]["amount"].", P:".$arr_val["precision"]["price"];            
        }
        $x = $x +1;
    }

} catch (\ccxt\NetworkError $e) {
    echo '[Network Error] ' . $e->getMessage () . "\n";
} catch (\ccxt\ExchangeError $e) {
    echo '[Exchange Error] ' . $e->getMessage () . "\n";
} catch (Exception $e) {
    echo '[Error] ' . $e->getMessage () . "\n";
}



/*
markets:

    [BTC/USDT] => Array
        (
            [tierBased] => 
            [percentage] => 1
            [taker] => 0.001
            [maker] => 0.001
            [precision] => Array
                (
                    [base] => 8
                    [quote] => 8
                    [amount] => 6
                    [price] => 2
                )

            [limits] => Array
                (
                    [cost] => Array
                        (
                            [min] => 10
                            [max] => 
                        )

                    [price] => Array
                        (
                            [min] => 0.01
                            [max] => 1000000
                        )

                    [amount] => Array
                        (
                            [min] => 1.0E-6
                            [max] => 9000
                        )

                    [market] => Array
                        (
                            [min] => 0
                            [max] => 126.95462941
                        )

                )

            [id] => BTCUSDT
            [lowercaseId] => btcusdt
            [symbol] => BTC/USDT
            [base] => BTC
            [quote] => USDT
            [baseId] => BTC
            [quoteId] => USDT
            [info] => Array
                (
                    [symbol] => BTCUSDT
                    [status] => TRADING
                    [baseAsset] => BTC
                    [baseAssetPrecision] => 8
                    [quoteAsset] => USDT
                    [quotePrecision] => 8
                    [quoteAssetPrecision] => 8
                    [baseCommissionPrecision] => 8
                    [quoteCommissionPrecision] => 8
                    [orderTypes] => Array
                        (
                            [0] => LIMIT
                            [1] => LIMIT_MAKER
                            [2] => MARKET
                            [3] => STOP_LOSS_LIMIT
                            [4] => TAKE_PROFIT_LIMIT
                        )

                    [icebergAllowed] => 1
                    [ocoAllowed] => 1
                    [quoteOrderQtyMarketAllowed] => 1
                    [isSpotTradingAllowed] => 1
                    [isMarginTradingAllowed] => 1
                    [filters] => Array
                        (
                            [0] => Array
                                (
                                    [filterType] => PRICE_FILTER
                                    [minPrice] => 0.01000000
                                    [maxPrice] => 1000000.00000000
                                    [tickSize] => 0.01000000
                                )

                            [1] => Array
                                (
                                    [filterType] => PERCENT_PRICE
                                    [multiplierUp] => 5
                                    [multiplierDown] => 0.2
                                    [avgPriceMins] => 5
                                )

                            [2] => Array
                                (
                                    [filterType] => LOT_SIZE
                                    [minQty] => 0.00000100
                                    [maxQty] => 9000.00000000
                                    [stepSize] => 0.00000100
                                )

                            [3] => Array
                                (
                                    [filterType] => MIN_NOTIONAL
                                    [minNotional] => 10.00000000
                                    [applyToMarket] => 1
                                    [avgPriceMins] => 5
                                )

                            [4] => Array
                                (
                                    [filterType] => ICEBERG_PARTS
                                    [limit] => 10
                                )

                            [5] => Array
                                (
                                    [filterType] => MARKET_LOT_SIZE
                                    [minQty] => 0.00000000
                                    [maxQty] => 126.95462941
                                    [stepSize] => 0.00000000
                                )

                            [6] => Array
                                (
                                    [filterType] => MAX_NUM_ORDERS
                                    [maxNumOrders] => 200
                                )

                            [7] => Array
                                (
                                    [filterType] => MAX_NUM_ALGO_ORDERS
                                    [maxNumAlgoOrders] => 5
                                )

                        )

                    [permissions] => Array
                        (
                            [0] => SPOT
                            [1] => MARGIN
                        )

                )

            [type] => spot
            [spot] => 1
            [margin] => 1
            [future] => 
            [delivery] => 
            [active] => 1
        )
*/
?>