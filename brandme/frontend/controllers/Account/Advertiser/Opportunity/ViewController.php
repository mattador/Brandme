<?php
namespace Frontend\Controllers\Account\Advertiser\Opportunity;

use Frontend\Module;
use Frontend\Controllers\Account\AccountControllerBase;
use Entities\Campaign;
use Entities\CampaignOpportunity;
use Common\Services\Campaign\Statuses;
use Frontend\Widgets\Translate;

/**
 * Class ViewController
 * @package Frontend\Controllers\Account\Advertiser\Opportunity
 */
class ViewController extends AccountControllerBase
{
    /**
     * @param $idCampaign
     * @param $idOpportunity
     */
    public function indexAction($idCampaign, $idOpportunity)
    {
        /** @var \Frontend\Services\Campaign\Opportunity\Actions $service */
        if (Module::getService('Campaign\Opportunity\Actions')->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $opportunity = CampaignOpportunity::findFirst('id = ' . $idOpportunity);
            $this->view->setVars([
                'participations' => Module::getService('Campaign\Opportunity')->getAdvertiserCampaignOpportunityParticipations($idOpportunity),
                'opportunity' => $opportunity->getName(),
                'campaign' => Campaign::findFirst('id = ' . $idCampaign)->getName(),
                'showActionLinks' => $opportunity->getStatus() == Statuses::OPPORTUNITY_EXECUTING //only show links for editing if opportunity is running
            ]);
            $this->addAssets('css/brandme/advertiser-opportunity-negotiate.css', 'css/bootstrap-datetimepicker.css', 'js/bootbox.min.js', 'js/moment.js', 'js/bootstrap-datetimepicker.js', 'js/jquery.maskMoney.min.js', 'js/brandme/advertiser-opportunity-negotiate.js');
        } else {
            $this->error(Translate::_('Invalid campaign opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * @param $idCampaign
     * @param $idOpportunity
     */
    public function informationAction($idCampaign, $idOpportunity)
    {
        if (Module::getService('Campaign\Opportunity\Actions')->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $opportunity = Module::getService('Campaign\Opportunity')->getAdvertiserOpportunity($idOpportunity);
            if (!$opportunity) {
                $this->error(Translate::_('Invalid Opportunity'));
                $this->redirect('/anunciante/campana/' . $idCampaign . '/oportunidades');
            }
            if ($opportunity['type'] == 'open') {
                $segmentation = [];
                foreach (explode(',', $opportunity['segmentation']) as $segment) {
                    $segment = explode(':', $segment);
                    if (!isset($segmentation[$segment[0]])) {
                        $segmentation[$segment[0]] = [];
                    }
                    if (isset($segment[1]) && !in_array($segment[1], $segmentation[$segment[0]])) {
                        $segmentation[$segment[0]][] = $segment[1];
                    }
                }
                $opportunity['segmentation'] = $segmentation;
            }
            $this->view->setVars($opportunity);
        } else {
            $this->error(Translate::_('Invalid Campaign Opportunity'));
            $this->redirect('/anunciante');
        }
    }
}