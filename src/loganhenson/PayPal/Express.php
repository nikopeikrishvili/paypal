<?php namespace loganhenson\PayPal;

/**
 * Class Express
 * @package loganhenson\PayPal
 */
class Express{

    /**
     * @var array
     */
    private $config;
    /**
     * @var array
     */
    private $items = array();
    /**
     * @var int
     */
    private $taxes = 0;
    /**
     * @var int
     */
    private $shipping_handling = 0;

    /**
     *
     */
    public function __construct(){
        $this->init();
    }

    /**
     *
     */
    private function init(){

        //do schema check and issue exception if no match
        $this->config = include(__DIR__ . '/../paypal.php');

        if(!$this->config['live']){
            $this->config['pwd'] = $this->config['credentials']['sandbox']['pwd'];
            $this->config['signature'] = $this->config['credentials']['sandbox']['signature'];
            $this->config['sandbox'] = '.sandbox';
        }else{
            $this->config['pwd'] = $this->config['credentials']['live']['pwd'];
            $this->config['signature'] = $this->config['credentials']['live']['signature'];
            $this->config['sandbox'] = '';
        }

    }

    /**
     * @param array $get
     */
    public function catchReturn($get){
        $_SESSION['Express_PayerID'] = $get['PayerID'];
        $_SESSION['Express_token'] = $get['token'];
    }

    /**
     * @param Item $item
     */
    public function addItem($item){

        $this->items[] = $item;

    }

    /**
     * @param array $items
     */
    public function addItems(Array $items){
        foreach($items as $item){
            $this->addItem($item);
        }
    }

    /**
     * @param $url
     * @param $params
     * @return array
     * @throws \Exception
     */
    private function makeRequest($url, $params){

        $ch = curl_init($url);

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER ,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST ,false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = array();
        parse_str(curl_exec($ch), $response);
        curl_close($ch);

        if($response['ACK'] === 'Success'){
            return $response;
        }else{
            throw new \Exception($response['L_LONGMESSAGE0']);
        }

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function completeTransaction(){

        $method = array('METHOD' => 'DoExpressCheckoutPayment');

        $ch = 'https://api-3t' . $this->config['sandbox'] . ".paypal.com/nvp";

        $fields = array_merge($_SESSION['Express_start'], array(
            'TOKEN' => $_SESSION['Express_token'],
            'PAYERID' => $_SESSION['Express_PayerID']
        ));

        //put method in request
        $fields = array_merge($fields, $method);

        $response = $this->makeRequest($ch, $fields);

        if($response['ACK'] == 'Success'){
            foreach($_SESSION as $index => $sessionVar){
                unset($_SESSION[$index]);
            }
        }else{
            throw new \Exception($response['L_LONGMESSAGE0']);
        }

        return $response;

    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTransactionDetails(){

        $method = array('METHOD' => 'GetExpressCheckoutDetails');

        $ch = 'https://api-3t' . $this->config['sandbox'] . ".paypal.com/nvp";

        $fields = array(
            'USER' => $this->config['user'],
            'PWD' => $this->config['pwd'],
            'SIGNATURE' => $this->config['signature'],
            'VERSION' => '93',
            'TOKEN' => $_SESSION['Express_token']
        );

        //add method
        $fields = array_merge($fields, $method);

        return $this->makeRequest($ch, $fields);

    }

    /**
     * @return int|mixed
     */
    private function calculateSubTotal(){

        $total = 0;
        /** @var Item $item */
        foreach($this->items as $item){
            $total += $item->getPrice() * $item->getQuantity();
        }

        return $total;

    }

    /**
     * @return int|mixed
     */
    private function calculateTotal(){

        return $this->calculateSubTotal() + $this->taxes + $this->shipping_handling;

    }

    /**
     * @return array
     */
    private function getLineItems(){

        $lineItems = array();

        /** @var  Item $item */
        foreach($this->items as $index => $item){
            $lineItems["L_PAYMENTREQUEST_0_NAME$index"] = $item->getName();
            $lineItems["L_PAYMENTREQUEST_0_NUMBER$index"] = $item->getNumber();
            $lineItems["L_PAYMENTREQUEST_0_DESC$index"] = $item->getDescription();
            $lineItems["L_PAYMENTREQUEST_0_AMT$index"] = $item->getPrice();
            $lineItems["L_PAYMENTREQUEST_0_QTY$index"] = $item->getQuantity();
        }

        return $lineItems;

    }

    /**
     * @param $return_url
     * @param $cancel_url
     * @return string
     * @throws \Exception
     */
    public function sendToPayPal($return_url, $cancel_url){


        $method = array('METHOD' => 'SetExpressCheckout');

        $ch = 'https://api-3t' . $this->config['sandbox'] . '.paypal.com/nvp';

        $fields = array(
            'USER' => $this->config['user'],
            'PWD' => $this->config['pwd'],
            'SIGNATURE' => $this->config['signature'],
            'SOLUTIONTYPE' => 'Sole',
            'VERSION' => '93',
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'SALE',
            'PAYMENTREQUEST_0_CURRENCYCODE' => 'USD',
            'RETURNURL' => $return_url,
            'CANCELURL' => $cancel_url,

            //totals
            'PAYMENTREQUEST_0_ITEMAMT' => $this->calculateSubTotal(),
            'PAYMENTREQUEST_0_TAXAMT' => $this->taxes,
            'PAYMENTREQUEST_0_SHIPPINGAMT' => $this->shipping_handling,
            'PAYMENTREQUEST_0_AMT' => $this->calculateTotal()
        );

        //add line items
        $fields = array_merge($fields, $this->getLineItems());

        //save initial fields for resubmission on checkout
        $_SESSION['Express_start'] = $fields;

        //add method
        $fields = array_merge($fields, $method);

        $response = $this->makeRequest($ch, $fields);

        //save token to session
        $_SESSION['Express_token'] = $response['TOKEN'];

        return 'https://www' . $this->config['sandbox'] . '.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=' . $response['TOKEN'];

    }

    /**
     * @param int $taxes
     */
    public function setTaxes($taxes)
    {
        $this->taxes = $taxes;
    }

    /**
     * @param int $shipping_handling
     */
    public function setShippingHandling($shipping_handling)
    {
        $this->shipping_handling = $shipping_handling;
    }

}