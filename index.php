<?php

use loganhenson\PayPal\Express;
use loganhenson\PayPal\Item;

require 'vendor/autoload.php';

session_cache_limiter(false);
session_start();

$app = new \Slim\Slim();

$app->get('/start', function() use ($app){

    $PayPal = new Express();

    $item = new Item('test item', 'this is test description', '123', '12.50', 5);
    $item2 = new Item('another item', 'another desc', '1234', '10.10', 3);

    $PayPal->addItems(array($item, $item2));

    //$PayPal->setTaxes('12.50');
    //$PayPal->setShippingHandling('5.59');

    //$PayPal->addItem(new Item('Order', 'Order from test store', '', '50.55', 1));

    try{
        $redirectUrl = $PayPal->sendToPayPal('http://localhost:3000/return', 'http://localhost:3000/cancel');
        echo '<a href="' . $redirectUrl . '">Go to paypal</a>';
    }catch(\Exception $e){
        echo $e->getMessage();
    }

});

$app->get('/return', function(){
    $PayPal = new Express();
    $PayPal->catchReturn($_GET);
    try{
        var_dump($PayPal->getTransactionDetails());
        echo '<a href="/complete">Complete Transaction</a>';
    }catch(\Exception $e){
        echo $e->getMessage();
    }
});

$app->get('/complete', function(){
    $PayPal = new Express();
    try{
        $details = $PayPal->getTransactionDetails();
        $PayPal->completeTransaction();
        var_dump($details);
    }catch(\Exception $e){
        echo $e->getMessage();
    }
});

$app->run();