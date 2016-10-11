<?php

namespace Entities;

class FactorMeta extends \Phalcon\Mvc\Model
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
    protected $id_plan;

    /**
     * @var integer
     */
    protected $is_brand;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $birthdate;

    /**
     * @var string
     */
    protected $gender;

    /**
     * @var string
     */
    protected $first_name;

    /**
     * @var string
     */
    protected $last_name;

    /**
     * @var string
     */
    protected $avatar;

    /**
     * @var string
     */
    protected $banner;

    /**
     * @var string
     */
    protected $referral_code;

    /**
     * @var string
     */
    protected $telephone;

    /**
     * @var string
     */
    protected $bio;

    /**
     * @var string
     */
    protected $stylesheets;

    /**
     * @var string
     */
    protected $content_filter;

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @var integer
     */
    protected $recieve_emails;

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
     * Method to set the value of field id_plan
     *
     * @param integer $id_plan
     * @return $this
     */
    public function setIdPlan($id_plan)
    {
        $this->id_plan = $id_plan;

        return $this;
    }

    /**
     * Method to set the value of field is_brand
     *
     * @param integer $is_brand
     * @return $this
     */
    public function setIsBrand($is_brand)
    {
        $this->is_brand = $is_brand;

        return $this;
    }

    /**
     * Method to set the value of field locale
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Method to set the value of field birthdate
     *
     * @param string $birthdate
     * @return $this
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Method to set the value of field gender
     *
     * @param string $gender
     * @return $this
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Method to set the value of field first_name
     *
     * @param string $first_name
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Method to set the value of field last_name
     *
     * @param string $last_name
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Method to set the value of field avatar
     *
     * @param string $avatar
     * @return $this
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Method to set the value of field banner
     *
     * @param string $banner
     * @return $this
     */
    public function setBanner($banner)
    {
        $this->banner = $banner;

        return $this;
    }

    /**
     * Method to set the value of field referral_code
     *
     * @param string $referral_code
     * @return $this
     */
    public function setReferralCode($referral_code)
    {
        $this->referral_code = $referral_code;

        return $this;
    }

    /**
     * Method to set the value of field telephone
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Method to set the value of field bio
     *
     * @param string $bio
     * @return $this
     */
    public function setBio($bio)
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * Method to set the value of field stylesheets
     *
     * @param string $stylesheets
     * @return $this
     */
    public function setStylesheets($stylesheets)
    {
        $this->stylesheets = $stylesheets;

        return $this;
    }

    /**
     * Method to set the value of field content_filter
     *
     * @param string $content_filter
     * @return $this
     */
    public function setContentFilter($content_filter)
    {
        $this->content_filter = $content_filter;

        return $this;
    }

    /**
     * Method to set the value of field timezone
     *
     * @param string $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Method to set the value of field recieve_emails
     *
     * @param integer $recieve_emails
     * @return $this
     */
    public function setRecieveEmails($recieve_emails)
    {
        $this->recieve_emails = $recieve_emails;

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
     * Returns the value of field id_plan
     *
     * @return integer
     */
    public function getIdPlan()
    {
        return $this->id_plan;
    }

    /**
     * Returns the value of field is_brand
     *
     * @return integer
     */
    public function getIsBrand()
    {
        return $this->is_brand;
    }

    /**
     * Returns the value of field locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Returns the value of field birthdate
     *
     * @return string
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Returns the value of field gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Returns the value of field first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Returns the value of field last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Returns the value of field avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Returns the value of field banner
     *
     * @return string
     */
    public function getBanner()
    {
        return $this->banner;
    }

    /**
     * Returns the value of field referral_code
     *
     * @return string
     */
    public function getReferralCode()
    {
        return $this->referral_code;
    }

    /**
     * Returns the value of field telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Returns the value of field bio
     *
     * @return string
     */
    public function getBio()
    {
        return $this->bio;
    }

    /**
     * Returns the value of field stylesheets
     *
     * @return string
     */
    public function getStylesheets()
    {
        return $this->stylesheets;
    }

    /**
     * Returns the value of field content_filter
     *
     * @return string
     */
    public function getContentFilter()
    {
        return $this->content_filter;
    }

    /**
     * Returns the value of field timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Returns the value of field recieve_emails
     *
     * @return integer
     */
    public function getRecieveEmails()
    {
        return $this->recieve_emails;
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
        $this->belongsTo('id_plan', 'Entities\Plan', 'id', array('foreignKey' => true, 'alias' => 'Plan'));
    }

    public function getSource()
    {
        return 'factor_meta';
    }

}
