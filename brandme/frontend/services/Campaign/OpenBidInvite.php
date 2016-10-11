<?php

namespace Frontend\Services\Campaign;

use Common\Services\Campaign\Statuses;
use Common\Services\Sql;
use Entities\CampaignOpportunityParticipation;
use Entities\FactorNetworkParticipation;
use Entities\Segmentation;

class OpenBidInvite
{
    protected $opportunity = [];
    protected $creatorNetwork = [];

    /**
     * Invite factors who in some way match a valid opportunities segmentation criteria
     */
    public function invite($forceInvite = false)
    {
        //First get all valid open opportunities which are ACTIVE and have CREDIT still allocated
        //Note: I removed location postcode and location state which require rethinking
        //Date start indicates when inviting should begin, hence it be less than or equal to now
        //If there is a bid, that means the negotiation part has been approved and we must count it as money used
        $opportunitySql
            = "
          SELECT
                c.id_factor,
                f.email,
                c.name campaign_name,
                c.budget,
                co.id campaign_opportunity_id,
                co.name,
                n.name network_name,
                co.id_network,
                co.maximum_bid,
                GROUP_CONCAT(DISTINCT(s.id),':',s.segment) segmentation,
                c.budget - IFNULL((SELECT SUM(cop.bid) FROM campaign_opportunity_participation cop WHERE cop.id_campaign_opportunity = co.id AND creator_status NOT IN (
                ".Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_CANCELED.",
                ".Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_REJECTED.",
                ".Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_EXPIRED
            . /* be careful, this could cause a possible leak if afterwards it is activated */
            ",
                ".Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_DIRECT_OFFER.",
                ".Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID.")) ,0) as credit_left
            FROM campaign c
                INNER JOIN campaign_opportunity co ON co.id_campaign = c.id
                INNER JOIN factor f ON f.id = c.id_factor
                INNER JOIN network n ON n.id = co.id_network
                INNER JOIN campaign_opportunity_segmentation cos ON cos.id_campaign_opportunity = co.id
                INNER JOIN segmentation s ON s.id = cos.id_segmentation
            WHERE co.date_start <= NOW()
                AND n.status = 'active'
                AND n.can_participate = 1
                    AND n.id > 1
                AND co.type = 'open'
                AND co.status = ".Statuses::OPPORTUNITY_EXECUTING."
            GROUP BY co.id
            HAVING credit_left >= co.maximum_bid";

        $opportunities = Sql::find($opportunitySql);
        if (empty($opportunities)) {
            echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().'): There are no opportunities ready for invites in this moment'
                .PHP_EOL;

            return;
        }
        //Now look for matching creator profiles to invite to participate in opportunities
        foreach ($opportunities as $o) {
            $this->opportunity = [];
            echo str_repeat('.', 50).PHP_EOL;
            echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().') ID: '.$o['campaign_opportunity_id'].', Campaign: '
                .$o['campaign_name'].', Opportunity: '
                .$o['name'].', Network: '.$o['network_name'].', Budget: $'.number_format($o['budget'], 2).', Anunciante: '.$o['email']
                .PHP_EOL;
            $segmentation = explode(',', $o['segmentation']);
            array_walk($segmentation, array($this, 'sortSegmentation'), 'opportunity');
            //now compare factors against opportunities segmentation
            $inviteSql
                = "
                SELECT
                    f.id id_factor,
                    fnm.statistics_following,
                    fnm.statistics_followers,
                    CONCAT(fn.account_alias,' (',n.name,')') alias,
                    fr.state,
                    fr.country,
                    GROUP_CONCAT(DISTINCT(fn.id)) id_factor_network,
                    GROUP_CONCAT(DISTINCT(s.id), ':',s.segment) as segmentation
                FROM factor f
                    INNER JOIN factor_region fr ON fr.id_factor = f.id
                    INNER JOIN factor_network fn ON fn.id_factor = f.id
                    INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                    LEFT JOIN factor_network_segmentation fns ON fns.id_factor_network = fn.id
                    LEFT JOIN segmentation s ON s.id = fns.id_segmentation
                    INNER JOIN network n ON n.id = fn.id_network
                    INNER JOIN factor_auth fa ON fa.id_factor = f.id
                    INNER JOIN role rl ON rl.id = fa.id_role
                WHERE rl.name = 'creator'
                    AND fn.id_network = {$o['id_network']}
                    AND (SELECT COUNT(*) FROM campaign_opportunity_participation cop WHERE cop.id_campaign_opportunity = {$o['campaign_opportunity_id']} AND cop.id_factor_network = fn.id) = 0
                    AND f.id != {$o['id_factor']}
                GROUP BY fn.id";
            //We decided to omit filtering out creators by the negotiating_price, since a creator may wish to participate for less money than he specified
            //AND (fnm.negotiating_price IS NULL OR fnm.negotiating_price <= {$o['maximum_bid']})
            $potentialInvites = Sql::find($inviteSql);
            if (empty($potentialInvites)) {
                echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().'): No potential creators found'.PHP_EOL;
                continue;
            }
            //The criteria for a match is > 0 segment options from a segment group.
            $total = 0;
            $invited = 0;
            foreach ($potentialInvites as $creatorNetwork) {
                $total++;
                if (!$forceInvite) {
                    $matched = [];
                    $failedToMatch = [];
                    $this->creatorNetwork = [];
                    $creatorSegmentation = array_filter(explode(',', $creatorNetwork['segmentation']), 'strlen');
                    array_walk($creatorSegmentation, array($this, 'sortSegmentation'), 'creatorNetwork');
                    foreach ($this->opportunity as $segment => $idOptions) {
                        switch ($segment) {
                            case 'location_country':
                                $country = Segmentation::findFirst('id = '.$idOptions[0])->getName();
                                if ($country != $creatorNetwork['country']) {
                                    //immediate fail
                                    continue;
                                }
                                break;
                            case 'statistics_following':
                            case 'statistics_followers':
                                // we need to convert the id to value to compare it against network stats
                                $statistic = Segmentation::findFirst($idOptions[0])->getName();
                                if ($statistic >= $creatorNetwork[$segment]) {
                                    /*echo '@'.$creatorNetwork['alias'].' doesn\'t have enough "'.$segment.'\'s": '.$statistic.'  >  '
                                        .$creatorNetwork[$segment].PHP_EOL;*/
                                    $failedToMatch[] = $segment;
                                } else {
                                    $matched[] = $segment;
                                }
                                break;
                            default:
                                if (isset($this->creatorNetwork[$segment])) {
                                    if (!count(array_intersect($idOptions, $this->creatorNetwork[$segment]))) {
                                        /*echo '@'.$creatorNetwork['alias'].' doesn\'t intersect on any of the opportunity\'s "'.$segment
                                            .'"" options.'.PHP_EOL;*/
                                        $failedToMatch[] = $segment;
                                    } else {
                                        //echo 'matched '.$segment.PHP_EOL;
                                        $matched[] = $segment;
                                    }
                                } else {
                                    $failedToMatch[] = $segment;
                                }
                                break;
                        }
                    }
                    //echo '@'.$creatorNetwork['alias'].' matched '.count($matched).' out of '.(count($failedToMatch) + count($matched)) .' comparable criteria'.PHP_EOL;
                    if (count($matched) < 1) {
                        /*echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().'): id_factor_network '
                            .$creatorNetwork['id_factor_network']
                            .' didn\'t meet enough criteria: '.implode(', ', $failedToMatch).PHP_EOL;*/
                        continue;
                    }
                }
                //initialize participation in zero's
                $participation = new CampaignOpportunityParticipation();
                $participation
                    ->setIdFactorNetwork($creatorNetwork['id_factor_network'])
                    ->setIdCampaignOpportunity($o['campaign_opportunity_id'])
                    ->setCreatorStatus(Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_NEW_OPEN_BID)
                    ->setAdvertiserStatus(Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_UNINITIALIZED)
                    ->setBid(0.00)
                    ->setBidWithMarkup(0.00)
                    ->setBidWithMarkupReserved(0.00)
                    ->setIsPaid(0)
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->save();
                /*echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().'): Invited id_factor_network '
                    .$creatorNetwork['id_factor_network'].' to participate in opportunity '.$o['campaign_opportunity_id'].PHP_EOL;*/
                $invited++;
            }
            echo date('Y-m-d H:i:s').' ('.date_default_timezone_get().'): Invited '.$invited.' of '.$total.' potential creators'.PHP_EOL;
        }
    }

    protected function sortSegmentation($segment, $k, $reference)
    {
        $segment = explode(':', $segment);
        if (!isset($this->{$reference}[$segment[1]])) {
            $this->{$reference}[$segment[1]] = [];
        }
        $this->{$reference}[$segment[1]][] = $segment[0];
    }

}