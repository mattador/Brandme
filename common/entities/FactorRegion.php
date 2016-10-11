<?php

namespace Entities;

class FactorRegion extends \Phalcon\Mvc\Model
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
    protected $postcode;

    /**
     * @var string
     */
    protected $street;

    /**
     * @var string
     */
    protected $interior_number;

    /**
     * @var string
     */
    protected $exterior_number;

    /**
     * @var string
     */
    protected $suburb;

    /**
     * @var string
     */
    protected $colony;

    /**
     * @var string
     */
    protected $state;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $country;

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
     * Method to set the value of field postcode
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Method to set the value of field street
     *
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Method to set the value of field interior_number
     *
     * @param string $interior_number
     * @return $this
     */
    public function setInteriorNumber($interior_number)
    {
        $this->interior_number = $interior_number;

        return $this;
    }

    /**
     * Method to set the value of field exterior_number
     *
     * @param string $exterior_number
     * @return $this
     */
    public function setExteriorNumber($exterior_number)
    {
        $this->exterior_number = $exterior_number;

        return $this;
    }

    /**
     * Method to set the value of field suburb
     *
     * @param string $suburb
     * @return $this
     */
    public function setSuburb($suburb)
    {
        $this->suburb = $suburb;

        return $this;
    }

    /**
     * Method to set the value of field colony
     *
     * @param string $colony
     * @return $this
     */
    public function setColony($colony)
    {
        $this->colony = $colony;

        return $this;
    }

    /**
     * Method to set the value of field state
     *
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Method to set the value of field city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Method to set the value of field country
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

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
     * Returns the value of field postcode
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Returns the value of field street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Returns the value of field interior_number
     *
     * @return string
     */
    public function getInteriorNumber()
    {
        return $this->interior_number;
    }

    /**
     * Returns the value of field exterior_number
     *
     * @return string
     */
    public function getExteriorNumber()
    {
        return $this->exterior_number;
    }

    /**
     * Returns the value of field suburb
     *
     * @return string
     */
    public function getSuburb()
    {
        return $this->suburb;
    }

    /**
     * Returns the value of field colony
     *
     * @return string
     */
    public function getColony()
    {
        return $this->colony;
    }

    /**
     * Returns the value of field state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Returns the value of field city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Returns the value of field country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
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
        return 'factor_region';
    }

}
