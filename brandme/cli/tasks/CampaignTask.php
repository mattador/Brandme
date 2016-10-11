<?php

namespace Cli\Tasks;

use Common\Services\Campaign\Statuses;
use Entities\CampaignOpportunity;

/**
 * Class CampaignTask
 *
 * @package Cli\Tasks
 */
class CampaignTask extends \Phalcon\CLI\Task
{

    /**
     * If an opportunity is set as pending_execution but the date_start value is in the past then update the status to executing
     */
    public function startOpportunityAction()
    {
        $pending = CampaignOpportunity::find(
            'status = '.Statuses::OPPORTUNITY_PENDING_EXECUTION.' AND date_start < "'.date('Y-m-d H:i:s').'"'
        );
        /** @var CampaignOpportunity $opportunity */
        foreach ($pending as $opportunity) {
            $opportunity
                ->setStatus(Statuses::OPPORTUNITY_EXECUTING)
                ->setUpdatedAt(date('Y-m-d H:i:s'))
                ->update();
        }
    }

}