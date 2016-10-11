<?php
namespace Frontend\Controllers\Account\Creator\Opportunity;

use Entities\FactorNetwork;
use Frontend\Module;
use Frontend\Controllers\Account\AccountControllerBase;
use Entities\CampaignOpportunity;
use Frontend\Services\Campaign\Negotiation;
use Frontend\Widgets\Translate;

/**
 * Class ActionController
 * @package Frontend\Controllers\Account\Creator\Opportunity
 */
class ActionController extends AccountControllerBase
{

    /**
     * The creator chooses not to participate in either stage of negotiation
     *
     * @param $idOpportunity
     * @param $idFactorNetwork
     */
    public function rejectAction($idOpportunity, $idFactorNetwork)
    {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCreatorCampaignOpportunityParticipationOwnership($this->get('id'), $idOpportunity, $idFactorNetwork)) {
            $service->rejectCreatorCampaignOpportunityParticipation($idOpportunity, $idFactorNetwork);
            $this->success(Translate::_('Opportunity Rejected'));
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
        }
        $this->redirect('/creador');
    }

    /**
     * The creator chooses to accept the advertiser's offer in the bid stage of negotiation
     *
     * When an advertiser directly invites a creator, he is making the offering the opening bid.
     * This is the opposite way around with new open bid opportunities, where the influencer creates the opening bid.
     *
     * Internally we create the initial bid on the advertisers behalf
     *
     * @param $idOpportunity
     * @param $idFactorNetwork
     */
    public function acceptBidAction($idOpportunity, $idFactorNetwork)
    {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCreatorCampaignOpportunityParticipationOwnership($this->get('id'), $idOpportunity, $idFactorNetwork)) {
            if(FactorNetwork::findFirst('id = '.$idFactorNetwork)->getStatus() != 'linked'){
                $this->error(Translate::_('You must relink this account before participating any further in the opportunity'));
                $this->redirect('/creador');
            }
            /** @var CampaignOpportunity $opportunity */
            $opportunity = Module::getService('Campaign\Opportunity')->getCreatorOpportunity($this->get('id'), $idOpportunity, $idFactorNetwork);
            //It must be creator's turn and the bid process can't already be complete
            $negotiation = new Negotiation($opportunity['id_participation']);
            if ($negotiation->isBidApproved()) {
                $this->error(Translate::_('The bid has already been accepted'));
                $this->redirect('/creador');
            }
            if (!$negotiation->actionRequiredBy(Negotiation::NEGOTIATION_ENTITY_CREATOR)) {
                $this->error(Translate::_('Your last offer is pending revision'));
                $this->redirect('/creador');
            }
            //attempt to accept the bid
            if ($service->creatorApproveCampaignOpportunityParticipationBid($opportunity['id_participation'], $opportunity['id_campaign'])) {
                $this->success(Translate::_('Oferta Aceptada, favor de generar contenido'));
                //Redirect to content page
                $this->redirect('/creador/oportunidad/' . $idOpportunity . '/social/' . $idFactorNetwork . '/contenido');
            } else {
                $this->error(Translate::_('The offer could not be accepted in this moment, try again later'));
            }
            $this->redirect('/creador');
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/creador');

        }
    }
}