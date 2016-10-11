<?php

namespace Entities;

class Exchange extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $is_white_label_partner;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $owner;

    /**
     * @var string
     */
    protected $contact;

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
     * Method to set the value of field is_white_label_partner
     *
     * @param integer $is_white_label_partner
     * @return $this
     */
    public function setIsWhiteLabelPartner($is_white_label_partner)
    {
        $this->is_white_label_partner = $is_white_label_partner;

        return $this;
    }

    /**
     * Method to set the value of field name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Method to set the value of field owner
     *
     * @param string $owner
     * @return $this
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Method to set the value of field contact
     *
     * @param string $contact
     * @return $this
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

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
     * Returns the value of field is_white_label_partner
     *
     * @return integer
     */
    public function getIsWhiteLabelPartner()
    {
        return $this->is_white_label_partner;
    }

    /**
     * Returns the value of field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the value of field owner
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Returns the value of field contact
     *
     * @return string
     */
    public function getContact()
    {
        return $this->contact;
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
        $this->hasOne('id', 'Entities\ExchangeBalance', 'id_exchange', array('alias' => 'ExchangeBalance'));
        $this->hasMany('id', 'Entities\ExchangeTransaction', 'id_exchange', array('alias' => 'ExchangeTransaction'));
        $this->hasMany('id', 'Entities\Factor', 'id_exchange', array('alias' => 'Factor'));
    }

    public function getSource()
    {
        return 'exchange';
    }

}
