<?php

namespace Frontend\Services\Campaign;

use Common\Services\Campaign\Statuses;
use Entities\CampaignOpportunityParticipation;
use Entities\CampaignOpportunityParticipationNegotiation;
use Frontend\Exception;

/**
 * Class DirectInvite
 *
 * @package Frontend\Services\Campaign
 */
class DirectInvite
{

    /**
     * Directly invite an influencer to participate in an opportunity
     *
     * @param $idOpportunity
     * @param $idFactorNetwork
     * @param $price
     * @return int
     * @throws Exception
     */
    public function invite($idOpportunity, $idFactorNetwork, $price)
    {
        $participation = new CampaignOpportunityParticipation();
        $participation
            ->setIdFactorNetwork($idFactorNetwork)
            ->setIdCampaignOpportunity($idOpportunity)
            //Note that even tough we are setting an opening bid the participation is still considered new, for the creator
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setBid(0.0000)
            ->setBidWithMarkup(0.0000)
            ->setBidWithMarkupReserved(number_format($price, 4))
            ->setIsPaid(0)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $idParticipation = $participation->getId();
        if (!$idParticipation) {
            throw new Exception('Could not create participation: '.var_export($participation->getMessages(), true));
        }

        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setBid(number_format($price / Pricing::STANDARD_MARKUP, 4))
            ->setBidWithMarkup(number_format($price, 4))
            ->setIdCampaignOpportunityParticipation($idParticipation)
            ->setStage(Negotiation::NEGOTIATION_STAGE_BID)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_NOT_APPROVED)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
            ->save();

        return $idParticipation;
    }

}