<?php namespace loganhenson\PayPal;

class Item{

    private $name;
    private $description;
    private $number;
    private $price;
    private $quantity;

    function __construct($name, $description, $number, $price, $quantity)
    {
        $this->name = $name;
        $this->description = $description;
        $this->number = $number;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

}