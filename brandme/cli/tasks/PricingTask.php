<?php
namespace Cli\Tasks;

use Frontend\Services\Campaign\Pricing;
use Common\Services\Campaign\Statuses;
use Common\Services\Sql;

/**
 * This task is responsible for charging and compensating advertisers and content creators
 *
 * Class PricingTask
 */
class PricingTask extends \Phalcon\CLI\Task
{

    /**
     * Initiates pricing model to payout creator's for their participation in opportunities
     */
    public function mainAction()
    {
        /** @var \Frontend\Services\Campaign\Pricing $pricing */
        $pricing = new Pricing();
        $sql = 'SELECT
                    cop.id id_participation,
                    f.id id_advertiser
                FROM campaign_opportunity_participation cop
                    INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                    INNER JOIN campaign c ON c.id = co.id_campaign
                    INNER JOIN factor f ON f.id = c.id_factor
                    INNER JOIN factor_balance fb ON fb.id_factor = f.id
                WHERE cop.is_paid = 0
                    AND co.status IN (' . Statuses::OPPORTUNITY_FINISHED . ',' . Statuses::OPPORTUNITY_EXECUTING . ',' . Statuses::OPPORTUNITY_PAUSED . ')
                    AND cop.creator_status = ' . Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED . '
                    AND cop.advertiser_status = ' . Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_DISPATCHED . '
                    AND fb.reserved > 0.00
                    AND DATEDIFF(NOW(), cop.updated_at) >= ' . Pricing::PAYOUT_PERIOD;
        $result = Sql::find($sql);
        foreach ($result as $particpation) {
            $pricing->distributeFunds($particpation['id_participation'], $particpation['id_advertiser']);
        }
    }
}