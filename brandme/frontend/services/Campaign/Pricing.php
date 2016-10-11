<?php

/**
 * Core price model logic
 *
 * The following explanation defines how Brandme calculates it's profit model.
 *
 * A transaction can be defined simply as an influencer's participation in an advertiser's campaign opportunity.
 *
 * Both Advertisers and Creators belong to a particular "Exchange Platform", which you can think of as a type of user grouping.
 * The default Exchange Platform is owned by Brandme, if it is not, then it is owned by a White Label Partner.
 *
 * All transaction calculations take place after firstly charging the +105% mark up to the advertiser and compensating the agreed amount to the Creator.
 * A cut is then taken out of the +105% and is paid out like this:
 *
 * 1.    25% goes to Brandme
 * 2.    40% goes to the Exchange Platform owner (either Brandme or a White Label Partner) which the Advertiser belongs to
 * 3.    27.5% goes to the Exchange Platform owner (either Brandme or a White Label Partner) which the Influencer belongs to
 * 4.    2.5% goes to the Advertisers referrer
 *          a.    This is a flat amount regardless of how many other Advertisers or influencers the user has referred
 * 5.    10% The influencers referrer
 *          a.    This is the maximum amount the Influencer’s Referrer can earn based on the following matrix:
 *                1  - 9 users referred = 2.5%
 *                10 - 24 users referred = 5%
 *                25 - 99 users referred = 7.5%
 *                100 + users referred = 10%
 *
 *          If the maximum of 10% is not met, the leftover goes to Brandme.
 *
 * In summary Brandme can technically earn the full 100% margin of the 105% markup.
 * On the the other hand Brandme stands to earn a minimum of only 25% of the markup value.
 *
 * Credit is dispersed 30 days after message was posted.
 */

namespace Frontend\Services\Campaign;

use Entities\CampaignOpportunity;
use Entities\CampaignOpportunityParticipation;
use Entities\CampaignOpportunityParticipationTransaction;
use Entities\ExchangeBalance;
use Entities\ExchangeTransaction;
use Entities\Factor;
use Entities\FactorActivity;
use Entities\FactorBalance;
use Entities\FactorNetwork;
use Entities\FactorReference;
use Entities\FactorTransaction;
use Entities\Transaction;
use Frontend\Services\AbstractService;
use Frontend\Widgets\Currency;

/**
 * Class Pricing
 * @package Frontend\Services\Campaign
 */
class Pricing extends AbstractService
{

    private $logger;

    /** +105% on top of base price */
    const STANDARD_MARKUP = 2.05;

    /** How many days after a message is publish does the user get compensated */
    const PAYOUT_PERIOD = 30;//default to 30 days

    /**
     * This set of constants are referred to by the entity_type column of campaign_opportunity_participation_transaction.
     * Use them to link opportunity participation transactions with their respective atomic transaction entities.
     *
     * Entity types 1 and 2 link the id_entity_transaction field to the exchange_transaction table
     * Entity types 3 to 6 link the id_entity_transaction field to the factor_transaction table - these entitiys are linked to 1 and 2 by id_exchange in the factor table
     */
    const ENTITY_BRANDME = 1; //Default exchange
    const ENTITY_WHITE_LABEL_PARTNER = 2; //White label partner exchange
    const ENTITY_ADVERTISER = 3; //user is considered an advertiser for the transaction (an advertiser cannot be an influencer, ever)
    const ENTITY_ADVERTISER_REFERRER = 4; //user acted as a referrer for an advertiser
    const ENTITY_INFLUENCER = 5;  //user is considered an influencer for the transaction (an influencer cannot be an advertiser, ever)
    const ENTITY_INFLUENCER_REFERRER = 6; //user acted as a referrer for an influencer

    /**
     * Log pricing movements
     *
     * @param $msg
     */
    protected function log($msg)
    {
        if (!$this->logger instanceof File) {
            $this->logger = new File(APPLICATION_LOG_DIR . '/payment-' . date('Y-m-d') . '.log', array(
                'mode' => 'a'
            ));
        }
        $this->logger->info($msg);
    }


