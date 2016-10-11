<?php

namespace Entities;

class FactorNetworkSegmentation extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $id_factor_network;

    /**
     * @var integer
     */
    protected $id_segmentation;

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
     * Method to set the value of field id_factor_network
     *
     * @param integer $id_factor_network
     * @return $this
     */
    public function setIdFactorNetwork($id_factor_network)
    {
        $this->id_factor_network = $id_factor_network;

        return $this;
    }

    /**
     * Method to set the value of field id_segmentation
     *
     * @param integer $id_segmentation
     * @return $this
     */
    public function setIdSegmentation($id_segmentation)
    {
        $this->id_segmentation = $id_segmentation;

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
     * Returns the value of field id_factor_network
     *
     * @return integer
     */
    public function getIdFactorNetwork()
    {
        return $this->id_factor_network;
    }

    /**
     * Returns the value of field id_segmentation
     *
     * @return integer
     */
    public function getIdSegmentation()
    {
        return $this->id_segmentation;
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
        $this->belongsTo('id_segmentation', 'Entities\Segmentation', 'id', array('foreignKey' => true, 'alias' => 'Segmentation'));
        $this->belongsTo('id_factor_network', 'Entities\FactorNetwork', 'id', array('foreignKey' => true, 'alias' => 'FactorNetwork'));
    }

    public function getSource()
    {
        return 'factor_network_segmentation';
    }

}
