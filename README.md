# Cryptocurrency Triangular Arbitrage on Binance
##  Complete and automatic trading system for triangular arbitrage for all cryptocurrencies

This project is written in PHP and works through the Windows and Linux console.

![ccxt](/images/ccxt_30.png) 
Use the [CCXT](https://github.com/ccxt/ccxt) libraries to interface with Binance's APIs



| What is triangular arbitrage? |   |
| ------ | ----------- |
| ![trnarb](/images/triangular_arbitrage.png)   | Arbitrage can be defined as the purchase and sale on different markets of currency pairs that have a common currency. <br> The purpose is to take advantage of price differentials. <br> When a trader uses the arbitrage technique, he basically goes to buy a cheaper asset and then resell it at a higher price on a different market within the same Exchange. <br> [Click here for more information on the topic of triangular arbitrage.](https://docs.google.com/spreadsheets/d/e/2PACX-1vSJdsPKIRCLLSJIhzXqYlDL2z965rFEzI7c2UWPx75WfRYhPfmJ1_8HxdF662GnhA7R3kLcjAK4vTbO/pubhtml)|


___

<br>

## Warning:

### Due to the volatility of the markets and competition from other traders and arbitrage systems, chances of profit are rare.
### The program is offered as is.
### By downloading and using this program you do so at your own risk.
### You cannot hold me responsible for any bugs and economic losses.


___

<br>

## Seutp Ambient:

+ First you need to install PHP and MySQL, in my case I used [XAMPP](https://www.apachefriends.org).

+ Import `Arbitrage_phpMyAdmin.sql` database stored in the `MySql` folder using [phpMyAdmin](https://github.com/phpmyadmin/phpmyadmin/wiki).
    - The name of the database you choose must be inserted in the `inc_config.php` file in the variable: `$dbname`

+ If you are not already registered on Binance, use my referral link to **get 10% off your trading fees**.
    - Referral link: https://www.binance.com/it/register?ref=IACMIAKV

+ After registering on Binance you need to create the API KEY.
  - it is necessary to provide the following permissions `Enable Reading` and `Enable a Spot & Margin Trading` to allow the program to know the funds in the portfolio and execute the trading orders of the currency pairs.

___

<br>

## Seutp Arbitrage configuration file: `inc_config.php`

the `inc_config.php` file allows you to manage the functionality of the application.
It is essential to enter the configuration data of the MySQL database and the Binance KEY API.
Also there are various parameters for the configuration of the application which we will see one by one.

I have created 2 example files called `inc_config - LIVE.php` and `inc_config - TEST.php` which you can use as a starting point.
NOTE: the program looks for the file: `inc_config.php` so you will have to rename them in case you want to use them.

<br>

+ First you need to enter your MySQL configuration data

    ```php
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
    ```
    The `$S_MySQL` variable will allow you to quickly switch between a local and a remote database configuration.

    The `$S_MySQL` variable can assume 2 values:
        
    ```php    
    $S_MySQL = 0; // LOCAL - Local Mysql Server Configuration

    $S_MySQL = 1; // REMOTE - Remote Mysql Server Configuration 
    ```

<br>

+ Second you need to enter the KEY and SECRET API:

    ```php
    // API KEY
    if($validate == 1){ 
        // API KEYS Binance Spot Test Network:    
        // https://testnet.binance.vision/
        $key = 'INSERT YOUR TESTNET KEY'; 
        $secret = 'INSERT YOUR TESTNET SECRET';        
    }else{     
        // REAL API KEYS:    
        $key = 'INSERT YOUR OFFICIAL KEY'; 
        $secret = 'INSERT YOUR OFFICIAL SECRET';   
    }
    ```

    As you can see it is possible to insert both the  [API KEY](https://www.binance.com/it/register?ref=IACMIAKV) of the Test Net and those of the real account.
    to manage the transition from the test network to the real Binance network, you need to set the parameter: `$validate`

    The `$validate` variable can assume 3 values:

    ```php
    $validate = 0; // Use real price data and execute real orders (Use it at your risk)

    $validate = 1; // Use the Binance testnet server (prices, orders and currency pairs are from the test network and do not affect your real account and your money)

    $validate = 2; // I use real market data but orders are simulated (good for test)
    ```

<br>

+ If you don't have funds in your account and you still want to test the application, or you want to see what results the triangular arbitrage system offers on one or more coins, you need to set the variable: `$Sim_Bal`

    The `$Sim_Bal` variable can assume 2 values:

    ```php
    $Sim_Bal = 0; // use your real balance

    $Sim_Bal = 1; // use the data from $my_coin_list as a simulation of the funds
    ```

<br>

+ the variable `$Max_Time_Execution` sets the idle time between API calls.
    it is not recommended to lower the waiting times to avoid blocking the API.

    ```php
    $Max_Time_Execution = 5; // seconds and milliseconds (example: 4.55).
    ```
<br>

+ The `$my_coin_list` variable has a dual functionality.
As you have read above it is used to simulate the funds in your wallet in case they are not present by setting the variable `$Sim_Bal=1`.
At the same time it serves to instruct the arbitrage program on which currencies you want to execute your trading.
The variable is composed of an array in which the key is the name of the coin and the value is the maximum volume used for the execution of the purchase and sale activities.

    EXAMPLE: let's suppose that you have 1000 USDT in your wallet but for arbitrage operations you want to use only 75 USDT you will have to write: `"USDT"=>75`.

    How set up `$my_coin_list`:

    ```php
    $my_coin_list = array("USDT" => 50); // Single coin (currency => amount)

    $my_coin_list = array("USDT"=>50, "XRP"=>250, "BTC"=>0.01, "ETH"=>0.15); // multiple coin
    ```    

<br>

+ Another essential variable is `$min_profit`. Once you have decided on the currency pairs on which to operate, you need to set the parameter that determines market entry. `$min_profit` identifies the minimum percentage profit given by the price analysis of the triangles.

    How set up `$min_profit`:

    ```php
    $min_profit = 5; // If the price analysis estimates a profit greater than 5% then the program enters the market.
    ``` 
    I don't advise you to go below 4%

<br>

+ The variable `$offset_volume_order` allows you to create a small reserve of money between switching from one currency pair to another in the triangular arbitrage process.
This is to prevent our orders from being rejected due to lack of funds.
    
     How set up `$offset_volume_order`:

     ``` php
     $offset_volume_order = 0.05; // 0.05% of the available amount is not included in the order.    
     ```

<br>

+ The `$min_trans` variable refers to the number of transactions that occurred in the last 24 hours. If they are less than the set parameter then the system does not enter the market even if the estimated profit is greater than `$min_profit`.
This control is very useful to prevent the program from entering new markets where the trading volume is limited and consequently there is a risk that price fluctuations are very high with a high probability of losing money.

     How set up `$min_trans`:

     ``` php
     $min_trans = 1000; // check if there have been at least 1000 transactions in the last 24 hours. I recommend not to go below 500 transactions
     ```

<br>

     
+ The `$vol_ask_bid_check` variable is used to control the bid or ask volume of the order book. If the triangle has a profit % greater than `$min_profit` and this control is activated it will be necessary that the first bid/ask price has a volume at least equal to the volume with which we want to enter the market. This is to avoid that our order is partially filled at an advantageous price and then the rest of the order could be executed with a significant difference in price from the planned one.

     The `$vol_ask_bid_check` variable can assume 2 values:

     ``` php
    $vol_ask_bid_check = true; // the volume control of the order book is activated
    
    $vol_ask_bid_check = false; // the volume control of the order book is deactivated
     ```

___

<br>

## Starting the arbitrage application: `Arbitrage.php`


After installing the MySQL database and setting the `inc_config.php` configuration file,
we just have to start our arbitration program by running the `Arbitrage.php` file.

<br>

+ ### **Starting the program from the Windows Console:**

     If you have XAMPP installed the path you should have is something like this:

     `C:\xampp\htdocs\Arb_Trng_BIN_Console\Arbitrage.php`

     Let's set the path to access PHP:

     `cd C:\xampp\php`

     Then we start the program:

     `php ../htdocs/Arb_Trng_BIN_Console/Arbitrage.php`

<br>

+ ### **Starting the program from the Linux Console.**
     (I tested the application on Linux CentOS)

     `php -f /var/www/html/Arb_Trng_BIN_Console/Arbitrage.php`

<br>
<br>

If all went well when the program is launched, you should see the triangulation between the currency pairs that have the most profit:

<br>

![arb_run](/images/arbitrage_running.png)


Inside the MySQL database you can find the tables in which the program stores the data:

+ ## asset_pairs_b_q: 
    it contains the list of currencies and the necessary data from the minimum order to the number of accepted decimals.

    ![asset_pairs_b_q](/images/asset_pairs_b_q.png)

+ ## asset_pairs_all_pairs: 
    Stores the list of all valid triangulations and related data.

    ![asset_pairs_all_pairs](/images/asset_pairs_all_pairs.png)

+ ## arb_log_best_candidate: 
    Stores the best candidate among all the possible triangulations that have the currencies entered in the variable as base or quotas: `$my_coin_list`.

    ![arb_log_best_candidate](/images/arb_log_best_candidate.png)

+ ## arb_log_orders: 
    Stores all orders placed on each currency pair.

    ![arb_log_orders](/images/arb_log_orders.png)

+ ## arb_log_timer_ms4: 
    Stores all requests made to the Binance API (including order requests).

    ![arb_log_timer_ms4](/images/arb_log_timer_ms4.png)

+ ## arb_log_errors: 
    All program errors including API errors are stored.
     If the error is frequent: `get_all_tickers_data(), [Network Error] binance 429  {"code":-1003,"msg":"Too much request weight used; current limit is 1200 request weight per 1 MINUTE. Please use the websocket for live updates to avoid polling the API."}`
    it will be necessary to increase the value of the configuration parameter: `$Max_Time_Execution`.

    If you often get this error message:
    `[Exchange Error] binance Account has insufficient balance for requested action.`
    I advise you to increase the value of the variable: `$offset_volume_order`.

    ![arb_log_errors](/images/arb_log_errors.png)






___

<br>

If you like my project and you want to support me, offer me a pizza ![ccxt](/images/pizza.png)     


![btc](/images/BTC.png) BTC: `19GcXwDcKDkqSpCDLEjeZTAcCu1WK8CZyg`

![eth](/images/ETH.png) ETH: `0xf75e8e810fc746386155bce442581c9157aecd30`    

![btt](/images/BTT.png) BTT: `TBBPEU3J6o37W28oBr4nrhS5mK3bkBqRsE`

![xrp](/images/XRP.png) XRP: `rEb8TK3gBgk5auZkwc6sHnwrGVJH8DuaLh` TAG: `100375876`

![btt](/images/Paypal_logo.png) Paypal: https://paypal.me/pools/c/8yJs88y7Uk


___

If you are not already registered on Binance, use my referral link to **get 10% off your trading fees**.

Referral link: https://www.binance.com/it/register?ref=IACMIAKV
___

     