    /**
     * Returns a Factor's referrer if they exist, false if not
     *
     * @param $idFactor
     * @return bool|int
     *
     */
    public function getFactorReferrerId($idFactor)
    {
        /** @var FactorReference $factorReferrer */
        $factorReferrer = FactorReference::findFirst('id_factor = ' . $idFactor);
        if (!$factorReferrer) {
            return false;
        }
        return $factorReferrer->getIdFactorReferencedBy();
    }

    /**
     * Return the creators negotiated amount for the advertiser after conversion
     *
     * @param $amount
     * @return float
     */
    public static function creatorToAdvertiser($amount)
    {
        return round($amount * self::STANDARD_MARKUP, 2);
    }

    /**
     * Return the advertisers negotiated amount for the creator after conversion
     *
     * @param $amount
     * @return float
     */
    public static function advertiserToCreator($amount)
    {
        return round($amount / self::STANDARD_MARKUP, 2);
    }

    /**
     * Creates opportunity participation transactions
     * For any doubts regarding the calculations here please refer to README.md#Pricing Model
     *
     * @param $idParticipation
     * @param $idAdvertiser
     * @return bool
     */
    public function distributeFunds($idParticipation, $idAdvertiser)
    {
        /** @var CampaignOpportunityParticipation $participation */
        $participation = CampaignOpportunityParticipation::findFirst('id = ' . $idParticipation);

        if (intval($participation->getIsPaid())) {
            //The participation has since been paid, pehaps by another overlapping process
            return false;
        }
        /** @var CampaignOpportunity $opportunity */
        $opportunity = $participation->getCampaignOpportunity();

        /** @var Factor $advertiser */
        $advertiser = Factor::findFirst('id = ' . $idAdvertiser);

        /** @var FactorBalance $advertiserBalance */
        $advertiserBalance = $advertiser->getFactorBalance();

        //Verify that the advertiser has enough reserved funds to pay for participation
        $bidWithMarkup = $participation->getBidWithMarkup();
        $reserved = $advertiserBalance->getReserved();
        if ($reserved < $bidWithMarkup) {
            $this->log('Advertiser #' . $idAdvertiser . ' does not have enough reserved credit to pay for participation #' . $idParticipation . '. $' . $reserved . ' < $' . $bidWithMarkup);
            return false;
        }

        //Reduce $bidWithMarkup from the reserved funds
        $advertiserBalance
            ->setReserved($reserved - $bidWithMarkup)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        //Create transaction - 3 step process in transaction > factor_transaction and > campaign_opportunity_participation_transaction tables
        $this->setParticipationTransaction(
            $participation->getId(),
            -$bidWithMarkup,
            'charge',
            'complete',
            'Cobro por servicio de participación en campaña "' . $opportunity->getName() . '"',
            $idAdvertiser,
            self::ENTITY_ADVERTISER,
            '$' . Currency::format($bidWithMarkup) . ' ha sido retirado de tu mondero reservado para cubrir el costo de una participation para la campaña "' . $opportunity->getName() . '"'
        );
        //Pay the creator
        $bidWithoutMarkup = $participation->getBid();

        $idCreator = FactorNetwork::findFirst('id = ' . $participation->getIdFactorNetwork())->getIdFactor();

        /** @var FactorBalance $factorBalance */
        $factorBalance = FactorBalance::findFirst('id_factor = ' . $idCreator);

        //Increase the creators balance
        $factorBalance
            ->setBalance($factorBalance->getBalance() + $bidWithoutMarkup)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        $this->setParticipationTransaction(
            $participation->getId(),
            $bidWithoutMarkup,
            'deposit',
            'complete',
            'Pago por participación en la campaña "' . $opportunity->getName() . '"',
            $idCreator,
            self::ENTITY_INFLUENCER,
            '$' . Currency::format($bidWithoutMarkup) . ' ha sido depositado en tu mondero por haber participado en la campaña "' . $opportunity->getName() . '"'
        );

        //Split up the remaining 105% and pay the other entities (i.e. Brandme, Exchanges and Referrers)
        $remainingShare = $bidWithMarkup - $bidWithoutMarkup;

        //Brandme's Exchange basic share is 25% - this can grow depending on the logic below
        $brandmeExchangeShare = $remainingShare * 0.25;

        //Calculate the influencer's referrer share based on how other people they have referred
        $influencerReferrerPercentage = 0;
        $idInfluencerReferrer = $this->getFactorReferrerId($idCreator);
        if ($idInfluencerReferrer !== false) {
            $count = FactorReference::count('id_factor_referenced_by = ' . $idInfluencerReferrer);
            if ($count > 0 && $count < 10) {
                $influencerReferrerPercentage = 0.025;
            } elseif ($count > 9 && $count < 25) {
                $influencerReferrerPercentage = 0.05;
            } elseif ($count > 24 && $count < 100) {
                $influencerReferrerPercentage = 0.075;
            } elseif ($count > 100) {
                $influencerReferrerPercentage = 0.1;
            }
        }
        $influencerReferrerShare = $remainingShare * $influencerReferrerPercentage;
        if ($influencerReferrerShare > 0) {
            //reward the creator's referrer

            /** @var FactorBalance $factorInfluencerReferrerBalance */
            $factorInfluencerReferrerBalance = FactorBalance::findFirst('id_factor = ' . $idInfluencerReferrer);

            //Increase the influencer's referrer's balance
            $factorInfluencerReferrerBalance
                ->setBalance($factorInfluencerReferrerBalance->getBalance() + $influencerReferrerShare)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->update();

            $this->setParticipationTransaction(
                $participation->getId(),
                $influencerReferrerShare,
                'deposit',
                'complete',
                'Pago por participación de alguien que has referenciado en la campaña "' . $opportunity->getName() . '"',
                $idInfluencerReferrer,
                self::ENTITY_INFLUENCER_REFERRER,
                '$' . Currency::format($influencerReferrerShare) . ' ha sido depositado en tu mondero por haber referenciado a alguien quien participó en la campaña "' . $opportunity->getName() . '"'
            );
        }

        //If the full 10 percent is not take by the $influencerReferrerShare, add it to the Brandme's Exchange
        if (($remainingShare * 0.1) - $influencerReferrerShare > 0) {
            $brandmeExchangeShare += (($remainingShare * 0.1) - $influencerReferrerShare);
        }

        //Pay the advertiser's referrer, if one exists. They advertiser's referrer earns a flat rate of 2.5% of the markup value
        $idAdvertiserReferrer = $this->getFactorReferrerId($idAdvertiser);
        $advertiserReferrerShare = 0;
        if ($idAdvertiserReferrer !== false) {
            $advertiserReferrerShare = $remainingShare * 0.025;
            /** @var FactorBalance $factorAdvertiserReferrerBalance */
            $factorAdvertiserReferrerBalance = FactorBalance::findFirst('id_factor = ' . $idAdvertiserReferrer);

            //Increase the advertisers's referrer's balance
            $factorAdvertiserReferrerBalance
                ->setBalance($factorAdvertiserReferrerBalance->getBalance() + $advertiserReferrerShare)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->update();

            $this->setParticipationTransaction(
                $participation->getId(),
                $advertiserReferrerShare,
                'deposit',
                'complete',
                'Pago por participación de alguien que has referenciado en la campaña "' . $opportunity->getName() . '"',
                $idAdvertiserReferrer,
                self::ENTITY_ADVERTISER_REFERRER,
                '$' . Currency::format($advertiserReferrerShare) . ' ha sido depositado en tu mondero por haber referenciado a alguien quien participó en la campaña "' . $opportunity->getName() . '"'
            );
        }

        //If the 2.5% is not taken by the $advertiserReferrerShare, add it to the Brandme's Exchange
        if (($remainingShare * 0.1) - $advertiserReferrerShare > 0) {
            $brandmeExchangeShare += (($remainingShare * 0.1) - $advertiserReferrerShare);
        }

        //Pay the advertiser exchange 40% of the markup value, this may or may not go to a white label partner, or it could just go to brandme
        $advertiserExchangeShare = $remainingShare * 0.4;

        $idAdvertiserExchange = $advertiser->getIdExchange();

        /** @var FactorBalance $advertiserExchangeBalance */
        $advertiserExchangeBalance = ExchangeBalance::findFirst('id_exchange = ' . $idAdvertiserExchange);

        //Increase the advertisers's exchange balance
        $advertiserExchangeBalance
            ->setBalance($advertiserExchangeBalance->getBalance() + $advertiserExchangeShare)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        $this->setParticipationTransaction(
            $participation->getId(),
            $advertiserExchangeShare,
            'deposit',
            'complete',
            'Deposito por participacion de un anunciante en la oportunidad "' . $opportunity->getName() . '"',
            $idAdvertiserExchange,
            //Brandme is the first exchange
            self::ENTITY_WHITE_LABEL_PARTNER
        );

        //Pay the influencer exchange 27.5% of the markup value, this may or may not go to a white label partner, or it could just go to brandme
        $influencerExchangeShare = $remainingShare * 0.275;

        $idInfluencerExchange = Factor::findFirst('id = ' . $idCreator)->getIdExchange();

        /** @var FactorBalance $influencerExchangeBalance */
        $influencerExchangeBalance = ExchangeBalance::findFirst('id_exchange = ' . $idInfluencerExchange);

        //Increase the influencer's exchange balance
        $influencerExchangeBalance
            ->setBalance($influencerExchangeBalance->getBalance() + $influencerExchangeShare)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        $this->setParticipationTransaction(
            $participation->getId(),
            $influencerExchangeShare,
            'deposit',
            'complete',
            'Deposito por participacion de un influenciador en la oportunidad "' . $opportunity->getName() . '"',
            $idInfluencerExchange,
            //Brandme is the first exchange
            self::ENTITY_WHITE_LABEL_PARTNER
        );

        //Pay the Brandme Exchange

        /** @var FactorBalance $brandmeExchangeBalance */
        $idBrandmeExchange = 1; // always 1
        $brandmeExchangeBalance = ExchangeBalance::findFirst('id_exchange = ' . $idBrandmeExchange);

        //Increase the influencer's referrer's balance
        $brandmeExchangeBalance
            ->setBalance($brandmeExchangeBalance->getBalance() + $brandmeExchangeShare)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

        $this->setParticipationTransaction(
            $participation->getId(),
            $brandmeExchangeShare,
            'deposit',
            'complete',
            'Deposito por participación en la campaña "' . $opportunity->getName() . '"',
            $idBrandmeExchange,
            self::ENTITY_BRANDME
        );

        //Finally, set the is_paid flag to 1 so we don't pay them again in the future
        $participation
            ->setIsPaid(1)
            ->setUpdatedAt(date('Y-m-d H:i:s'))
            ->update();

    }

