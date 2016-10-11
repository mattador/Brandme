<?php

namespace Entities;

class Transaction extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var double
     */
    protected $amount;

    /**
     * @var double
     */
    protected $tax;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $paypal_token;

    /**
     * @var string
     */
    protected $paypal_payer_id;

    /**
     * @var string
     */
    protected $paypal_set_express_checkout_response;

    /**
     * @var string
     */
    protected $paypal_do_express_checkout_response;

    /**
     * @var string
     */
    protected $paypal_get_express_checkout_response;

    /**
     * @var string
     */
    protected $authorization;

    /**
     * @var string
     */
    protected $created_at;

    /**
     * @var string
     */
    protected $updated_at;

    /**
     * Method to set the value of field id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Method to set the value of field amount
     *
     * @param double $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Method to set the value of field tax
     *
     * @param double $tax
     * @return $this
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    /**
     * Method to set the value of field type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Method to set the value of field status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Method to set the value of field description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Method to set the value of field paypal_token
     *
     * @param string $paypal_token
     * @return $this
     */
    public function setPaypalToken($paypal_token)
    {
        $this->paypal_token = $paypal_token;

        return $this;
    }

    /**
     * Method to set the value of field paypal_payer_id
     *
     * @param string $paypal_payer_id
     * @return $this
     */
    public function setPaypalPayerId($paypal_payer_id)
    {
        $this->paypal_payer_id = $paypal_payer_id;

        return $this;
    }

    /**
     * Method to set the value of field paypal_set_express_checkout_response
     *
     * @param string $paypal_set_express_checkout_response
     * @return $this
     */
    public function setPaypalSetExpressCheckoutResponse($paypal_set_express_checkout_response)
    {
        $this->paypal_set_express_checkout_response = $paypal_set_express_checkout_response;

        return $this;
    }

    /**
     * Method to set the value of field paypal_do_express_checkout_response
     *
     * @param string $paypal_do_express_checkout_response
     * @return $this
     */
    public function setPaypalDoExpressCheckoutResponse($paypal_do_express_checkout_response)
    {
        $this->paypal_do_express_checkout_response = $paypal_do_express_checkout_response;

        return $this;
    }

    /**
     * Method to set the value of field paypal_get_express_checkout_response
     *
     * @param string $paypal_get_express_checkout_response
     * @return $this
     */
    public function setPaypalGetExpressCheckoutResponse($paypal_get_express_checkout_response)
    {
        $this->paypal_get_express_checkout_response = $paypal_get_express_checkout_response;

        return $this;
    }

    /**
     * Method to set the value of field authorization
     *
     * @param string $authorization
     * @return $this
     */
    public function setAuthorization($authorization)
    {
        $this->authorization = $authorization;

        return $this;
    }

    /**
     * Method to set the value of field created_at
     *
     * @param string $created_at
     * @return $this
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * Method to set the value of field updated_at
     *
     * @param string $updated_at
     * @return $this
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field amount
     *
     * @return double
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Returns the value of field tax
     *
     * @return double
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * Returns the value of field type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the value of field status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the value of field description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the value of field paypal_token
     *
     * @return string
     */
    public function getPaypalToken()
    {
        return $this->paypal_token;
    }

    /**
     * Returns the value of field paypal_payer_id
     *
     * @return string
     */
    public function getPaypalPayerId()
    {
        return $this->paypal_payer_id;
    }

    /**
     * Returns the value of field paypal_set_express_checkout_response
     *
     * @return string
     */
    public function getPaypalSetExpressCheckoutResponse()
    {
        return $this->paypal_set_express_checkout_response;
    }

    /**
     * Returns the value of field paypal_do_express_checkout_response
     *
     * @return string
     */
    public function getPaypalDoExpressCheckoutResponse()
    {
        return $this->paypal_do_express_checkout_response;
    }

    /**
     * Returns the value of field paypal_get_express_checkout_response
     *
     * @return string
     */
    public function getPaypalGetExpressCheckoutResponse()
    {
        return $this->paypal_get_express_checkout_response;
    }

    /**
     * Returns the value of field authorization
     *
     * @return string
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * Returns the value of field created_at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Returns the value of field updated_at
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('id', 'Entities\ExchangeTransaction', 'id_transaction', array('alias' => 'ExchangeTransaction'));
        $this->hasMany('id', 'Entities\FactorTransaction', 'id_transaction', array('alias' => 'FactorTransaction'));
    }

    public function getSource()
    {
        return 'transaction';
    }

}
