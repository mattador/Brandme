<?php

namespace Entities;

class ExchangeTransaction extends \Phalcon\Mvc\Model
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
     * @var integer
     */
    protected $id_transaction;

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
     * Method to set the value of field id_transaction
     *
     * @param integer $id_transaction
     * @return $this
     */
    public function setIdTransaction($id_transaction)
    {
        $this->id_transaction = $id_transaction;

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
     * Returns the value of field id_transaction
     *
     * @return integer
     */
    public function getIdTransaction()
    {
        return $this->id_transaction;
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
        $this->belongsTo('id_transaction', 'Entities\Transaction', 'id', array('foreignKey' => true, 'alias' => 'Transaction'));
    }

    public function getSource()
    {
        return 'exchange_transaction';
    }

}
