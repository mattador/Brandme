<?php

namespace Frontend\Services\Campaign\Opportunity;

use Frontend\Module;
use Entities\CampaignOpportunity;
use Entities\CampaignOpportunityParticipation;
use Entities\CampaignOpportunityParticipationNegotiation;
use Entities\FactorActivity;
use Entities\FactorNetwork;
use Frontend\Exception;
use Frontend\Services\AbstractService;
use Frontend\Services\Campaign\Negotiation;
use Frontend\Services\Campaign\Pricing;
use Common\Services\Campaign\Statuses;
use Common\Services\Sql;
use Frontend\Widgets\Translate;

class Actions extends AbstractService
{

    const EXPIRY_TIMEOUT_IN_DAYS = 3;

    /**
     * Pausing an opportunity will cause it to disappear from the creators dashboard,
     * regardless of whether the participation has already been initialized
     *
     * @param     $idOpportunity
     * @param int $status
     * @return bool
     */
    public function pauseOpportunity($idOpportunity, $status = Statuses::OPPORTUNITY_PAUSED)
    {
        /** @var CampaignOpportunity $opportunity */
        $opportunity = CampaignOpportunity::findFirst('id = '.$idOpportunity);
        $opportunity
            ->setStatus($status)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();
        //remove reserved bid funds from opportunity
        Sql::write(
            'UPDATE campaign_opportunity_participation SET bid_with_markup_reserved = "0.00" WHERE id_campaign_opportunity = '
            .$idOpportunity
        );

        return true;
    }

    /**
     * Resume a paused opportunity
     *
     * @param $idOpportunity
     * @return bool
     */
    public function resumeOpportunity($idOpportunity)
    {
        /** @var CampaignOpportunity $opportunity */
        $opportunity = CampaignOpportunity::findFirst('id = '.$idOpportunity);
        //Determine if we set the opportunity to pending execution or executing
        $timezone = $opportunity
            ->getCampaign()
            ->getFactor()
            ->getFactorMeta()
            ->getTimezone();
        $isPendingExecution = strtotime(Module::getService('Time')->timezoneTimeToUtc($opportunity->getDateStart(), $timezone)) > time();
        $opportunity
            ->setStatus($isPendingExecution ? Statuses::OPPORTUNITY_PENDING_EXECUTION : Statuses::OPPORTUNITY_EXECUTING)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Using negotiation history, reactivate an expired participation to previous state
     *
     * @param $idParticipation
     * @return bool
     */
    public function reactivateCampaignOpportunityParticipation($idParticipation)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        /** @var CampaignOpportunity $opportunity */
        $opportunity = CampaignOpportunity::findFirst('id ='.$participation->getIdCampaignOpportunity());
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isNegotiationComplete()) {
            $this->flash->error(Translate::_('You cannot reactivate a participation in which negotiations have already been completed'));

            return false;
        }

