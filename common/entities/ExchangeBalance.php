<?php

namespace Entities;

class ExchangeBalance extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $id_exchange;

    /**
     * @var double
     */
    protected $balance;

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
     * Method to set the value of field id_exchange
     *
     * @param integer $id_exchange
     * @return $this
     */
    public function setIdExchange($id_exchange)
    {
        $this->id_exchange = $id_exchange;

        return $this;
    }

    /**
     * Method to set the value of field balance
     *
     * @param double $balance
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

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
     * Returns the value of field id_exchange
     *
     * @return integer
     */
    public function getIdExchange()
    {
        return $this->id_exchange;
    }

    /**
     * Returns the value of field balance
     *
     * @return double
     */
    public function getBalance()
    {
        return $this->balance;
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
        $this->belongsTo('id_exchange', 'Entities\Exchange', 'id', array('foreignKey' => true, 'alias' => 'Exchange'));
    }

    public function getSource()
    {
        return 'exchange_balance';
    }

}
