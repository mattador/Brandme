<?php

namespace Frontend\Services\Campaign;


use Common\Services\Campaign\Statuses;
use Common\Services\Sql;
use Entities\CampaignOpportunity;
use Entities\CampaignOpportunityParticipation;
use Entities\CampaignOpportunitySegmentation;
use Entities\FactorMeta;
use Entities\Network;
use Entities\Opportunity as OpportunityEntity;
use Entities\OpportunitySegmentation;
use Entities\OpportunityVertical;
use Entities\Social;
use Entities\Vertical;
use Entities\VerticalSlice;
use Frontend\Module;
use Frontend\Services\AbstractService;
use Frontend\Services\Time;
use Frontend\Widgets\Translate;
use Phalcon\Exception;

/**
 * Core opportunity life cycle logic
 * Class Opportunity
 *
 * @package Frontend\Services\Campaign
 */
class Opportunity extends AbstractService
{


    /**
     * Returns left over credit in campaign after subtracting dispatched (and un-dispatched but already confirmed)
     * participations and reserved participation bids funds from the campaign's budget.
     *
     * @param $idCampaign
     * @return float
     */
    public function getCreditLeftInCampaign($idCampaign)
    {
        $sql
            = 'SELECT
                  c.budget campaign_budget,
                  IFNULL(
                      (SELECT SUM(cop.bid_with_markup)
                      FROM campaign_opportunity_participation cop
                      INNER JOIN campaign_opportunity co ON cop.id_campaign_opportunity = co.id
                      WHERE co.id_campaign = c.id
                      AND cop.creator_status NOT IN('.implode(',', Statuses::$excluded).')
                  ), 0) AS funds_used,
                  IFNULL(
                      (SELECT SUM(cop.bid_with_markup_reserved)
                      FROM campaign_opportunity_participation cop
                      INNER JOIN campaign_opportunity co ON cop.id_campaign_opportunity = co.id
                      WHERE co.id_campaign = c.id
                      AND cop.creator_status NOT IN('.implode(',', Statuses::$excluded).')
                      ), 0) AS funds_reserved
                FROM campaign c WHERE c.id ='.$idCampaign;
        $result = Sql::find($sql)[0];

        return $result['campaign_budget'] - $result['funds_used'] - $result['funds_reserved'];
    }

    /**
     * Creates a opportunity
     *
     * @param $idCampaign
     * @param $data
     * @return int
     * @throws Exception
     */
    public function create($idCampaign, $data, $idAdvertiser)
    {
        //Prepare timezone difference. We always store times in UTC format.
        $timezone = FactorMeta::findFirst('id_factor = '.$idAdvertiser)->getTimezone();
        $startDateUTC = Module::getService('Time')->timezoneTimeToUtc(
            $data['instructions']['start_opportunity'],
            is_null($timezone) ? Time::DEFAULT_REGIONAL_TIMEZONE : $timezone
        );
        $opportunity = new CampaignOpportunity();
        $opportunity
            ->setIdCampaign($idCampaign)
            ->setName($data['instructions']['opportunity_name'])
            ->setLogo(isset($data['instructions']['image_logo']) ? $data['instructions']['image_logo'] : '')
            ->setDateStart(
                $startDateUTC
            )//This is the date the advertiser would like to being inviting people, it has NOTHING to do with when the status updates actually start getting published
            ->setMaximumBid($data['instructions']['type'] == 'open' ? (float)$data['instructions']['max_offer'] : null)
            ->setIdNetwork(Network::findFirst('name = "'.ucfirst(strtolower($data['network']['network'])).'"')->getId())
            ->setRequirements(trim($data['instructions']['requirements']))
            ->setIdealCreator(trim($data['instructions']['ideal_candidate']))
            ->setMention(strlen(trim($data['instructions']['mention'])) > 0 ? $data['instructions']['mention'] : null)
            ->setMentionRequired(isset($data['instructions']['mention_required']) ? 1 : 0)
            ->setHashtag(strlen(trim($data['instructions']['hashtag'])) > 0 ? $data['instructions']['hashtag'] : null)
            ->setHashtagRequired(isset($data['instructions']['hashtag_required']) ? 1 : 0)
            ->setLink(strlen(trim($data['instructions']['link'])) > 0 ? trim($data['instructions']['link']) : null)
            ->setLinkRequired(isset($data['instructions']['link_required']) ? 1 : 0)
            ->setDescription($data['instructions']['about_opportunity'])
            ->setStatus(Statuses::OPPORTUNITY_IN_REVIEW)
            ->setType($data['instructions']['type'])
            //->setPostcode(strlen(trim($data['segmentation']['postcode'])) > 0 ? trim($data['segmentation']['postcode']) : null)
            ->setContentType($data['type']['type'])
            ->setCreatedAt(date('Y-m-d H:i:s'));
        if (!$opportunity->create()) {
            throw new Exception(
                'Error occurred creating opportunity for campaign #'.$idCampaign.' : '.
                // PHP_EOL . json_encode($data) .
                PHP_EOL.var_export($opportunity->getMessages(), true)
            );
        }
        $idOpportunity = $opportunity->getId();
        //only open bid campaigns require segmentation, with direct bids the advertiser uses the search engine to look for creators
        if ($data['instructions']['type'] == 'open') {
            $this->setSegmentation($idOpportunity, array_filter($data['segmentation']));
        }

        return $idOpportunity;
    }