        if (Module::getService('Campaign/Opportunity')->getCreditLeftInCampaign($opportunity->getIdCampaign()) < Pricing::STANDARD_MARKUP) {
            $this->flash->error(Translate::_('There is not enough credit left in this campaign to resume the participation'));

            return false;
        }
        if (!$negotiation->hasNegotiatingStarted()) {
            $participation
                ->setCreatorStatus(
                    $opportunity->getType() == 'open' ? Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID
                        : Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER
                )
                ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->setExpiredAt(null)
                ->update();
        } else {
            if ($negotiation->actionRequiredBy(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)) {
                $participation
                    ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING)
                    ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN)
                    ->setUpdatedAt(date('Y-m-d H:i:s'))
                    ->setExpiredAt(null)
                    ->update();
            } else {
                $participation
                    ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION)
                    ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
                    ->setUpdatedAt(date('Y-m-d H:i:s'))
                    ->setExpiredAt(null)
                    ->update();
            }
        }

        return true;
    }

    /**
     * Verify that an opportunity belongs to specified advertiser
     *
     * @param $idAdvertiser
     * @param $idCampaign
     * @param $idOpportunity
     * @return bool
     */
    public function checkCampaignOpportunityOwnership($idAdvertiser, $idCampaign, $idOpportunity)
    {
        $sql
            = 'SELECT
                  COUNT(co.id) as cnt
                FROM campaign c
                INNER JOIN campaign_opportunity co ON co.id_campaign = c.id
                WHERE c.id = '.$idCampaign.' AND co.id = '.$idOpportunity.' AND c.id_factor = '.$idAdvertiser;

        return (bool)intval(Sql::find($sql)[0]['cnt']);
    }


    /**
     * Verify that a campaign belongs to specified advertiser
     *
     * @param $idAdvertiser
     * @param $idCampaign
     * @return bool
     */
    public function checkCampaignOwnership($idAdvertiser, $idCampaign)
    {
        $sql
            = 'SELECT
                  COUNT(c.id) as cnt
                FROM campaign c
                WHERE c.id = '.$idCampaign.' AND c.id_factor = '.$idAdvertiser;

        return (bool)intval(Sql::find($sql)[0]['cnt']);
    }


    /**
     * Advertiser rejects a participant's participation
     *
     * @param $idParticipation
     * @return bool
     */
    public function rejectCampaignOpportunityParticipation($idParticipation)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_CANCELED)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_CLOSED)
            ->setBidWithMarkupReserved(0.00)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Advertiser approves bid - updates negotiation object
     *
     * @param $idCampaign
     * @param $idParticipation
     * @return bool
     */
    public function approveCampaignOpportunityParticipationBid($idCampaign, $idParticipation)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isBidApproved()) {
            //already approved
            return false;
        }
        $approvedBid = $negotiation->getLatestNegotiationElement(Negotiation::NEGOTIATION_STAGE_BID);
        //is there enough money left in campaign?
        $creditLeft = Module::getService('Campaign\Opportunity')
            ->getCreditLeftInCampaign($idCampaign);
        if (($creditLeft - $approvedBid['bid_with_markup'] + $participation->getBidWithMarkupReserved()) > Pricing::STANDARD_MARKUP) {
            $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
            $participationNegotiation
                ->setIdCampaignOpportunityParticipation($participation->getId())
                ->setStage(Negotiation::NEGOTIATION_STAGE_BID)
                ->setBid($approvedBid['bid'])
                ->setBidWithMarkup($approvedBid['bid_with_markup'])
                ->setStatus(Negotiation::NEGOTIATION_STATUS_APPROVED)
                ->setEntity(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->save();

            $participation
                ->setBid($approvedBid['bid'])
                ->setBidWithMarkup($approvedBid['bid_with_markup'])
                ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_READY_FOR_CONTENT)
                ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
                ->setBidWithMarkupReserved(0.00)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->update();

            $campaignOpportunity = $participation->getCampaignOpportunity();
            $factorNetworkMeta = $participation->getFactorNetwork()->getFactorNetworkMeta();
            $factor = $participation->getFactorNetwork()->getFactor();
            $factorMeta = $factor->getFactorMeta();
            $activity = new FactorActivity();
            $activity
                ->setIdFactor($factor->getId())
                ->setMessage(
                    Translate::_(
                        'Your offer for "%opportunity_name%" has been approved',
                        ['opportunity_name' => $participation->getCampaignOpportunity()->getName()]
                    )
                )
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->create();

            $mail = $this->getDI()->get('email');
            $mail->send(
                $factor->getEmail(),
                'offer_approved',
                'Brandme - Confirmar correo',
                [
                    'login_url'        => APPLICATION_HOST.'/',
                    'referral_url'     => APPLICATION_HOST.'/registro/'.$factorMeta->getReferralCode(),
                    'avatar'           => json_decode($factorNetworkMeta->getJson())->photoURL,
                    'name'             => $factorMeta->getFirstName(),
                    'opportunity_name' => $campaignOpportunity->getName()
                ]
            );

            return true;
        } else {
            $this->flash->error(Translate::_('There is not enough money left in this campaign to approve this bid'));

            return false;
        }
    }


    /**
     * Advertiser negotiates bid - updates negotiation object
     *
     * @param $idCampaign
     * @param $idParticipation
     * @param $suggestedBid
     * @return bool
     */
    public function negotiateAdvertiserCampaignOpportunityParticipationBid($idCampaign, $idParticipation, $suggestedBid)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isBidApproved()) {
            //The bid has already been approved
            return true;
        }
        $creditLeft = Module::getService('Campaign\Opportunity')->getCreditLeftInCampaign($idCampaign);
        if (($creditLeft - $suggestedBid + $participation->getBidWithMarkupReserved()) < 0) {
            $this->flash->error(Translate::_('There is not enough credit left to in the campaign to cover the suggested cost'));

            return false;
        }
        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($participation->getId())
            ->setStage(Negotiation::NEGOTIATION_STAGE_BID)
            ->setBid($suggestedBid / Pricing::STANDARD_MARKUP)
            ->setBidWithMarkup($suggestedBid)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_NOT_APPROVED)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setBidWithMarkupReserved($suggestedBid)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Check if creator has genuinely been invited to participate in an opportunity
     *
     * @param $idCreator
     * @param $idOpportunity
     * @param $idFactorNetwork
     * @return bool
     */
    public function checkCreatorCampaignOpportunityParticipationOwnership($idCreator, $idOpportunity, $idFactorNetwork)
    {
        $sql
            = 'SELECT COUNT(cop.id) cnt FROM campaign_opportunity_participation cop
                INNER JOIN factor_network fn ON fn.id = cop.id_factor_network
                WHERE cop.id_campaign_opportunity = '.$idOpportunity.'
                AND cop.id_factor_network = '.$idFactorNetwork.'
                AND fn.id_factor = '.$idCreator;

        return (bool)intval(Sql::find($sql)[0]['cnt']);
    }

    /**
     * Creator rejects a participation
     *
     * @param $idOpportunity
     * @param $idFactorNetwork
     */
    public function rejectCreatorCampaignOpportunityParticipation($idOpportunity, $idFactorNetwork)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst(
            'id_factor_network = '.$idFactorNetwork.' AND id_campaign_opportunity = '.$idOpportunity
        );
        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_REJECTED)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_REJECTED)
            ->setBidWithMarkupReserved(0.00)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();
    }

    /**
     * Creator bids on an opportunity
     *
     * @param $idFactor
     * @param $idOpportunity
     * @param $idFactorNetwork
     * @param $amount
     * @param $pitch
     * @return bool
     */
    public function bidCreatorCampaignOpportunityParticipation($idFactor, $idOpportunity, $idFactorNetwork, $amount, $pitch)
    {
        if (!intval(FactorNetwork::count('id = '.$idFactorNetwork.' AND id_factor  = '.$idFactor))) {
            return false;
        }
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst(
            'id_factor_network = '.$idFactorNetwork.' AND id_campaign_opportunity = '.$idOpportunity
        );
        $negotiation = new Negotiation($participation->getId());
        if ($negotiation->isBidApproved()) {
            //already approved
            return false;
        }
        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($participation->getId())
            ->setStage(Negotiation::NEGOTIATION_STAGE_BID)
            ->setBid($amount)
            ->setBidWithMarkup($amount * Pricing::STANDARD_MARKUP)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_NOT_APPROVED)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_CREATOR)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN)
            ->setPitch($pitch)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Advertiser approves content - updates negotiation object
     *
     * @param $idParticipation
     * @param $dispatchTimestamp
     * @return bool
     */
    public function approveCampaignOpportunityParticipationContent($idParticipation, $dispatchTimestamp = null)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isContentApproved()) {
            //already approved
            return false;
        }
        $creatorContent = $negotiation->getLatestNegotiationElement(
            Negotiation::NEGOTIATION_STAGE_CONTENT,
            Negotiation::NEGOTIATION_ENTITY_CREATOR
        )['content'];
        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($participation->getId())
            ->setStage(Negotiation::NEGOTIATION_STAGE_CONTENT)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_APPROVED)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $participation
            ->setContent($creatorContent)
            ->setDispatchAt(is_null($dispatchTimestamp) ? date('Y-m-d H:i:s') : $dispatchTimestamp)
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING_PUBLISHING)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING_DISPATCH)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Creator creates content for an opportunity
     *
     * @param $idFactor
     * @param $idOpportunity
     * @param $idFactorNetwork
     * @param $content
     * @return bool
     */
    public function createContentCreatorCampaignOpportunityParticipation($idFactor, $idOpportunity, $idFactorNetwork, $content)
    {
        if (!intval(FactorNetwork::count('id = '.$idFactorNetwork.' AND id_factor  = '.$idFactor))) {
            return false;
        }
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst(
            'id_factor_network = '.$idFactorNetwork.' AND id_campaign_opportunity = '.$idOpportunity
        );

        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($participation->getId())
            ->setStage(Negotiation::NEGOTIATION_STAGE_CONTENT)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_NOT_APPROVED)
            ->setContent($content)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_CREATOR)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }


    /**
     * Check participation expiry date - we rely on UTC updated_at timestamp for this
     *
     * @param $participation
     */
    public function checkParticipationExpiryDateAndExpireIfNecessary($participation)
    {
        //Don't expire open invites if bidding hasn't begun yet
        if ($participation['creator_status'] == Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID) {
            return $participation;
        }
        $referenceDate = is_null($participation['updated_at']) ? $participation['created_at'] : $participation['updated_at'];
        $expiry = (strtotime($referenceDate.' + '.self::EXPIRY_TIMEOUT_IN_DAYS.' DAY') - time()) / (60 * 60);
        if ($expiry < 0) { //|| round($expiry) == 72
            //Track original advertiser status, it might have been uninitialized,
            $initialized
                = $participation['advertiser_status'] == Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED ? false : true;
            $this->expireCampaignOpportunityParticipation($participation['id_participation'], $initialized);
            $participation['creator_status'] = Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED;
            $participation['advertiser_status'] = $initialized ? Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_EXPIRED
                : Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED;
        } else {
            $participation['expires'] = (
            round($expiry) == 1
                ? '1 '.'Hora'
                :
                (round($expiry) == 0 ? 'A punto de expirar' : round($expiry).' '.'Horas')
            );
        }

        return $participation;
    }

    /**
     * Expires a participation.
     * If a participation was uninitialized it needs to stay that way.
     *
     * @param $idParticipation
     * @param $initialized
     * @return bool
     */
    protected function expireCampaignOpportunityParticipation($idParticipation, $initialized)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED)
            ->setAdvertiserStatus(
                $initialized ? Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_EXPIRED
                    : Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED
            )
            ->setBidWithMarkupReserved(0.00)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->setExpiredAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * Advertiser negotiates content - updates negotiation object
     *
     * @param $idParticipation
     * @param $contentSuggestion
     * @return bool
     */
    public function negotiateAdvertiserCampaignOpportunityParticipationContent($idParticipation, $contentSuggestion)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isContentApproved()) {
            return true; //The content has already been approved
        }

        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($idParticipation)
            ->setStage(Negotiation::NEGOTIATION_STAGE_CONTENT)
            ->setStatus(Negotiation::NEGOTIATION_STATUS_NOT_APPROVED)
            ->setContentSuggestion($contentSuggestion)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();


        $participation
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }

    /**
     * The creator accepts an advertiser's "direct offer" in the bid stage
     *
     * @param $idParticipation
     * @param $idCampaign
     * @return bool
     */
    public function creatorApproveCampaignOpportunityParticipationBid($idParticipation, $idCampaign)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
        $negotiation = new Negotiation($idParticipation);
        if ($negotiation->isBidApproved()) {
            //The bid has already been approved
            return true;
        }
        //Can't approve his own bid, it must be the advertiser's bid
        $approvedBid = $negotiation->getLatestNegotiationElement(
            Negotiation::NEGOTIATION_STAGE_BID,
            Negotiation::NEGOTIATION_ENTITY_ADVERTISER
        );

        //Verify that there is enough money left in the campaign to accept the bid
        $creditLeft = Module::getService('Campaign\Opportunity')->getCreditLeftInCampaign($idCampaign);
        if (($creditLeft - $approvedBid['bid_with_markup'] + $participation->getBidWithMarkupReserved()) < 0) {
            //@todo notify the advertiser by mail that they need to add more credit to their campaign
            return false;
        }
        $participationNegotiation = new CampaignOpportunityParticipationNegotiation();
        $participationNegotiation
            ->setIdCampaignOpportunityParticipation($participation->getId())
            ->setStage(Negotiation::NEGOTIATION_STAGE_BID)
            ->setBid($approvedBid['bid'])
            ->setBidWithMarkup($approvedBid['bid_with_markup'])
            ->setStatus(Negotiation::NEGOTIATION_STATUS_APPROVED)
            ->setEntity(Negotiation::NEGOTIATION_ENTITY_CREATOR)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->save();

        $participation
            ->setBid($approvedBid['bid'])
            ->setBidWithMarkup($approvedBid['bid_with_markup'])
            ->setBidWithMarkupReserved(0.00)
            ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_READY_FOR_CONTENT)
            ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        return true;
    }
}