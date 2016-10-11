<?php
namespace Cli\Tasks;

use Frontend\Services\Search\Elastic;
use Common\Services\Sql;

/**
 * This task is responsible for charging and compensating advertisers and content creators
 * Class PricingTask
 */
class ReindexElasticTask extends \Phalcon\CLI\Task
{

    /**
     * Re-indexes entire database of creators in Elastic, regardless of whether they are linked or not
     */
    public function mainAction()
    {
        $elastic = new Elastic();
        $elastic->restructure();
        $sql
            = 'SELECT fn.id FROM factor_network fn
                INNER JOIN network n ON n.id = fn.id_network
                WHERE n.status = "active"
                AND n.can_participate = 1
                AND n.id > 1';
        $factorNetworkIds = Sql::find($sql);
        foreach ($factorNetworkIds as $factorNetwork) {
            $elastic->upsert($factorNetwork['id']);
        }

    }
}

