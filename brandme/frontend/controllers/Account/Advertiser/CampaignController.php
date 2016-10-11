<?php
namespace Frontend\Controllers\Account\Advertiser;

use Frontend\Module;
use Frontend\Controllers\Account\AccountControllerBase;
use Entities\Campaign;
use Entities\FactorActivity;
use Entities\FactorBalance;
use Frontend\Services\Campaign\Pricing;
use Frontend\Widgets\Translate;
use Phalcon\Exception;
use Phalcon\Tag;
use Phalcon\Validation;

class CampaignController extends AccountControllerBase
{

    /**
     * Creates a new campaign
     *
     * @throws Exception
     */
    public function createAction()
    {
        if (!$this->request->isAjax()) {
            $this->redirect('/anunciante');
        }
        $post = $this->getPost();
        $errors = [];
        if (!isset($post['name']) || !strlen(trim($post['name']))) {
            $errors[] = Translate::_('Please specify a unique campaign name');
        }
        if (!isset($post['budget']) || !preg_match('/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/', $post['budget'])) {
            $errors[] = Translate::_('Please enter a valid budget amount');
        }
        if (count($errors)) {
            $this->response->setContent(
                json_encode(['error' => true, 'msg' => $errors])
            );

            return $this->response;
        } else {
            $errors = [];
            $post['budget'] = preg_replace('/[^\d\.]/', '', $post['budget']);
            $campaign = Campaign::count('id_factor = '.$this->get('id').' AND name ="'.$post['name'].'"');
            if (intval($campaign) > 0) {
                $errors[] = Translate::_('Please specify a unique campaign name');
            }
            if ((float)$post['budget'] < Pricing::STANDARD_MARKUP) {
                $errors[] = Translate::_('You haven\'t met the minimum amount required to start a campaign');
            }
            $availableFunds = FactorBalance::findFirst('id_factor = '.$this->get('id'));
            if ((float)$post['budget'] > $availableFunds->getBalance()) {
                $errors[] = Translate::_('You cannot create a campaign with more funds then you have available');
            }
            if (count($errors)) {
                $this->response->setContent(
                    json_encode(['error' => true, 'msg' => $errors])
                );

                return $this->response;
            } else {
                $campaign = new Campaign();
                $campaign
                    ->setBudget((float)$post['budget'])
                    ->setIdFactor($this->get('id'))
                    ->setIsArchived(0)
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setName(trim($post['name']))
                    ->create();

                //We reduce the advertisers balance and reserve it for the campaign, but no transaction actually takes place yet.
                $factorBalance = FactorBalance::findFirst('id_factor = '.$this->get('id'));
                $factorBalance->setBalance($factorBalance->getBalance() - (float)$post['budget']);
                $factorBalance->setReserved((float)$factorBalance->getReserved() + (float)$post['budget']);
                $factorBalance->update();

                $factorActivity = new FactorActivity();
                $factorActivity
                    ->setCreatedAt(date('Y-m-d H:i:s'))
                    ->setIdFactor($this->get('id'))
                    ->setMessage(
                        Translate::_(
                            'You have created a new campaign called "%campaign%" and the budget has been reserved from your account balance',
                            ['campaign' => trim($post['name'])]
                        )
                    )
                    ->save();

                $this->success(
                    Translate::_(
                        '$%amount% USD has been reserved from your balance for this campaign',
                        ['amount' => $post['budget']]
                    )
                );
                $this->refreshSession();
                $this->response->setContent(
                    json_encode(['error' => false, 'redirect' => '/anunciante/campana/'.$campaign->getId().'/oportunidad/crear'])
                );

                return $this->response;
            }
        }
    }

    /**
     * Lists a campaign's opportunities
     *
     * @param $idCampaign
     */
    public function listAction($idCampaign)
    {
        $this->addAssets('css/brandme/advertiser-opportunities.css');
        if (!intval(Campaign::count('id = '.$idCampaign.' AND id_factor ='.$this->get('id')))) {
            $this->error(Translate::_('The specified campaign does not exist'));
            $this->redirect('/anunciante');
        }
        $this->view->setVars(
            [
                'opportunities' => Module::getService('Campaign\Opportunity')->getAdvertiserCampaignOpportunities(
                    $idCampaign
                ),
                'idCampaign'    => $idCampaign
            ]
        );
    }

}