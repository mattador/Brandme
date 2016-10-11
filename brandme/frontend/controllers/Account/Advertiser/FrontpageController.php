<?php
namespace Frontend\Controllers\Account\Advertiser;

use Frontend\Module;
use Frontend\Controllers\Account\AccountControllerBase;
use Phalcon\Mvc\View;

class FrontpageController extends AccountControllerBase
{

    /**
     * Lists campaigns
     *
     * @todo implement pagination
     */
    public function indexAction()
    {
        $this->addAssets(
            'js/bootbox.min.js',
            'js/brandme/advertiser-dashboard.js',
            'js/jquery.maskMoney.min.js',
            'css/brandme/advertiser-dashboard.css'
        );
        $this->view->setVar(
            'campaigns',
            Module::getService('Campaign\Opportunity')->getAdvertiserCampaigns($this->get('id'))
        );
    }
}