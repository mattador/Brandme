<?php

namespace Entities;

class FactorNetwork extends \Phalcon\Mvc\Model
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
    protected $id_network;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $account_id;

    /**
     * @var string
     */
    protected $account_alias;

    /**
     * @var string
     */
    protected $oauth_token;

    /**
     * @var string
     */
    protected $oauth_token_secret;

    /**
     * @var string
     */
    protected $oauth_refresh_token;

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
     * Method to set the value of field status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Method to set the value of field account_id
     *
     * @param string $account_id
     * @return $this
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;

        return $this;
    }

    /**
     * Method to set the value of field account_alias
     *
     * @param string $account_alias
     * @return $this
     */
    public function setAccountAlias($account_alias)
    {
        $this->account_alias = $account_alias;

        return $this;
    }

    /**
     * Method to set the value of field oauth_token
     *
     * @param string $oauth_token
     * @return $this
     */
    public function setOauthToken($oauth_token)
    {
        $this->oauth_token = $oauth_token;

        return $this;
    }

    /**
     * Method to set the value of field oauth_token_secret
     *
     * @param string $oauth_token_secret
     * @return $this
     */
    public function setOauthTokenSecret($oauth_token_secret)
    {
        $this->oauth_token_secret = $oauth_token_secret;

        return $this;
    }

    /**
     * Method to set the value of field oauth_refresh_token
     *
     * @param string $oauth_refresh_token
     * @return $this
     */
    public function setOauthRefreshToken($oauth_refresh_token)
    {
        $this->oauth_refresh_token = $oauth_refresh_token;

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
     * Returns the value of field id_network
     *
     * @return integer
     */
    public function getIdNetwork()
    {
        return $this->id_network;
    }

    /**
     * Returns the value of field status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Returns the value of field account_id
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * Returns the value of field account_alias
     *
     * @return string
     */
    public function getAccountAlias()
    {
        return $this->account_alias;
    }

    /**
     * Returns the value of field oauth_token
     *
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauth_token;
    }

    /**
     * Returns the value of field oauth_token_secret
     *
     * @return string
     */
    public function getOauthTokenSecret()
    {
        return $this->oauth_token_secret;
    }

    /**
     * Returns the value of field oauth_refresh_token
     *
     * @return string
     */
    public function getOauthRefreshToken()
    {
        return $this->oauth_refresh_token;
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
            'id_factor_network',
            array('alias' => 'CampaignOpportunityParticipation')
        );
        $this->hasOne('id', 'Entities\FactorNetworkMeta', 'id_factor_network', array('alias' => 'FactorNetworkMeta'));
        $this->hasMany('id', 'Entities\FactorNetworkSegmentation', 'id_factor_network', array('alias' => 'FactorNetworkSegmentation'));
        $this->belongsTo('id_factor', 'Entities\Factor', 'id', array('foreignKey' => true, 'alias' => 'Factor'));
        $this->belongsTo('id_network', 'Entities\Network', 'id', array('foreignKey' => true, 'alias' => 'Network'));
    }

    public function getSource()
    {
        return 'factor_network';
    }

}
