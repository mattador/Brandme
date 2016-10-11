<?php

namespace Entities;

class CampaignOpportunityParticipationNegotiation extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $id_campaign_opportunity_participation;

    /**
     * @var integer
     */
    protected $entity;

    /**
     * @var integer
     */
    protected $stage;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var double
     */
    protected $bid;

    /**
     * @var double
     */
    protected $bid_with_markup;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $content_suggestion;

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
     * Method to set the value of field id_campaign_opportunity_participation
     *
     * @param integer $id_campaign_opportunity_participation
     * @return $this
     */
    public function setIdCampaignOpportunityParticipation($id_campaign_opportunity_participation)
    {
        $this->id_campaign_opportunity_participation = $id_campaign_opportunity_participation;

        return $this;
    }

    /**
     * Method to set the value of field entity
     *
     * @param integer $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Method to set the value of field stage
     *
     * @param integer $stage
     * @return $this
     */
    public function setStage($stage)
    {
        $this->stage = $stage;

        return $this;
    }

    /**
     * Method to set the value of field status
     *
     * @param integer $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * Method to set the value of field content_suggestion
     *
     * @param string $content_suggestion
     * @return $this
     */
    public function setContentSuggestion($content_suggestion)
    {
        $this->content_suggestion = $content_suggestion;

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
     * Returns the value of field id_campaign_opportunity_participation
     *
     * @return integer
     */
    public function getIdCampaignOpportunityParticipation()
    {
        return $this->id_campaign_opportunity_participation;
    }

    /**
     * Returns the value of field entity
     *
     * @return integer
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the value of field stage
     *
     * @return integer
     */
    public function getStage()
    {
        return $this->stage;
    }

    /**
     * Returns the value of field status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
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
     * Returns the value of field content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Returns the value of field content_suggestion
     *
     * @return string
     */
    public function getContentSuggestion()
    {
        return $this->content_suggestion;
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
        $this->belongsTo(
            'id_campaign_opportunity_participation',
            'Entities\CampaignOpportunityParticipation',
            'id',
            array('foreignKey' => true, 'alias' => 'CampaignOpportunityParticipation')
        );
    }

    public function getSource()
    {
        return 'campaign_opportunity_participation_negotiation';
    }

}
