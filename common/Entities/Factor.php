<?php

namespace Entities;

class Factor extends \Phalcon\Mvc\Model
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
    protected $is_administrator;

    /**
     * @var integer
     */
    protected $is_legacy_user;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $accepted_terms_at;

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
     * Method to set the value of field is_administrator
     *
     * @param integer $is_administrator
     * @return $this
     */
    public function setIsAdministrator($is_administrator)
    {
        $this->is_administrator = $is_administrator;

        return $this;
    }

    /**
     * Method to set the value of field is_legacy_user
     *
     * @param integer $is_legacy_user
     * @return $this
     */
    public function setIsLegacyUser($is_legacy_user)
    {
        $this->is_legacy_user = $is_legacy_user;

        return $this;
    }

    /**
     * Method to set the value of field email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Method to set the value of field accepted_terms_at
     *
     * @param string $accepted_terms_at
     * @return $this
     */
    public function setAcceptedTermsAt($accepted_terms_at)
    {
        $this->accepted_terms_at = $accepted_terms_at;

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
     * Returns the value of field is_administrator
     *
     * @return integer
     */
    public function getIsAdministrator()
    {
        return $this->is_administrator;
    }

    /**
     * Returns the value of field is_legacy_user
     *
     * @return integer
     */
    public function getIsLegacyUser()
    {
        return $this->is_legacy_user;
    }

    /**
     * Returns the value of field email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns the value of field accepted_terms_at
     *
     * @return string
     */
    public function getAcceptedTermsAt()
    {
        return $this->accepted_terms_at;
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
        $this->hasMany('id', 'Entities\Campaign', 'id_factor', array('alias' => 'Campaign'));
        $this->hasMany('id', 'Entities\FactorActivity', 'id_factor', array('alias' => 'FactorActivity'));
        $this->hasMany('id', 'Entities\FactorAdminAuth', 'id_factor', array('alias' => 'FactorAdminAuth'));
        $this->hasOne('id', 'Entities\FactorAuth', 'id_factor', array('alias' => 'FactorAuth'));
        $this->hasOne('id', 'Entities\FactorBalance', 'id_factor', array('alias' => 'FactorBalance'));
        $this->hasOne('id', 'Entities\FactorFiscal', 'id_factor', array('alias' => 'FactorFiscal'));
        $this->hasOne('id', 'Entities\FactorMeta', 'id_factor', array('alias' => 'FactorMeta'));
        $this->hasMany('id', 'Entities\FactorNetwork', 'id_factor', array('alias' => 'FactorNetwork'));
        $this->hasOne('id', 'Entities\FactorReference', 'id_factor', array('alias' => 'FactorReference'));
        $this->hasOne('id', 'Entities\FactorRegion', 'id_factor', array('alias' => 'FactorRegion'));
        $this->hasMany('id', 'Entities\FactorSession', 'id_factor', array('alias' => 'FactorSession'));
        $this->hasMany('id', 'Entities\FactorTransaction', 'id_factor', array('alias' => 'FactorTransaction'));
        $this->belongsTo('id_exchange', 'Entities\Exchange', 'id', array('foreignKey' => true, 'alias' => 'Exchange'));
    }

    public function getSource()
    {
        return 'factor';
    }

}