    /**
     * Creates segmentation which will then be used for dispatching invites in the case of direct campaigns
     *
     * @param $idOpportunity
     * @param $segmentation
     */
    public function setSegmentation($idOpportunity, $segmentation)
    {
        $segments = [
            'location_state',
            'location_country',
            'statistics_followers',
            'statistics_following',
            'age',
            'education',
            'gender',
            'income',
            'interests',
            'language',
            'family',
            'civil_state'
        ];
        foreach (array_intersect_key($segmentation, array_flip($segments)) as $segment => $options) {
            foreach ((array)$options as $idOption) {
                if (!is_numeric($idOption)) {
                    continue;
                }
                $seg = new CampaignOpportunitySegmentation();
                $seg->setIdCampaignOpportunity($idOpportunity)
                    ->setIdSegmentation($idOption)
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->create();
            }
        }
    }

    public function checkForNamingConflict($name, $idFactor, $idCampaign)
    {
        $query
            = 'SELECT COUNT(o.name) cnt
                  FROM Entities\CampaignOpportunity o
                  JOIN Entities\Campaign c ON c.id = o.id_campaign
                  WHERE
                  o.name = "'.$name.'" AND c.id_factor = '.$idFactor.' AND c.id = '.$idCampaign;
        $results = $this->getManager()->executeQuery($query);

        return intval($results->getFirst()->cnt) ? true : false;
    }

