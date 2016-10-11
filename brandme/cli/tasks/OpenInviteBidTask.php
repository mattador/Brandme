<?php
namespace Cli\Tasks;

use Frontend\Services\Campaign\OpenBidInvite;

/**
 * Responsible for insert opportunities into factor_opportunity table
 * Class OpenBidInviteTask
 */
class OpenInviteBidTask extends \Phalcon\CLI\Task
{

    public function mainAction()
    {
        $service = new OpenBidInvite();
        $forceInvite = false;
        $args = func_get_args();
        foreach ($args as $arg) {
            if (array_pop($arg) == 'force') {
                $forceInvite = true;
            }
        }
        $service->invite($forceInvite);
    }

}