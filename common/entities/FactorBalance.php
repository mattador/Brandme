<?php

namespace Entities;

class FactorBalance extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $id_factor;

    /**
     * @var double
     */
    protected $balance;

    /**
     * @var double
     */
    protected $reserved;

    /**
     * @var double
     */
    protected $pending_withdrawal;

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
     * Method to set the value of field id_factor
     *
     * @param integer $id_factor
     * @return $this
     */
    public function setIdFactor($id_factor)
    {
        $this->id_factor = $id_factor;

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
     * Method to set the value of field reserved
     *
     * @param double $reserved
     * @return $this
     */
    public function setReserved($reserved)
    {
        $this->reserved = $reserved;

        return $this;
    }

    /**
     * Method to set the value of field pending_withdrawal
     *
     * @param double $pending_withdrawal
     * @return $this
     */
    public function setPendingWithdrawal($pending_withdrawal)
    {
        $this->pending_withdrawal = $pending_withdrawal;

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
     * Returns the value of field id_factor
     *
     * @return integer
     */
    public function getIdFactor()
    {
        return $this->id_factor;
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
     * Returns the value of field reserved
     *
     * @return double
     */
    public function getReserved()
    {
        return $this->reserved;
    }

    /**
     * Returns the value of field pending_withdrawal
     *
     * @return double
     */
    public function getPendingWithdrawal()
    {
        return $this->pending_withdrawal;
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
        $this->belongsTo('id_factor', 'Entities\Factor', 'id', array('foreignKey' => true, 'alias' => 'Factor'));
    }

    public function getSource()
    {
        return 'factor_balance';
    }

}
