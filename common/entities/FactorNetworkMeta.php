<?php

namespace Entities;

class FactorNetworkMeta extends \Phalcon\Mvc\Model
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
     * @var string
     */
    protected $json;

    /**
     * @var string
     */
    protected $tags;

    /**
     * @var double
     */
    protected $negotiating_price;

    /**
     * @var integer
     */
    protected $statistics_followers;

    /**
     * @var integer
     */
    protected $statistics_following;

    /**
     * @var integer
     */
    protected $statistics_status_updates;

    /**
     * @var integer
     */
    protected $statistics_lists;

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
     * Method to set the value of field json
     *
     * @param string $json
     * @return $this
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Method to set the value of field tags
     *
     * @param string $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Method to set the value of field negotiating_price
     *
     * @param double $negotiating_price
     * @return $this
     */
    public function setNegotiatingPrice($negotiating_price)
    {
        $this->negotiating_price = $negotiating_price;

        return $this;
    }

    /**
     * Method to set the value of field statistics_followers
     *
     * @param integer $statistics_followers
     * @return $this
     */
    public function setStatisticsFollowers($statistics_followers)
    {
        $this->statistics_followers = $statistics_followers;

        return $this;
    }

    /**
     * Method to set the value of field statistics_following
     *
     * @param integer $statistics_following
     * @return $this
     */
    public function setStatisticsFollowing($statistics_following)
    {
        $this->statistics_following = $statistics_following;

        return $this;
    }

    /**
     * Method to set the value of field statistics_status_updates
     *
     * @param integer $statistics_status_updates
     * @return $this
     */
    public function setStatisticsStatusUpdates($statistics_status_updates)
    {
        $this->statistics_status_updates = $statistics_status_updates;

        return $this;
    }

    /**
     * Method to set the value of field statistics_lists
     *
     * @param integer $statistics_lists
     * @return $this
     */
    public function setStatisticsLists($statistics_lists)
    {
        $this->statistics_lists = $statistics_lists;

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
     * Returns the value of field json
     *
     * @return string
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Returns the value of field tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Returns the value of field negotiating_price
     *
     * @return double
     */
    public function getNegotiatingPrice()
    {
        return $this->negotiating_price;
    }

    /**
     * Returns the value of field statistics_followers
     *
     * @return integer
     */
    public function getStatisticsFollowers()
    {
        return $this->statistics_followers;
    }

    /**
     * Returns the value of field statistics_following
     *
     * @return integer
     */
    public function getStatisticsFollowing()
    {
        return $this->statistics_following;
    }

    /**
     * Returns the value of field statistics_status_updates
     *
     * @return integer
     */
    public function getStatisticsStatusUpdates()
    {
        return $this->statistics_status_updates;
    }

    /**
     * Returns the value of field statistics_lists
     *
     * @return integer
     */
    public function getStatisticsLists()
    {
        return $this->statistics_lists;
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
        $this->belongsTo('id_factor_network', 'Entities\FactorNetwork', 'id', array('foreignKey' => true, 'alias' => 'FactorNetwork'));
    }

    public function getSource()
    {
        return 'factor_network_meta';
    }

}
