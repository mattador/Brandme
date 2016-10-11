<?php

namespace Entities;

class FactorFiscal extends \Phalcon\Mvc\Model
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
     * @var string
     */
    protected $tax_id;

    /**
     * @var string
     */
    protected $paypal_email;

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
     * Method to set the value of field tax_id
     *
     * @param string $tax_id
     * @return $this
     */
    public function setTaxId($tax_id)
    {
        $this->tax_id = $tax_id;

        return $this;
    }

    /**
     * Method to set the value of field paypal_email
     *
     * @param string $paypal_email
     * @return $this
     */
    public function setPaypalEmail($paypal_email)
    {
        $this->paypal_email = $paypal_email;

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
     * Returns the value of field tax_id
     *
     * @return string
     */
    public function getTaxId()
    {
        return $this->tax_id;
    }

    /**
     * Returns the value of field paypal_email
     *
     * @return string
     */
    public function getPaypalEmail()
    {
        return $this->paypal_email;
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
        return 'factor_fiscal';
    }

}
