# *No Longer Maintained*

## Simple PayPal Express API

## Installation

```
composer require loganhenson/paypal:@dev
```
 
## Assumptions

This package is a very opinionated/focused api wrapper for PayPal Express, thus it makes a few assumptions to provide the lightest api possible:

2. You are using a native session store and the session is always started before this class is used

# API

##\Item

> constructor + getters/setters for the following:
> 
> - name
> - description
> - number
> - price
> - quantity

##\Express

### addItem(item) REQUIRED
> Adds the item to the checkout

### addItems(items)
> Adds an array of items all at once

### setTaxes(amount)
> Sets the tax amount for the purchase

### setShippingHandling(amount)
> Sets the shipping and handling cost for the purchase

### sendToPayPal(return\_url, cancel\_url) REQUIRED, THROWS
> Returns a url to redirect the client to, must have at least one item added to the checkout

### catchReturn($_GET) REQUIRED
> Accepts the request $_GET params, and saves the state of the transaction, must call on the return route, before the complete route

### getTransactionDetails() THROWS
> Can be called at any point between catchReturn, and completeTransaction for validation purposes

### completeTransaction() REQUIRED, THROWS
> Successfully completes the transaction, or throws an error

## Usage

You will need 3 routes/scripts/pages

1. A start page:

```php
<?php

$PayPal = new Express();

$item = new Item('test item', 'this is test description', '123', '12.50', 5);
$item2 = new Item('another item', 'another desc', '1234', '10.10', 3);

$PayPal->addItems(array($item, $item2));

try{
    $redirectUrl = $PayPal->sendToPayPal('http://localhost:3000/return', 'http://localhost:3000/cancel');
    echo '<a href="' . $redirectUrl . '">Go to paypal</a>';
}catch(\Exception $e){
    echo $e->getMessage();
}

?>
```

2. A return page:

```php
<?php

$PayPal = new Express();
$PayPal->catchReturn($_GET);
echo '<a href="/complete">Complete Transaction</a>';

```

3. A complete page

```php
<?php

$PayPal = new Express();
try{
    $PayPal->completeTransaction();
}catch(\Exception $e){
    echo $e->getMessage();
}

```


## Example (Slim Framework)

```php
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
```
