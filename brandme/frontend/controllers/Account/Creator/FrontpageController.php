<?php
namespace Frontend\Controllers\Account\Creator;

use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Widgets\Translate;

/**
 * Class FrontpageController
 *
 * @package Frontend\Controllers\Account\Creator
 */
class FrontpageController extends AccountControllerBase
{

    /**
     * Creator's dashboard and participation card layout
     */
    public function indexAction()
    {
        $this->addAssets('js/brandme/creator-dashboard.js', 'css/brandme/creator-participation.css');
        $networks = $this->get('networks');
        if (!$this->s->has('has_displayed_startup_messages')) {
            if (!$this->get('rootSegments')) {
                $this->error(
                    Translate::_(
                        'Your profile is %incomplete%, complete it to receive offers',
                        ['incomplete' => '<a href="creador/perfil/informacion">incompleto</a>']
                    )
                );
            }
            $accountsMissingSegmentation = 0;
            $unlinkedAccounts = 0;
            if (!$networks) {
                $this->error(Translate::_('You have no networks connected'));
            } else {
                foreach ($networks as $network) {
                    foreach ($network as $account) {
                        if (!$account['is_linked']) {
                            $unlinkedAccounts++;
                        }
                        if (!$account['segment']) {
                            $accountsMissingSegmentation++;
                        }
                    }
                }
            }
            if ($accountsMissingSegmentation) {
                $this->error(
                    Translate::_(
                        'One or more connected networks have not been %segmented%',
                        ['segmented' => '<a href="creador/redes-sociales">segmentada</a>']
                    )
                );
            }
            if ($unlinkedAccounts) {
                $this->error(
                    Translate::_('One or more of your networks requires relinking')
                );
            }
            $this->s->set('has_displayed_startup_messages', true);
        }
        $this->view->setVars(
            [
                'networks'      => $networks,
                'opportunities' => Module::getService('Campaign\Opportunity')
                    ->getCreatorOpportunities($this->get('id'))
            ]
        );
    }

}