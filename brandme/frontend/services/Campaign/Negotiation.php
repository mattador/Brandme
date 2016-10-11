<?php

namespace Frontend\Services\Campaign;

use Entities\CampaignOpportunityParticipationNegotiation;
use Common\Services\Sql;

/**
 * Logic to interpret creator/advertiser opportunity participation negotiation life cycle
 *
 * Class Negotiation
 * @package Frontend\Services\Campaign
 * @see table campaign_opportunity_participation_negotiation
 */
class Negotiation
{

    const NEGOTIATION_ENTITY_CREATOR = 1;
    const NEGOTIATION_ENTITY_ADVERTISER = 2;

    const NEGOTIATION_STAGE_BID = 1;
    const NEGOTIATION_STAGE_CONTENT = 2;

    const NEGOTIATION_STATUS_APPROVED = 1;
    const NEGOTIATION_STATUS_NOT_APPROVED = 2;

    protected $negotiation = [
        self::NEGOTIATION_STAGE_BID => [],
        self::NEGOTIATION_STAGE_CONTENT => []
    ];

    public function __construct($idParticipation)
    {
        $sql = 'SELECT copn.*, co.type opportunity_type
                FROM campaign_opportunity_participation_negotiation copn
                INNER JOIN campaign_opportunity_participation cop ON cop.id = copn.id_campaign_opportunity_participation
                INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                WHERE cop.id = ' . $idParticipation;
        $negotiations = Sql::find($sql);
        /** @var CampaignOpportunityParticipationNegotiation $negotiation */
        foreach ($negotiations as $negotiation) {
            if ($negotiation['stage'] == self::NEGOTIATION_STAGE_BID) {
                $this->negotiation[self::NEGOTIATION_STAGE_BID][] = $negotiation;
                continue;
            }
            $this->negotiation[self::NEGOTIATION_STAGE_CONTENT][] = $negotiation;
        }
    }

    /**
     * Return read only copy of negotiation
     *
     * @param int $stage
     * @return mixed
     */
    public function getNegotiation($stage = false)
    {
        if ($stage && isset($this->negotiation[$stage])) {
            return $this->negotiation[$stage];
        }
        return $this->negotiation;
    }

    /**
     * Determines whether negotiating has begun but has not finished
     *
     * @return bool
     */
    public function hasNegotiatingStarted()
    {
        return count($this->negotiation[self::NEGOTIATION_STAGE_BID]) && !$this->isNegotiationComplete();
    }

    /**
     * Returns active negotiation stage: negotiation is a two stage process
     *
     * @return mixed
     */
    public function getActiveNegotiationStage()
    {
        if ($this->isNegotiationComplete()) {
            return false;
        }
        if (empty($this->negotiation[self::NEGOTIATION_STAGE_BID]) || !$this->isBidApproved()) {
            return self::NEGOTIATION_STAGE_BID;
        }
        return self::NEGOTIATION_STAGE_CONTENT;
    }

    /**
     * Return the latest element in specified negotiation stage
     *
     * @param $stage
     * @param int $negotiator
     * @return mixed
     */
    public function getLatestNegotiationElement($stage, $negotiator = null)
    {
        if (!is_null($negotiator)) {
            $stageNegotiations = array_reverse($this->negotiation[$stage]);
            foreach ($stageNegotiations as $negotiation) {
                if ($negotiation['entity'] == $negotiator) {
                    return $negotiation;
                }
            }
            return false;
        }
        if (isset($this->negotiation[$stage][count($this->negotiation[$stage]) - 1])) {
            return $this->negotiation[$stage][count($this->negotiation[$stage]) - 1];
        }
        return false;
    }

    /**
     * Check if negotiator (negotiating party) needs to respond
     *
     * @param int $negotiator
     * @return bool
     */
    public function actionRequiredBy($negotiator)
    {
        $stage = $this->getActiveNegotiationStage();
        if (!$stage) {
            return false; //negotiation has finished
        }
        /**
         *  If a stage is uninitialized the creator starts, there's logic behind this:
         *
         *  a.  If the opportunity is of type open, the creator starts the bid process, and since the
         *          creator creates the content the creator must therefore be the first in the content process
         *  b.  If the opportunity is of type direct, the advertiser starts the process before we even get to this point
         *          so the bid process process should never be empty, and since the
         *          creator creates the content the creator must therefore be the first in the content process.
         */
        if (empty($this->negotiation[$stage])) {
            return $negotiator == self::NEGOTIATION_ENTITY_CREATOR ? true : false;
        } else {
            return $this->negotiation[$stage][count($this->negotiation[$stage]) - 1]['entity'] != $negotiator;
        }
    }

    /**
     * Negotiation is considered as finished once content has been approved
     *
     * @return bool
     */
    public function isNegotiationComplete()
    {
        return $this->isBidApproved() && $this->isContentApproved();
    }

    /**
     * Checks if content is approved
     *
     * @return bool
     */
    public function isContentApproved()
    {
        if (count($this->negotiation[self::NEGOTIATION_STAGE_CONTENT])) {
            return $this->negotiation[self::NEGOTIATION_STAGE_CONTENT]
            [count($this->negotiation[self::NEGOTIATION_STAGE_CONTENT]) - 1]['status'] == self::NEGOTIATION_STATUS_APPROVED;
        }
        return false;
    }

    /**
     * Checks if bid is approved
     *
     * @return bool
     */
    public function isBidApproved()
    {
        if (count($this->negotiation[self::NEGOTIATION_STAGE_BID])) {
            return $this->negotiation[self::NEGOTIATION_STAGE_BID]
            [count($this->negotiation[self::NEGOTIATION_STAGE_BID]) - 1]['status'] == self::NEGOTIATION_STATUS_APPROVED;
        }
        return false;
    }

}