    /**
     * This method pays the different parties involved in an opportunity participation.
     * It is quite verbose but necessary to set up the table relationships for maximum reporting flexibility
     *
     * @param $amount
     * @param $type
     * @param $status
     * @param $description
     * @param $idFactor
     * @param $entityType
     * @param $activityMessage
     */
    protected function setParticipationTransaction($idParticipation, $amount, $type, $status, $description, $idEntity, $entityType, $activityMessage = null)
    {
        //create the transaction itself
        $transaction = new Transaction();
        $transaction
            ->setType($type)
            ->setDescription($description)
            ->setStatus($status)
            ->setAmount($amount)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->create();

        //Create the entity transaction
        switch ($entityType) {
            case self::ENTITY_BRANDME;
            case self::ENTITY_WHITE_LABEL_PARTNER;
                $entityTransaction = new ExchangeTransaction();
                $msg = $entityTransaction
                    ->setIdTransaction($transaction->getId())
                    ->setIdExchange($idEntity)
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->create();
                break;
            case self::ENTITY_ADVERTISER;
            case self::ENTITY_ADVERTISER_REFERRER;
            case self::ENTITY_INFLUENCER;
            case self::ENTITY_INFLUENCER_REFERRER;
                $entityTransaction = new FactorTransaction();
                $entityTransaction
                    ->setIdTransaction($transaction->getId())
                    ->setIdFactor($idEntity)
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->create();
                break;
        }

        //Create the participation transaction link
        $participationTransaction = new CampaignOpportunityParticipationTransaction();
        $participationTransaction
            ->setEntityType($entityType)
            ->setIdEntityTransaction($entityTransaction->getId())
            ->setIdCampaignOpportunityParticipation($idParticipation)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->create();

        //Record activity
        if (!is_null($activityMessage) && in_array($entityType, [self::ENTITY_ADVERTISER, self::ENTITY_ADVERTISER_REFERRER, self::ENTITY_INFLUENCER, self::ENTITY_INFLUENCER_REFERRER])) {
            $activity = new FactorActivity();
            $activity
                ->setIdFactor($idEntity)
                ->setMessage($activityMessage)
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->create();
        }

    }
}