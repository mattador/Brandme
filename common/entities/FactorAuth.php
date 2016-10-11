<?php

namespace Entities;

class FactorAuth extends \Phalcon\Mvc\Model
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
    protected $id_role;

    /**
     * @var string
     */
    protected $passhash;

    /**
     * @var string
     */
    protected $last_passhash;

    /**
     * @var string
     */
    protected $recovery_key;

    /**
     * @var string
     */
    protected $persistent_sess_token;

    /**
     * @var string
     */
    protected $persistent_sess_token_expire_at;

    /**
     * @var string
     */
    protected $persistent_sess_token_user_client;

    /**
     * @var string
     */
    protected $secret_question;

    /**
     * @var string
     */
    protected $secret_answer;

    /**
     * @var integer
     */
    protected $remote_support;

    /**
     * @var string
     */
    protected $confirmation_key;

    /**
     * @var string
     */
    protected $confirmed_at;

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
     * Method to set the value of field id_role
     *
     * @param integer $id_role
     * @return $this
     */
    public function setIdRole($id_role)
    {
        $this->id_role = $id_role;

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
     * Method to set the value of field last_passhash
     *
     * @param string $last_passhash
     * @return $this
     */
    public function setLastPasshash($last_passhash)
    {
        $this->last_passhash = $last_passhash;

        return $this;
    }

    /**
     * Method to set the value of field recovery_key
     *
     * @param string $recovery_key
     * @return $this
     */
    public function setRecoveryKey($recovery_key)
    {
        $this->recovery_key = $recovery_key;

        return $this;
    }

    /**
     * Method to set the value of field persistent_sess_token
     *
     * @param string $persistent_sess_token
     * @return $this
     */
    public function setPersistentSessToken($persistent_sess_token)
    {
        $this->persistent_sess_token = $persistent_sess_token;

        return $this;
    }

    /**
     * Method to set the value of field persistent_sess_token_expire_at
     *
     * @param string $persistent_sess_token_expire_at
     * @return $this
     */
    public function setPersistentSessTokenExpireAt($persistent_sess_token_expire_at)
    {
        $this->persistent_sess_token_expire_at = $persistent_sess_token_expire_at;

        return $this;
    }

    /**
     * Method to set the value of field persistent_sess_token_user_client
     *
     * @param string $persistent_sess_token_user_client
     * @return $this
     */
    public function setPersistentSessTokenUserClient($persistent_sess_token_user_client)
    {
        $this->persistent_sess_token_user_client = $persistent_sess_token_user_client;

        return $this;
    }

    /**
     * Method to set the value of field secret_question
     *
     * @param string $secret_question
     * @return $this
     */
    public function setSecretQuestion($secret_question)
    {
        $this->secret_question = $secret_question;

        return $this;
    }

    /**
     * Method to set the value of field secret_answer
     *
     * @param string $secret_answer
     * @return $this
     */
    public function setSecretAnswer($secret_answer)
    {
        $this->secret_answer = $secret_answer;

        return $this;
    }

    /**
     * Method to set the value of field remote_support
     *
     * @param integer $remote_support
     * @return $this
     */
    public function setRemoteSupport($remote_support)
    {
        $this->remote_support = $remote_support;

        return $this;
    }

    /**
     * Method to set the value of field confirmation_key
     *
     * @param string $confirmation_key
     * @return $this
     */
    public function setConfirmationKey($confirmation_key)
    {
        $this->confirmation_key = $confirmation_key;

        return $this;
    }

    /**
     * Method to set the value of field confirmed_at
     *
     * @param string $confirmed_at
     * @return $this
     */
    public function setConfirmedAt($confirmed_at)
    {
        $this->confirmed_at = $confirmed_at;

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
     * Returns the value of field id_role
     *
     * @return integer
     */
    public function getIdRole()
    {
        return $this->id_role;
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
     * Returns the value of field last_passhash
     *
     * @return string
     */
    public function getLastPasshash()
    {
        return $this->last_passhash;
    }

    /**
     * Returns the value of field recovery_key
     *
     * @return string
     */
    public function getRecoveryKey()
    {
        return $this->recovery_key;
    }

    /**
     * Returns the value of field persistent_sess_token
     *
     * @return string
     */
    public function getPersistentSessToken()
    {
        return $this->persistent_sess_token;
    }

    /**
     * Returns the value of field persistent_sess_token_expire_at
     *
     * @return string
     */
    public function getPersistentSessTokenExpireAt()
    {
        return $this->persistent_sess_token_expire_at;
    }

    /**
     * Returns the value of field persistent_sess_token_user_client
     *
     * @return string
     */
    public function getPersistentSessTokenUserClient()
    {
        return $this->persistent_sess_token_user_client;
    }

    /**
     * Returns the value of field secret_question
     *
     * @return string
     */
    public function getSecretQuestion()
    {
        return $this->secret_question;
    }

    /**
     * Returns the value of field secret_answer
     *
     * @return string
     */
    public function getSecretAnswer()
    {
        return $this->secret_answer;
    }

    /**
     * Returns the value of field remote_support
     *
     * @return integer
     */
    public function getRemoteSupport()
    {
        return $this->remote_support;
    }

    /**
     * Returns the value of field confirmation_key
     *
     * @return string
     */
    public function getConfirmationKey()
    {
        return $this->confirmation_key;
    }

    /**
     * Returns the value of field confirmed_at
     *
     * @return string
     */
    public function getConfirmedAt()
    {
        return $this->confirmed_at;
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
        $this->belongsTo('id_role', 'Entities\Role', 'id', array('foreignKey' => true, 'alias' => 'Role'));
    }

    public function getSource()
    {
        return 'factor_auth';
    }

}
