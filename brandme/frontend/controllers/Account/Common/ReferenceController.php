<?php
namespace Frontend\Controllers\Account\Common;

use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Services\Campaign\Pricing;
use Common\Services\Sql;

/**
 * Class ReferenceController
 * @package Frontend\Controllers\Account\Creator
 */
class ReferenceController extends AccountControllerBase
{

    /**
     * @todo this action will get incrementally slower with time, improve the way we get amounts
     */
    public function indexAction()
    {
        $this->addAssets('js/bootbox.min.js', 'js/zeroclipboard/ZeroClipboard.min.js', 'assets/global/plugins/jquery-knob/js/jquery.knob.js', 'js/brandme/common-reference.js');
        $referenceCode = $this->get('account.meta')->getReferralCode();

        //Get people the influencer has referenced
        $referenceSql = "
        SELECT
          (SELECT DATE_FORMAT(fs.logged_in, '%d/%m/%Y') FROM factor_session fs WHERE fs.id_factor = f.id ORDER BY fs.created_at DESC LIMIT 1) logged_in_at,
          DATE_FORMAT(f.created_at, '%d/%m/%Y') created_at,
          fr.id_factor,
          fm.first_name,
          fm.last_name,
          fm.avatar,
          r.name role
        FROM factor_reference fr
        INNER JOIN factor f ON f.id = fr.id_factor
        INNER JOIN factor_auth fa ON fa.id_factor = f.id
        INNER JOIN factor_meta fm ON fm.id_factor = f.id
        INNER JOIN role r ON r.id = fa.id_role
        WHERE
        fr.id_factor_referenced_by = {$this->get('id')}
        ORDER BY f.created_at DESC";
        $referencedGroups = [
            'creator' => [],
            'advertiser' => []
        ];
        foreach (Sql::find($referenceSql) as $referenced) {
            if ($referenced['role'] == 'creator') {
                $sql = "SELECT
                  IFNULL(SUM(t.amount), '0.00') earned
                  FROM transaction t
                  INNER JOIN factor_transaction ft ON t.id = ft.id_transaction
                  INNER JOIN campaign_opportunity_participation_transaction copt ON copt.id_entity_transaction = ft.id AND copt.entity_type = 6
                  INNER JOIN campaign_opportunity_participation cop ON cop.id = copt.id_campaign_opportunity_participation
                  INNER JOIN factor_network fn ON fn.id = cop.id_factor_network
                  WHERE ft.id_factor = {$this->get('id')}
                  AND fn.id_factor = {$referenced['id_factor']}
                  AND t.type = 'deposit'";
                $referenced['earned'] = Sql::find($sql)[0]['earned'];
            } elseif ($referenced['role'] == 'advertiser') {
                $sql = "SELECT
                  IFNULL(SUM(t.amount), '0.00') earned
                  FROM transaction t
                  INNER JOIN factor_transaction ft ON t.id = ft.id_transaction
                  INNER JOIN campaign_opportunity_participation_transaction copt ON copt.id_entity_transaction = ft.id AND copt.entity_type = 4
                  INNER JOIN campaign_opportunity_participation cop ON cop.id = copt.id_campaign_opportunity_participation
                  INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                  INNER JOIN campaign c ON c.id = co.id_campaign
                  WHERE ft.id_factor = {$this->get('id')}
                  AND c.id_factor = {$referenced['id_factor']}
                  AND t.type = 'deposit'";
                $referenced['earned'] = Sql::find($sql)[0]['earned'];
            }
            $referencedGroups[$referenced['role']][] = $referenced;
        }
        /**
         * Check the the Pricing model to learn the commission ladder
         */
        $creatorCommissionLevel = 2.5;
        $referredCreatorsCount = count($referencedGroups['creator']);
        if ($referredCreatorsCount > 9 && $referredCreatorsCount < 25) {
            $creatorCommissionLevel = 5;
        } elseif ($referredCreatorsCount > 24 && $referredCreatorsCount < 100) {
            $creatorCommissionLevel = 7.5;
        } elseif ($referredCreatorsCount > 100) {
            $creatorCommissionLevel = 10; //maximum
        }
        /**
         * Get amounts earned from referencing users
         *
         * Again, take a look at the Pricing model to understand how these tables link, the important thing to understand here is that
         * the we are retrieving an influencer's earnings from referring both other influencers and also advertisers.
         */
        $earnedFromGroups = [
            'creator' => 0.00,
            'advertiser' => 0.00
        ];
        $earningsSql =
            'SELECT
            t.amount earned,
            c.id_factor participation_advertiser_id,
            fn.id_factor participation_creator_id,
            n.name,
            copt.entity_type referenced_group_type
            FROM campaign_opportunity_participation_transaction copt
            INNER JOIN campaign_opportunity_participation cop ON cop.id = copt.id_campaign_opportunity_participation
            INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
            INNER JOIN campaign c ON c.id = co.id_campaign
            INNER JOIN factor_network fn ON fn.id = cop.id_factor_network
            INNER JOIN network n ON n.id = fn.id_network
            INNER JOIN factor_transaction ft ON ft.id = copt.id_entity_transaction
            INNER JOIN transaction t ON t.id = ft.id_transaction
            WHERE ft.id_factor = ' . $this->get('id') . '
            AND copt.entity_type IN(' . Pricing::ENTITY_INFLUENCER_REFERRER . ',' . Pricing::ENTITY_ADVERTISER_REFERRER . ')';
        $earnings = Sql::find($earningsSql);
        //calculate earnings at group level
        foreach ($earnings as $earnedFromParticipation) {
            $group = $earnedFromParticipation['referenced_group_type'] == Pricing::ENTITY_INFLUENCER_REFERRER ? 'creator' : 'advertiser';
            $earnedFromGroups[$group] += $earnedFromParticipation['earned'];
        }
        $this->view->setVars(array(
            'code' => 'http://' . $_SERVER['HTTP_HOST'] . '/registro/' . $referenceCode,
            'referencedGroups' => $referencedGroups,
            'earnedFromGroups' => $earnedFromGroups,
            'creatorCommissionLevel' => $creatorCommissionLevel
        ));

    }

}