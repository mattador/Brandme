<?php

namespace Entities;

class FactorAdminAuth extends \Phalcon\Mvc\Model
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
     * @var integer
     */
    protected $is_usable;

    /**
     * @var string
     */
    protected $passhash;

    /**
     * @var integer
     */
    protected $id_factor_accessed;

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
     * Method to set the value of field is_usable
     *
     * @param integer $is_usable
     * @return $this
     */
    public function setIsUsable($is_usable)
    {
        $this->is_usable = $is_usable;

        return $this;
    }

    /**
     * Method to set the value of field passhash
     *
     * @param string $passhash
     * @return $this
     */
    public function setPasshash($passhash)
    {
        $this->passhash = $passhash;

        return $this;
    }

    /**
     * Method to set the value of field id_factor_accessed
     *
     * @param integer $id_factor_accessed
     * @return $this
     */
    public function setIdFactorAccessed($id_factor_accessed)
    {
        $this->id_factor_accessed = $id_factor_accessed;

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
     * Returns the value of field is_usable
     *
     * @return integer
     */
    public function getIsUsable()
    {
        return $this->is_usable;
    }

    /**
     * Returns the value of field passhash
     *
     * @return string
     */
    public function getPasshash()
    {
        return $this->passhash;
    }

    /**
     * Returns the value of field id_factor_accessed
     *
     * @return integer
     */
    public function getIdFactorAccessed()
    {
        return $this->id_factor_accessed;
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
        return 'factor_admin_auth';
    }

}
