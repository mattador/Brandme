<?php

namespace Entities;

class CampaignOpportunityParticipationTransaction extends \Phalcon\Mvc\Model
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
    protected $id_entity_transaction;

    /**
     * @var integer
     */
    protected $entity_type;

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
     * Method to set the value of field id_entity_transaction
     *
     * @param integer $id_entity_transaction
     * @return $this
     */
    public function setIdEntityTransaction($id_entity_transaction)
    {
        $this->id_entity_transaction = $id_entity_transaction;

        return $this;
    }

    /**
     * Method to set the value of field entity_type
     *
     * @param integer $entity_type
     * @return $this
     */
    public function setEntityType($entity_type)
    {
        $this->entity_type = $entity_type;

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
     * Returns the value of field id_entity_transaction
     *
     * @return integer
     */
    public function getIdEntityTransaction()
    {
        return $this->id_entity_transaction;
    }

    /**
     * Returns the value of field entity_type
     *
     * @return integer
     */
    public function getEntityType()
    {
        return $this->entity_type;
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
        return 'campaign_opportunity_participation_transaction';
    }

}
