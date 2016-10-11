<?php

namespace Entities;

class CampaignOpportunityParticipation extends \Phalcon\Mvc\Model
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
    protected $id_campaign_opportunity;

    /**
     * @var integer
     */
    protected $is_paid;

    /**
     * @var double
     */
    protected $bid;

    /**
     * @var double
     */
    protected $bid_with_markup;

    /**
     * @var double
     */
    protected $bid_with_markup_reserved;

    /**
     * @var string
     */
    protected $pitch;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var integer
     */
    protected $creator_status;

    /**
     * @var integer
     */
    protected $advertiser_status;

    /**
     * @var string
     */
    protected $dispatch_at;

    /**
     * @var integer
     */
    protected $dispatch_code;

    /**
     * @var string
     */
    protected $created_at;

    /**
     * @var string
     */
    protected $updated_at;

    /**
     * @var string
     */
    protected $expired_at;

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
     * Method to set the value of field id_campaign_opportunity
     *
     * @param integer $id_campaign_opportunity
     * @return $this
     */
    public function setIdCampaignOpportunity($id_campaign_opportunity)
    {
        $this->id_campaign_opportunity = $id_campaign_opportunity;

        return $this;
    }

    /**
     * Method to set the value of field is_paid
     *
     * @param integer $is_paid
     * @return $this
     */
    public function setIsPaid($is_paid)
    {
        $this->is_paid = $is_paid;

        return $this;
    }

    /**
     * Method to set the value of field bid
     *
     * @param double $bid
     * @return $this
     */
    public function setBid($bid)
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * Method to set the value of field bid_with_markup
     *
     * @param double $bid_with_markup
     * @return $this
     */
    public function setBidWithMarkup($bid_with_markup)
    {
        $this->bid_with_markup = $bid_with_markup;

        return $this;
    }

    /**
     * Method to set the value of field bid_with_markup_reserved
     *
     * @param double $bid_with_markup_reserved
     * @return $this
     */
    public function setBidWithMarkupReserved($bid_with_markup_reserved)
    {
        $this->bid_with_markup_reserved = $bid_with_markup_reserved;

        return $this;
    }

    /**
     * Method to set the value of field pitch
     *
     * @param string $pitch
     * @return $this
     */
    public function setPitch($pitch)
    {
        $this->pitch = $pitch;

        return $this;
    }

    /**
     * Method to set the value of field content
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Method to set the value of field creator_status
     *
     * @param integer $creator_status
     * @return $this
     */
    public function setCreatorStatus($creator_status)
    {
        $this->creator_status = $creator_status;

        return $this;
    }

    /**
     * Method to set the value of field advertiser_status
     *
     * @param integer $advertiser_status
     * @return $this
     */
    public function setAdvertiserStatus($advertiser_status)
    {
        $this->advertiser_status = $advertiser_status;

        return $this;
    }

    /**
     * Method to set the value of field dispatch_at
     *
     * @param string $dispatch_at
     * @return $this
     */
    public function setDispatchAt($dispatch_at)
    {
        $this->dispatch_at = $dispatch_at;

        return $this;
    }

    /**
     * Method to set the value of field dispatch_code
     *
     * @param integer $dispatch_code
     * @return $this
     */
    public function setDispatchCode($dispatch_code)
    {
        $this->dispatch_code = $dispatch_code;

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
     * Method to set the value of field expired_at
     *
     * @param string $expired_at
     * @return $this
     */
    public function setExpiredAt($expired_at)
    {
        $this->expired_at = $expired_at;

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
     * Returns the value of field id_campaign_opportunity
     *
     * @return integer
     */
    public function getIdCampaignOpportunity()
    {
        return $this->id_campaign_opportunity;
    }

    /**
     * Returns the value of field is_paid
     *
     * @return integer
     */
    public function getIsPaid()
    {
        return $this->is_paid;
    }

    /**
     * Returns the value of field bid
     *
     * @return double
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * Returns the value of field bid_with_markup
     *
     * @return double
     */
    public function getBidWithMarkup()
    {
        return $this->bid_with_markup;
    }

    /**
     * Returns the value of field bid_with_markup_reserved
     *
     * @return double
     */
    public function getBidWithMarkupReserved()
    {
        return $this->bid_with_markup_reserved;
    }

    /**
     * Returns the value of field pitch
     *
     * @return string
     */
    public function getPitch()
    {
        return $this->pitch;
    }

    /**
     * Returns the value of field content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the value of field creator_status
     *
     * @return integer
     */
    public function getCreatorStatus()
    {
        return $this->creator_status;
    }

    /**
     * Returns the value of field advertiser_status
     *
     * @return integer
     */
    public function getAdvertiserStatus()
    {
        return $this->advertiser_status;
    }

    /**
     * Returns the value of field dispatch_at
     *
     * @return string
     */
    public function getDispatchAt()
    {
        return $this->dispatch_at;
    }

    /**
     * Returns the value of field dispatch_code
     *
     * @return integer
     */
    public function getDispatchCode()
    {
        return $this->dispatch_code;
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
     * Returns the value of field expired_at
     *
     * @return string
     */
    public function getExpiredAt()
    {
        return $this->expired_at;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany(
            'id',
            'Entities\CampaignOpportunityParticipationNegotiation',
            'id_campaign_opportunity_participation',
            array('alias' => 'CampaignOpportunityParticipationNegotiation')
        );
        $this->hasMany(
            'id',
            'Entities\CampaignOpportunityParticipationTransaction',
            'id_campaign_opportunity_participation',
            array('alias' => 'CampaignOpportunityParticipationTransaction')
        );
        $this->belongsTo('id_factor_network', 'Entities\FactorNetwork', 'id', array('foreignKey' => true, 'alias' => 'FactorNetwork'));
        $this->belongsTo(
            'id_campaign_opportunity',
            'Entities\CampaignOpportunity',
            'id',
            array('foreignKey' => true, 'alias' => 'CampaignOpportunity')
        );
    }

    public function getSource()
    {
        return 'campaign_opportunity_participation';
    }

}