    /**
     * This is the query for the advertisers campaign dashboard
     * Don't get confused - approved refers to how many OPPORTUNITIES have been approved, and NOT how many PARTICIPATIONS have been approved
     * Spent however DOES SUM the successful bids at a participation level
     *
     * @param $idAdvertiser
     * @return mixed
     */
    public function getAdvertiserCampaigns($idAdvertiser)
    {
        //@todo This query could probably be improved without PHP manipulation of the results at the end
        $sql
            = 'SELECT
                    c.id,
                    c.name campaign_name,
                    c.budget,
                    c.is_archived,
                    c.created_at created_at,
                    (SELECT IFNULL(SUM(bid_with_markup),0) FROM campaign_opportunity_participation
                        WHERE id_campaign_opportunity = co.id AND creator_status
                        NOT IN('.implode(',', Statuses::$excluded).')) spent,
                    (SELECT IFNULL(SUM(bid_with_markup_reserved),0) FROM campaign_opportunity_participation
                        WHERE id_campaign_opportunity = co.id AND creator_status
                        NOT IN('.implode(',', Statuses::$excluded).')) reserved,
                    (SELECT IFNULL(COUNT(*),0) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id) invited,
                    (SELECT IFNULL(COUNT(*),0) FROM campaign_opportunity co0 WHERE co0.id_campaign = c.id) count_opportunities,
                    (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND creator_status IN('.
            Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING_PUBLISHING.','.Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING.','.
            Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED.','.Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING_ERROR.')) participations_approved,
                    (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND creator_status IN('
            .Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING.')) participations_pending_review
                FROM campaign c
                LEFT JOIN campaign_opportunity co ON co.id_campaign = c.id
                WHERE c.id_factor = '.$idAdvertiser.'
                ORDER BY c.created_at DESC';

        $campaigns = Sql::find($sql);
        $campaignStats = [];
        foreach ($campaigns as $c) {
            if (!isset($campaignStats[$c['id']])) {
                $campaignStats[$c['id']] = $c;
                $time = Module::getService('Time')->utcToTimezone(
                    $c['created_at'],
                    FactorMeta::findFirst('id_factor = '.$idAdvertiser)->getTimezone(),
                    'd/m/Y'
                );
                $campaignStats[$c['id']]['created_at'] = $time;
            } else {
                $campaignStats[$c['id']]['invited'] += $c['invited'];
                $campaignStats[$c['id']]['spent'] += $c['spent'];
                $campaignStats[$c['id']]['reserved'] += $c['reserved'];
                $campaignStats[$c['id']]['participations_approved'] += $c['participations_approved'];
                $campaignStats[$c['id']]['participations_pending_review'] += $c['participations_pending_review'];
            }
        }

        return array_values($campaignStats);
    }

    public function getAdvertiserCampaignOpportunities($idCampaign)
    {
        $sql
            = 'SELECT
                  co.id id_opportunity,
                  c.id_factor,
                  c.id id_campaign,
                  co.status,
                  co.rejection_status_message,
                  co.content_type,
                  n.name network,
                  co.created_at,
                  co.name opportunity_name,
                  co.logo,
                  co.date_start,
                  (SELECT IFNULL(SUM(bid_with_markup),0) FROM campaign_opportunity_participation
                        WHERE id_campaign_opportunity = co.id AND creator_status
                        NOT IN('.implode(',', Statuses::$excluded).')) cost,
                (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND advertiser_status IN ('
            .Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING.')) pending,
                (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND advertiser_status IN ('
            .Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_CLOSED.', '.Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_REJECTED.')) closed,
                (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND advertiser_status IN ('
            .Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_EXPIRED.')) expired,
                (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id AND creator_status IN ('
            .Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED.')) published,
                (SELECT COUNT(id) FROM campaign_opportunity_participation WHERE id_campaign_opportunity = co.id) invited
            FROM campaign c
            INNER JOIN campaign_opportunity co ON co.id_campaign = c.id
            INNER JOIN network n ON n.id = co.id_network
            WHERE c.id = '.$idCampaign;
        $result = Sql::find($sql);
        $opportunities = [
            'in_review'         => [],
            'pending_execution' => [],
            'executing'         => [],
            'finished'          => [],
            'paused'            => [],
            'rejected'          => []
        ];
        foreach ($result as $opportunity) {
            $opportunity['links'] = [];
            $opportunity['msg'] = '';
            $time = Module::getService('Time')->utcToTimezone(
                $opportunity['created_at'],
                FactorMeta::findFirst('id_factor = '.$opportunity['id_factor'])->getTimezone(),
                'd/m/Y'
            );
            $opportunity['created_at'] = $time;
            switch ($opportunity['status']) {
                case Statuses::OPPORTUNITY_FINISHED:
                    $opportunities['finished'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_PENDING_EXECUTION:
                    $opportunity['links'][] = [
                        'href'  => 'anunciante/campana/'.$opportunity['id_campaign'].'/oportunidad/'.$opportunity['id_opportunity']
                            .'/detener',
                        'label' => 'Pause'
                    ];
                    $opportunities['pending_execution'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_EXECUTING:
                    $opportunity['links'][] = [
                        'href'  => 'anunciante/campana/'.$opportunity['id_campaign'].'/oportunidad/'.$opportunity['id_opportunity']
                            .'/detener',
                        'label' => 'Stop'
                    ];
                    $opportunities['executing'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_IN_REVIEW:
                    $opportunities['in_review'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_PAUSED:
                    $opportunity['links'][] = [
                        'href'  => 'anunciante/campana/'.$opportunity['id_campaign'].'/oportunidad/'.$opportunity['id_opportunity']
                            .'/resumir',
                        'label' => 'Resume'
                    ];
                    $opportunities['paused'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_PAUSED_NO_MONEY:
                    $opportunity['links'][] = [
                        'href'  => 'anunciante/campana/'.$opportunity['id_campaign'].'/oportunidad/'.$opportunity['id_opportunity']
                            .'/resumir',
                        'label' => 'Resume'
                    ];
                    $opportunities['paused'][] = $opportunity;
                    break;
                case Statuses::OPPORTUNITY_REVIEW_REJECTED:
                    $opportunity['msg'] = '<i style="color:red;">'.$opportunity['rejection_status_message'].'</i>';
                    $opportunities['rejected'][] = $opportunity;
                    break;
            }
        }

        return $opportunities;
    }

    /**
     * Returns the participations with bid+markup for the advertiser
     *
     * @param $idOpportunity
     * @return array
     */
    public function getAdvertiserCampaignOpportunityParticipations($idOpportunity)
    {
        //get all opportunity participations without discrimination
        $sql
            = 'SELECT
                    co.id id_opportunity,
                    c.id id_campaign,
                    c.id_factor id_advertiser,
                    co.name opportunity_name,
                    cop.id id_participation,
                    cop.creator_status,
                    cop.advertiser_status,
                    cop.pitch,
                    cop.bid,
                    cop.bid_with_markup,
                    cop.content,
                    cop.dispatch_at,
                    cop.expired_at,
                    fn.account_alias network_alias,
                    CONCAT(fm.first_name," ",fm.last_name) factor_name,
                    fnm.statistics_following,
                    fnm.statistics_followers,
                    fnm.json,
                    CONCAT(fm.referral_code,"/",fn.account_id) link,
                    cop.created_at,
                    cop.updated_at,
                    cop.expired_at,
                    fr.country creator_country
                FROM campaign c
                INNER JOIN campaign_opportunity co ON co.id_campaign = c.id
                INNER JOIN campaign_opportunity_participation cop ON cop.id_campaign_opportunity = co.id
                INNER JOIN factor_network fn ON fn.id = cop.id_factor_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor f ON f.id = fn.id_factor
                INNER JOIN factor_meta fm ON fm.id_factor = f.id
                INNER JOIN factor_region fr ON fr.id_factor = f.id
                WHERE co.id = '.$idOpportunity;
        $result = Sql::find($sql);

        //Organize and group participations logically
        $participations = ['open' => [], 'pending' => [], 'closed' => []];
        foreach ($result as $participation) {
            $json = json_decode($participation['json']);
            $participation['network_avatar'] = $json->photoURL;
            //Normal links which appear in the actions drop-down
            $participation['links'] = [];
            //"Forms" appear as links in the actions drop-down of the participation view, but when clicked render forms in a modal using javascript with bootbox
            $participation['forms'] = [];//@see public/js/brandme/opportunity-negotiate.js
            //Messages are interchangable with status messages, and are shown to the advertiser in the participation
            $participation['msg'] = '';
            //strip slashes from json if required
            $negotiation = new Negotiation($participation['id_participation']);

            //Check if the opportunity is time bound and "expire" it if necessary
            /** @var \Frontend\Services\Campaign\Opportunity\Actions $actionService */
            $actionService = Module::getService('Campaign\Opportunity\Actions');
            switch ($participation['advertiser_status']) {
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED:
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN:
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING:
                    $participation = $actionService->checkParticipationExpiryDateAndExpireIfNecessary($participation);
                    break;
            }

            //Unintialized participations are invisible to the advertiser
            switch ($participation['advertiser_status']) {
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_OPEN:
                    if ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_BID
                        && $negotiation->actionRequiredBy(
                            Negotiation::NEGOTIATION_ENTITY_ADVERTISER
                        )
                    ) {
                        //at this stage the bid isn't formalized so we copy the negotiation bid over for visualization purposes only
                        $participation['bid'] = number_format(
                            $negotiation->getLatestNegotiationElement(Negotiation::NEGOTIATION_STAGE_BID)['bid_with_markup'],
                            2
                        );
                        $participation['msg'] = Translate::_('The creator has sent you a bid');
                        $participation['links'][] = [
                            'href'  => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                                .'/participacion/'.$participation['id_participation'].'/aprobar/bid',
                            'label' => 'Aprobar Bid'
                        ];
                        $participation['forms'][] = [
                            'action' => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                                .'/participacion/'.$participation['id_participation'].'/negociar/bid',
                            'label'  => 'Negociar Bid',
                            'class'  => 'negotiate-bid'
                        ];
                    } elseif ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_CONTENT
                        && $negotiation->actionRequiredBy(Negotiation::NEGOTIATION_ENTITY_ADVERTISER)
                    ) {
                        $participation['bid'] = $participation['bid_with_markup'];
                        $participation['content'] = $negotiation->getLatestNegotiationElement(
                            Negotiation::NEGOTIATION_STAGE_CONTENT,
                            Negotiation::NEGOTIATION_ENTITY_CREATOR
                        )['content'];
                        $participation['msg'] = Translate::_('The creator has sent you content to review');
                        $participation['links'][] = [
                            'href'  => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                                .'/participacion/'.$participation['id_participation'].'/aprobar/contenido',
                            'label' => 'Publicar Ahora'
                        ];
                        $participation['forms'][] = [
                            'action' => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                                .'/participacion/'.$participation['id_participation'].'/aprobar/contenido',
                            'label'  => 'Publicar Despues',
                            'class'  => 'approve-dispatch'
                        ];
                        $participation['forms'][] = [
                            'action' => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                                .'/participacion/'.$participation['id_participation'].'/negociar/contenido',
                            'label'  => 'Negociar Contenido',
                            'class'  => 'negotiate-content'
                        ];
                    }
                    $participation['links'][] = [
                        'href'  => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                            .'/participacion/'.$participation['id_participation'].'/declinar',
                        'label' => 'Declinar'
                    ];
                    $participations['open'][] = $participation;
                    break;
                case
                Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING:
                    if ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_BID) {
                        if (count($negotiation->getNegotiation(Negotiation::NEGOTIATION_STAGE_BID)) > 0) {
                            $participation['bid'] = $negotiation->getLatestNegotiationElement(
                                Negotiation::NEGOTIATION_STAGE_BID,
                                Negotiation::NEGOTIATION_ENTITY_ADVERTISER
                            )['bid_with_markup'];
                            $participation['msg'] = Translate::_('Pending Counter Bid');
                        } else {
                            $participation['bid'] = 0.00;
                            $participation['msg'] = Translate::_('Pending Bid');
                        }
                    } else {
                        $participation['bid'] = $participation['bid_with_markup'];
                        if (count($negotiation->getNegotiation(Negotiation::NEGOTIATION_STAGE_CONTENT)) > 0) {
                            $participation['msg'] = Translate::_('Pending Revised Content');
                        } else {
                            $participation['msg'] = Translate::_('Pending Content');
                        }
                    }
                    $participations['pending'][] = $participation;
                    break;
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING_DISPATCH:
                    //convert UTC back to advertiser timezone
                    $participation['bid'] = $participation['bid_with_markup'];
                    $dispatchAt = Module::getService('Time')->utcToTimezone(
                        $participation['dispatch_at'],
                        FactorMeta::findFirst('id_factor = '.$participation['id_advertiser'])->getTimezone(),
                        'd/m/Y h:i a'
                    );
                    $participation['msg'] = Translate::_('Pending dispatch at').' '.$dispatchAt;
                    $participations['pending'][] = $participation;
                    break;
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_CLOSED:
                    //rejected by anunciante - so he can see it and reopen it if he wishes
                    if (!is_null($participation['bid_with_markup'])) {
                        //most likely rejected during content stage
                        $participation['bid'] = $participation['bid_with_markup'];
                    } else {
                        $participation['bid'] = $negotiation->getLatestNegotiationElement(
                            Negotiation::NEGOTIATION_STAGE_BID
                        )['bid_with_markup'];
                    }
                    $participation['msg'] = 'Rechazado por ti';
                    $participation['updated_at'] = Module::getService('Time')->utcToTimezone(
                        $participation['updated_at'],
                        FactorMeta::findFirst('id_factor = '.$participation['id_advertiser'])->getTimezone(),
                        'd/m/Y h:i a'
                    );
                    $participation['links'][] = [
                        'href'  => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                            .'/participacion/'.$participation['id_participation'].'/reactivar',
                        'label' => 'Reabrir'
                    ];
                    $participations['closed'][] = $participation;
                    break;
                case Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_EXPIRED:
                    $participation['expired_at'] = Module::getService('Time')->utcToTimezone(
                        $participation['expired_at'],
                        FactorMeta::findFirst('id_factor = '.$participation['id_advertiser'])->getTimezone(),
                        'd/m/Y h:i a'
                    );
                    $bid = $negotiation->getLatestNegotiationElement(
                        Negotiation::NEGOTIATION_STAGE_BID,
                        Negotiation::NEGOTIATION_ENTITY_CREATOR
                    );
                    $participation['bid'] = !is_null($participation['bid_with_markup']) ? $participation['bid_with_markup']
                        : (isset($bid['bid_with_markup']) ? $bid['bid_with_markup'] : 0.00);
                    $participation['msg'] = 'Expirado';
                    $participation['links'][] = [
                        'href'  => 'anunciante/campana/'.$participation['id_campaign'].'/oportunidad/'.$participation['id_opportunity']
                            .'/participacion/'.$participation['id_participation'].'/reactivar',
                        'label' => 'Reactivar'
                    ];
                    $participations['closed'][] = $participation;
                    break;
            }
        }

        return $participations;
    }

    /**
     * Return a single advertiser opportunity
     *
     * @param $idOpportunity
     * @return bool
     */
    public function getAdvertiserOpportunity($idOpportunity)
    {
        $sql
            = '
                SELECT
                  co.id,
                  co.name opportunity,
                  c.name campaign,
                  n.name network,
                  co.logo,
                  co.content_type,
                  co.type,
                  co.maximum_bid,
                  co.description,
                  co.requirements,
                  co.ideal_creator,
                  co.link,
                  co.link_required,
                  co.hashtag,
                  co.hashtag_required,
                  co.mention,
                  co.mention_required,
                  co.date_start,
                  GROUP_CONCAT(s.segment,":",s.name) segmentation
                FROM campaign_opportunity co
                INNER JOIN campaign c ON c.id = co.id_campaign
                INNER JOIN network n ON n.id = co.id_network
                INNER JOIN campaign_opportunity_segmentation cos ON cos.id_campaign_opportunity = co.id
                INNER JOIN segmentation s ON s.id = cos.id_segmentation
                WHERE co.id = '.$idOpportunity;
        $opportunity = Sql::find($sql)[0];
        if (is_null($opportunity['id'])) {
            return false;
        }
        $opportunity['hashtag'] = preg_replace('/\#/', '', $opportunity['hashtag']);
        $opportunity['mention'] = preg_replace('/\@/', '', $opportunity['mention']);

        return $opportunity;
    }

    /**
     * Returns a list of an advertisers direct opportunities which are active
     *
     * @param $idAdvertiser
     * @param $idFactorNetwork
     * @return array
     */
    public function getAdvertiserDirectOpportunities($idAdvertiser, $idFactorNetwork = null)
    {
        $sql
            = '
            SELECT
                co.id_campaign campaign,
                co.id opportunity,
                co.name name,
                co.logo logo,
                LOWER(n.name) network
            FROM campaign_opportunity co
                INNER JOIN campaign c ON c.id = co.id_campaign
                INNER JOIN network n ON n.id = co.id_network
            WHERE
                co.type = "direct" AND
                co.status = '.Statuses::OPPORTUNITY_EXECUTING.' AND
                c.id_factor = '.$idAdvertiser;
        $directOpportunities = Sql::find($sql);
        //see if creator has already chosen to participate in any of the opportunities
        if (!empty($directOpportunities)) {
            foreach ($directOpportunities as &$opportunity) {
                $canParticipate = false;
                if (!is_null($idFactorNetwork)) {
                    $canParticipate = !intval(
                        CampaignOpportunityParticipation::count(
                            'id_campaign_opportunity = '.$opportunity['opportunity'].' AND id_factor_network = '.$idFactorNetwork
                        )
                    );
                }
                if ($canParticipate) {
                    $canParticipate = $this->getCreditLeftInCampaign($opportunity['campaign']) >= Pricing::STANDARD_MARKUP;
                }
                $opportunity['can_participate'] = $canParticipate;
            }
        }

        return $directOpportunities;
    }

    /**
     * Retrieve creator opportunities
     *
     * @todo implement pagination
     * @param $idCreator
     * @return array
     */
    public function getCreatorOpportunities($idCreator)
    {
        //load opportunities
        $sql
            = 'SELECT
                    co.id,
                    cop.id id_participation,
                    n.name network,
                    fn.status factor_network_status,
                    fn.id_factor id_creator,
                    fn.id id_factor_network,
                    fn.account_alias,
                    co.logo,
                    co.name opportunity_name,
                    co.type opportunity_type,
                    co.ideal_creator,
                    co.description,
                    cop.dispatch_at,
                    cop.creator_status,
                    cop.advertiser_status,
                    cop.pitch,
                    cop.content,
                    co.link,
                    co.content_type,
                    cop.created_at,
                    cop.updated_at,
                    cop.is_paid,
                    cop.bid,
                    cop.bid_with_markup,
                    fr.country
                FROM factor_network fn
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN campaign_opportunity_participation cop ON cop.id_factor_network = fn.id
                INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                INNER JOIN factor_region fr ON fr.id_factor = fn.id_factor
                WHERE fn.id_factor = '.$idCreator.'
                AND co.status IN ('.
            Statuses::OPPORTUNITY_EXECUTING.','.
            Statuses::OPPORTUNITY_PENDING_EXECUTION.','.
            Statuses::OPPORTUNITY_FINISHED.')'; //GROUP BY fn.id
        $results = Sql::find($sql);
        $opportunities = [
            'new_direct'        => [],
            'new_open'          => [],
            'pending'           => [],
            'canceled'          => [],
            'rejected'          => [],
            'complete'          => [],
            'expired'           => [],
            'ready_to_post'     => [],
            'ready_for_content' => [],
            'in_negotiation'    => []
            //'draft' => [],
            //'issues' => []
        ];
        /** @var \Frontend\Services\Campaign\Opportunity\Actions $actionService */
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        foreach ($results as $opportunity) {
            $opportunity['links'] = [];
            $opportunity['msg'] = '';

            $negotiation = new Negotiation($opportunity['id_participation']);
            if ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_BID) {
                $currentNegotiationBid = $negotiation->getLatestNegotiationElement(Negotiation::NEGOTIATION_STAGE_BID);
                if ($currentNegotiationBid) {
                    //at this stage the bid isn't formalized so we copy the negotiated bid over for visualization purposes only
                    $opportunity['bid'] = $currentNegotiationBid['bid'];
                    if ($currentNegotiationBid['entity'] == Negotiation::NEGOTIATION_ENTITY_ADVERTISER) {
                        $opportunity['is_bid_suggested'] = true;
                    }
                }
            }
            //Check if the opportunity is time bound and "expire" it if necessary
            switch ($opportunity['creator_status']) {
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING:
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID:
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER:
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_READY_FOR_CONTENT:
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION:
                    $opportunity = $actionService->checkParticipationExpiryDateAndExpireIfNecessary($opportunity);
                    break;
            }
            //Organize opportunities according to state
            switch ($opportunity['creator_status']) {
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER:

                    if ($opportunity['factor_network_status'] == 'linked') {
                        $opportunity['msg'] = 'El anunciante te ha invitado participar por un precio inicial de $'.number_format(
                                $opportunity['bid'],
                                2
                            ).' USD';
                        $opportunity['links'][] = [
                            'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/aceptar',
                            'label' => 'Aceptar Oferta'
                        ];
                        $opportunity['links'][] = [
                            'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/bid',
                            'label' => 'Negociar Oferta'
                        ];
                    } else {
                        $opportunity['links'][] = [
                            'href'  => 'creador/redes-sociales',
                            'label' => 'Autorizar Cuenta'
                        ];
                    }
                    $opportunity['links'][] = [
                        'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/declinar',
                        'label' => 'Declinar'
                    ];
                    $opportunities['new_direct'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID:
                    $opportunity['links'][] = [
                        'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/bid',
                        'label' => 'Ofertar'
                    ];
                    $opportunity['links'][] = [
                        'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/declinar',
                        'label' => 'Declinar'
                    ];
                    $opportunities['new_open'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING:
                    if ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_BID) {
                        $opportunity['msg'] = 'Tu oferta está pendiente de revisión';
                    } else {
                        $opportunity['msg'] = 'Tu contenido está pendiente de revisión';
                    }
                    $opportunities['pending'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_CANCELED:
                    $opportunity['msg'] = 'La oferta fue rechazada por el anunciante';
                    $opportunities['canceled'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_REJECTED:
                    $opportunity['msg'] = ' Has rechazada la oferta';
                    $opportunities['rejected'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED:
                    $opportunities['expired'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING_PUBLISHING:
                    $dispatchAt = Module::getService('Time')->utcToTimezone(
                        $opportunity['dispatch_at'],
                        FactorMeta::findFirst('id_factor = '.$opportunity['id_creator'])->getTimezone(),
                        'd/m/Y h:i a'
                    );
                    $opportunity['msg'] = Translate::_('Se envía ').' '.$dispatchAt;
                    $opportunities['ready_to_post'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_READY_FOR_CONTENT:
                    $opportunity['links'][] = [
                        'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/contenido',
                        'label' => 'Crear Contenido'
                    ];
                    $opportunities['ready_for_content'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_ACTIONS_REQUIRED_IN_NEGOTIATION:
                    if ($negotiation->getActiveNegotiationStage() == Negotiation::NEGOTIATION_STAGE_BID) {
                        $opportunity['links'][] = [
                            'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/aceptar',
                            'label' => 'Aceptar Oferta'
                        ];
                        $opportunity['msg'] = 'El anunciante te ha enviado una oferta nueva';
                        $opportunity['links'][] = [
                            'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/bid',
                            'label' => 'Negociar Oferta'
                        ];
                    } else {
                        $opportunity['msg'] = 'El anunciante ha sugerido cambios de contenido';
                        $opportunity['content_suggestion'] = $negotiation->getLatestNegotiationElement(
                            Negotiation::NEGOTIATION_STAGE_CONTENT,
                            Negotiation::NEGOTIATION_ENTITY_ADVERTISER
                        )['content_suggestion'];
                        $opportunity['links'][] = [
                            'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/contenido',
                            'label' => 'Editar Contenido'
                        ];
                    }
                    $opportunity['links'][] = [
                        'href'  => 'creador/oportunidad/'.$opportunity['id'].'/social/'.$opportunity['id_factor_network'].'/declinar',
                        'label' => 'Declinar'
                    ];
                    $opportunities['in_negotiation'][] = $opportunity;
                    break;
                case Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED:
                    if (!intval($opportunity['is_paid'])) {
                        $days = ceil((strtotime($opportunity['updated_at'].' +30 DAY') - time()) / 60 / 60 / 24);
                        if ($days > 0) {
                            $opportunity['msg'] = Translate::_(
                                'You will receive your credit in approximately %days% days',
                                ['days' => $days]
                            );
                        }
                    }
                    $opportunities['complete'][] = $opportunity;
                    break;
            }
        }

        return $opportunities;
    }


    /**
     * Get creator opportunity for bidding or content view
     *
     * @param $idCreator
     * @param $idFactorNetwork
     * @param $idOpportunity
     * @return mixed
     */
    public function getCreatorOpportunity($idCreator, $idOpportunity, $idFactorNetwork)
    {
        $sql
            = 'SELECT
                    co.id_campaign,
                    co.logo,
                    co.content_type,
                    co.name opportunity_name,
                    n.name network,
                    fn.account_alias,
                    co.link,
                    co.link_required,
                    co.hashtag,
                    co.hashtag_required,
                    co.mention,
                    co.mention_required,
                    co.description,
                    co.ideal_creator,
                    co.requirements,
                    cop.bid,
                    co.maximum_bid,
                    cop.pitch,
                    co.id id_opportunity,
                    co.type opportunity_type,
                    cop.id id_participation,
                    cop.creator_status,
                    fnm.json,
                    CONCAT(advertiser_factor_meta.first_name," ",advertiser_factor_meta.last_name) as advertiser_name
                    FROM factor_network fn
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN campaign_opportunity_participation cop ON cop.id_factor_network = fn.id
                INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                INNER JOIN campaign c ON c.id = co.id_campaign
                INNER JOIN factor_meta advertiser_factor_meta ON advertiser_factor_meta.id_factor = c.id_factor
                WHERE fn.id_factor = '.$idCreator.' AND
                cop.id_factor_network = '.$idFactorNetwork.' AND
                cop.id_campaign_Opportunity = '.$idOpportunity;
        $opportunity = Sql::find($sql);
        if (empty($opportunity)) {
            return false;
        }
        $opportunity = $opportunity[0];
        $opportunity['account_image'] = json_decode($opportunity['json'])->photoURL;
        $opportunity['maximum_bid'] = !is_null($opportunity['maximum_bid']) ? Pricing::advertiserToCreator($opportunity['maximum_bid'])
            : null;

        return $opportunity;
    }
}