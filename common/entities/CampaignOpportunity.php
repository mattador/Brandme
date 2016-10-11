<?php

namespace Entities;

class CampaignOpportunity extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $id_campaign;

    /**
     * @var integer
     */
    protected $id_network;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $logo;

    /**
     * @var string
     */
    protected $content_type;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var double
     */
    protected $maximum_bid;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $requirements;

    /**
     * @var string
     */
    protected $ideal_creator;

    /**
     * @var string
     */
    protected $link;

    /**
     * @var integer
     */
    protected $link_required;

    /**
     * @var string
     */
    protected $hashtag;

    /**
     * @var integer
     */
    protected $hashtag_required;

    /**
     * @var string
     */
    protected $mention;

    /**
     * @var integer
     */
    protected $mention_required;

    /**
     * @var string
     */
    protected $postcode;

    /**
     * @var string
     */
    protected $date_start;

    /**
     * @var integer
     */
    protected $status;

    /**
     * @var string
     */
    protected $rejection_status_message;

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
     * Method to set the value of field id_campaign
     *
     * @param integer $id_campaign
     * @return $this
     */
    public function setIdCampaign($id_campaign)
    {
        $this->id_campaign = $id_campaign;

        return $this;
    }

    /**
     * Method to set the value of field id_network
     *
     * @param integer $id_network
     * @return $this
     */
    public function setIdNetwork($id_network)
    {
        $this->id_network = $id_network;

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
     * Method to set the value of field logo
     *
     * @param string $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Method to set the value of field content_type
     *
     * @param string $content_type
     * @return $this
     */
    public function setContentType($content_type)
    {
        $this->content_type = $content_type;

        return $this;
    }

    /**
     * Method to set the value of field type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Method to set the value of field maximum_bid
     *
     * @param double $maximum_bid
     * @return $this
     */
    public function setMaximumBid($maximum_bid)
    {
        $this->maximum_bid = $maximum_bid;

        return $this;
    }

    /**
     * Method to set the value of field description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Method to set the value of field requirements
     *
     * @param string $requirements
     * @return $this
     */
    public function setRequirements($requirements)
    {
        $this->requirements = $requirements;

        return $this;
    }

    /**
     * Method to set the value of field ideal_creator
     *
     * @param string $ideal_creator
     * @return $this
     */
    public function setIdealCreator($ideal_creator)
    {
        $this->ideal_creator = $ideal_creator;

        return $this;
    }

    /**
     * Method to set the value of field link
     *
     * @param string $link
     * @return $this
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Method to set the value of field link_required
     *
     * @param integer $link_required
     * @return $this
     */
    public function setLinkRequired($link_required)
    {
        $this->link_required = $link_required;

        return $this;
    }

    /**
     * Method to set the value of field hashtag
     *
     * @param string $hashtag
     * @return $this
     */
    public function setHashtag($hashtag)
    {
        $this->hashtag = $hashtag;

        return $this;
    }

    /**
     * Method to set the value of field hashtag_required
     *
     * @param integer $hashtag_required
     * @return $this
     */
    public function setHashtagRequired($hashtag_required)
    {
        $this->hashtag_required = $hashtag_required;

        return $this;
    }

    /**
     * Method to set the value of field mention
     *
     * @param string $mention
     * @return $this
     */
    public function setMention($mention)
    {
        $this->mention = $mention;

        return $this;
    }

    /**
     * Method to set the value of field mention_required
     *
     * @param integer $mention_required
     * @return $this
     */
    public function setMentionRequired($mention_required)
    {
        $this->mention_required = $mention_required;

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
     * Method to set the value of field date_start
     *
     * @param string $date_start
     * @return $this
     */
    public function setDateStart($date_start)
    {
        $this->date_start = $date_start;

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
     * Method to set the value of field rejection_status_message
     *
     * @param string $rejection_status_message
     * @return $this
     */
    public function setRejectionStatusMessage($rejection_status_message)
    {
        $this->rejection_status_message = $rejection_status_message;

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
     * Returns the value of field id_campaign
     *
     * @return integer
     */
    public function getIdCampaign()
    {
        return $this->id_campaign;
    }

    /**
     * Returns the value of field id_network
     *
     * @return integer
     */
    public function getIdNetwork()
    {
        return $this->id_network;
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
     * Returns the value of field logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Returns the value of field content_type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Returns the value of field type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the value of field maximum_bid
     *
     * @return double
     */
    public function getMaximumBid()
    {
        return $this->maximum_bid;
    }

    /**
     * Returns the value of field description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the value of field requirements
     *
     * @return string
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * Returns the value of field ideal_creator
     *
     * @return string
     */
    public function getIdealCreator()
    {
        return $this->ideal_creator;
    }

    /**
     * Returns the value of field link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Returns the value of field link_required
     *
     * @return integer
     */
    public function getLinkRequired()
    {
        return $this->link_required;
    }

    /**
     * Returns the value of field hashtag
     *
     * @return string
     */
    public function getHashtag()
    {
        return $this->hashtag;
    }

    /**
     * Returns the value of field hashtag_required
     *
     * @return integer
     */
    public function getHashtagRequired()
    {
        return $this->hashtag_required;
    }

    /**
     * Returns the value of field mention
     *
     * @return string
     */
    public function getMention()
    {
        return $this->mention;
    }

    /**
     * Returns the value of field mention_required
     *
     * @return integer
     */
    public function getMentionRequired()
    {
        return $this->mention_required;
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
     * Returns the value of field date_start
     *
     * @return string
     */
    public function getDateStart()
    {
        return $this->date_start;
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
     * Returns the value of field rejection_status_message
     *
     * @return string
     */
    public function getRejectionStatusMessage()
    {
        return $this->rejection_status_message;
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
            'Entities\CampaignOpportunityParticipation',
            'id_campaign_opportunity',
            array('alias' => 'CampaignOpportunityParticipation')
        );
        $this->hasMany(
            'id',
            'Entities\CampaignOpportunitySegmentation',
            'id_campaign_opportunity',
            array('alias' => 'CampaignOpportunitySegmentation')
        );
        $this->belongsTo('id_campaign', 'Entities\Campaign', 'id', array('foreignKey' => true, 'alias' => 'Campaign'));
        $this->belongsTo('id_network', 'Entities\Network', 'id', array('foreignKey' => true, 'alias' => 'Network'));
    }

    public function getSource()
    {
        return 'campaign_opportunity';
    }

}
