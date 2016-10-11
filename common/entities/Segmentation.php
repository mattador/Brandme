<?php

namespace Entities;

class Segmentation extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $english_name;

    /**
     * @var string
     */
    protected $segment;

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
     * Method to set the value of field english_name
     *
     * @param string $english_name
     * @return $this
     */
    public function setEnglishName($english_name)
    {
        $this->english_name = $english_name;

        return $this;
    }

    /**
     * Method to set the value of field segment
     *
     * @param string $segment
     * @return $this
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;

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
     * Returns the value of field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the value of field english_name
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->english_name;
    }

    /**
     * Returns the value of field segment
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
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
        $this->hasMany(
            'id',
            'Entities\CampaignOpportunitySegmentation',
            'id_segmentation',
            array('alias' => 'CampaignOpportunitySegmentation')
        );
        $this->hasMany('id', 'Entities\FactorNetworkSegmentation', 'id_segmentation', array('alias' => 'FactorNetworkSegmentation'));
    }

    public function getSource()
    {
        return 'segmentation';
    }

}
