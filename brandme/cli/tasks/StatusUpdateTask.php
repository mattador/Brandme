<?php
namespace Cli\Tasks;

use Common\Services\Campaign\Statuses;
use Common\Services\Sql;

/**
 * This cron task is responsible for dispatching status updates
 *
 * Class StatusUpdateTask
 */
class StatusUpdateTask extends \Phalcon\CLI\Task
{

    /**
     * Dispatch updates
     */
    public function mainAction()
    {
        $sql
            = 'SELECT
                cop.id id_participation,
                cop.content,
                fn.id id_factor_network,
                n.name network
                FROM campaign_opportunity_participation cop
                INNER JOIN campaign_opportunity co ON co.id = cop.id_campaign_opportunity
                INNER JOIN factor_network fn ON fn.id = cop.id_factor_network
                INNER JOIN network n ON n.id = fn.id_network
                WHERE cop.creator_status = ' . Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PENDING_PUBLISHING . ' AND
                cop.advertiser_status = ' . Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_PENDING_DISPATCH . ' AND
                co.status= ' . Statuses::OPPORTUNITY_EXECUTING . ' AND
                fn.status = "linked" AND
                n.status = "active"';
        $result = Sql::find($sql);
        foreach ($result as $participation) {
            $participation['content'] = stripslashes($participation['content']);
            $this->dispatchCampaignOpportunityParticipation($participation);
        }
    }

    public function dispatchCampaignOpportunityParticipation($participation)
    {
        $start = microtime(true);
        echo 'ID: ' . $participation['id_participation'] . PHP_EOL;
        echo 'CONTENT: ' . $participation['content'] . PHP_EOL;
        echo 'NETWORK: ' . $participation['network'] . PHP_EOL;
        //first set the creator status to "publishing" so it doesn't get re-dispatched
        $phql
            = 'UPDATE Entities\CampaignOpportunityParticipation SET
                creator_status = ' . Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING . '
                WHERE id = ' . $participation['id_participation'];
        try {
            $this->getDi()->get('models')->executeQuery($phql);
        } catch (\Phalcon\Mvc\Model\Exception $e) {
            echo 'Couldn\'t lock status update, skipping' . PHP_EOL;
            return false;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, APPLICATION_HOST . "/social/dispatch");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, array(
                'Token: ' . $this->getDI()->get('token')
            )
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($participation));
        $output = curl_exec($ch);
        $response = curl_getinfo($ch);

        echo 'RESPONSE CODE: ' . $response['http_code'] . PHP_EOL;
        if ($response['http_code'] == 200) {
            echo 'RESPONSE ERROR: ' . curl_error($ch) . PHP_EOL;
        }
        curl_close($ch);
        echo 'RESPONSE OUTPUT: "' . $output . '"' . PHP_EOL;
        echo 'TIME TAKE: ' . (microtime(true) - $start) . PHP_EOL;
        echo str_repeat('.', 50) . PHP_EOL;
        if ($response['http_code'] == 200 && !strlen($output)) {
            echo 'STATUS: success' . PHP_EOL;
            //A post is ONLY considered successfully if there is no output and the response type is 200
            $phql
                = 'UPDATE Entities\CampaignOpportunityParticipation SET
                creator_status = ' . Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHED . ',
                advertiser_status = ' . Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_DISPATCHED . '
                WHERE id = ' . $participation['id_participation'];
            $this->getDi()->get('models')->executeQuery($phql);
        } else {
            echo 'STATUS: error' . PHP_EOL;
            $phql
                = 'UPDATE Entities\CampaignOpportunityParticipation SET
                creator_status = ' . Statuses::CREATOR_OPPORTUNITY_PARTICIPATION_PUBLISHING_ERROR . ',
                advertiser_status = ' . Statuses::ADVERTISER_OPPORTUNITY_PARTICIPATION_DISPATCH_ERROR . ',
                dispatch_code = "' . $response['http_code'] . '"
                WHERE id = ' . $participation['id_participation'];
            $this->getDi()->get('models')->executeQuery($phql);
        }
    }

}