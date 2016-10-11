<?php
namespace Frontend\Controllers\Account\Creator\Opportunity;

use Entities\FactorNetwork;
use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Campaign\Negotiation;
use Frontend\Widgets\Translate;

class ViewController extends AccountControllerBase
{

    /**
     * Creator bids on opportunity
     *
     * @param $idOpportunity
     * @param $idFactorNetwork
     */
    public function bidAction($idOpportunity, $idFactorNetwork)
    {
        if (FactorNetwork::findFirst('id = '.$idFactorNetwork)->getStatus() != 'linked') {
            $this->error(Translate::_('You must relink this account before participating any further in the opportunity'));
            $this->redirect('/creador');
        }
        $this->addAssets('css/brandme/creator-participation.css', 'js/jquery.maskMoney.min.js', 'js/brandme/creator-participation-bid.js');
        //does opp exist and assigned to network / user
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        if (!$actionService->checkCreatorCampaignOpportunityParticipationOwnership(
            $this->get('id'),
            $idOpportunity,
            $idFactorNetwork
        )
        ) {
            $this->error('Invalid Opportunity');
            $this->redirect('/creador');
        }
        $opportunityService = Module::getService('Campaign\Opportunity');
        $opportunity = $opportunityService->getCreatorOpportunity($this->get('id'), $idOpportunity, $idFactorNetwork);
        if (!$opportunity) {
            $this->error('Invalid Opportunity');
            $this->redirect('/creador');
        }
        $negotiation = new Negotiation($opportunity['id_participation']);
        if ($negotiation->isBidApproved()) {
            $this->success(Translate::_('Your bid has already been approved'));
            $this->redirect('/creador');
        }

        if ($negotiation->hasNegotiatingStarted()
            && $negotiation->getLatestNegotiationElement(Negotiation::NEGOTIATION_STAGE_BID)['entity']
            == Negotiation::NEGOTIATION_ENTITY_ADVERTISER
        ) {
            $suggestedBid = $negotiation->getLatestNegotiationElement(Negotiation::NEGOTIATION_STAGE_BID)['bid'];
            $creatorsBid = $negotiation->getLatestNegotiationElement(
                Negotiation::NEGOTIATION_STAGE_BID,
                Negotiation::NEGOTIATION_ENTITY_CREATOR
            )['bid'];
            $creatorsOriginalBid = false;
            foreach ($negotiation->getNegotiation(Negotiation::NEGOTIATION_STAGE_BID) as $bidEvent) {
                if ($bidEvent['entity'] == Negotiation::NEGOTIATION_ENTITY_CREATOR) {
                    $creatorsOriginalBid = $bidEvent['bid'];
                    break;
                }
            }
            $this->view->setVars(
                [
                    'suggested_bid' => $suggestedBid,
                    'original_bid'  => $creatorsOriginalBid,
                    //This is the threshold bid, the creator cannot bid higher than the limit they themselves put.
                    'last_bid'      => $creatorsBid
                ]
            );

        } elseif ($negotiation->hasNegotiatingStarted()
            && $negotiation->getLatestNegotiationElement(
                Negotiation::NEGOTIATION_STAGE_BID
            )['entity'] == Negotiation::NEGOTIATION_ENTITY_CREATOR
        ) {
            //Creator needs to wait their turn
            $this->success(Translate::_('Your bid has already been sent and is awaiting approval'));
            $this->redirect('/creador');
        }
        $isDirectOfferOpportunity = $opportunity['opportunity_type'] == 'direct' ? true : false;
        $this->view->setVar('is_direct_offer_opportunity', $isDirectOfferOpportunity);

        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->redirect('/creador/oportunidad/'.$idOpportunity.'/social/'.$idFactorNetwork.'/bid');
            }
            $post = $this->getPost();
            $errors = [];
            if (isset($post['decline'])) {
                $this->redirect('/creador/oportunidad/'.$idOpportunity.'/social/'.$idFactorNetwork.'/declinar');
            }
            //The pitch is not obligatory
            $pitch = !$isDirectOfferOpportunity && isset($post['pitch']) ? trim($post['pitch']) : '';
            if (!isset($post['amount']) || !preg_match('/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/', $post['amount'])) {
                $errors[] = 'Invalid bid amount';
            } else {
                $post['amount'] = preg_replace('/[^\d\.]/', '', $post['amount']);

                if ($post['amount'] < 1) {
                    $errors[] = 'Please enter a valid bid amount';
                }
                if (!is_null($opportunity['maximum_bid']) && $post['amount'] > $opportunity['maximum_bid']) {
                    $errors[] = 'Your bid is higher than the maximum permitted bid';
                }
                if (isset($creatorsOriginalBid) && ($creatorsOriginalBid !== false && $post['amount'] > $creatorsOriginalBid)) {
                    $errors[] = 'You cannot bid higher than your original bid';
                }
            }
            if (empty($errors)) {
                //The creator cannot update pitch once set
                $pitch = strlen($opportunity['pitch']) ? $opportunity['pitch'] : $pitch;
                if (!$actionService->bidCreatorCampaignOpportunityParticipation(
                    $this->get('id'),
                    $idOpportunity,
                    $idFactorNetwork,
                    number_format($post['amount'], 2),
                    $pitch
                )
                ) {
                    $errors[] = 'Could not bid';
                } else {
                    $this->success(Translate::_('Your bid has been sent'));
                }
                $this->redirect('/creador');
            } else {
                $this->view->setVar('messages', $errors);
            }
        }
        $this->view->setVars(
            [
                'idOpportunity'   => $idOpportunity,
                'idFactorNetwork' => $idFactorNetwork,
                'opportunity'     => $opportunity
            ]
        );
    }

    public function contentAction($idOpportunity, $idFactorNetwork)
    {
        if (FactorNetwork::findFirst('id = '.$idFactorNetwork)->getStatus() != 'linked') {
            $this->error(Translate::_('You must relink this account before participating any further in the opportunity'));
            $this->redirect('/creador');
        }
        $this->addAssets('css/brandme/creator-participation.css', 'js/jquery.limit-1.2.js', 'js/brandme/creator-participation-content.js');
        //does opp exist and assigned to network / user
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        if (!$actionService->checkCreatorCampaignOpportunityParticipationOwnership(
            $this->get('id'),
            $idOpportunity,
            $idFactorNetwork
        )
        ) {
            $this->error('Invalid Opportunity');
            $this->redirect('/creador');
        }
        $opportunityService = Module::getService('Campaign\Opportunity');
        $opportunity = $opportunityService->getCreatorOpportunity($this->get('id'), $idOpportunity, $idFactorNetwork);
        if (!$opportunity) {
            $this->error('Invalid Opportunity');
            $this->redirect('/creador');
        }
        $negotiation = new Negotiation($opportunity['id_participation']);
        //check that the participation is actually ready for content
        if (!$negotiation->isBidApproved()) {
            $this->error(Translate::_('Your bid must be approved before you can negotiate content'));
            $this->redirect('/creador');
        } elseif ($negotiation->isNegotiationComplete()) {
            $this->success(Translate::_('The negotiation has been complete'));
            $this->redirect('/creador');
        }
        $contentSuggestion = $negotiation->getLatestNegotiationElement(
            Negotiation::NEGOTIATION_STAGE_CONTENT,
            Negotiation::NEGOTIATION_ENTITY_ADVERTISER
        );
        if ($contentSuggestion) {
            $this->view->setVar('content_suggestion', $contentSuggestion['content_suggestion']);
        }
        $creatorsLastContent = $negotiation->getLatestNegotiationElement(
            Negotiation::NEGOTIATION_STAGE_CONTENT,
            Negotiation::NEGOTIATION_ENTITY_CREATOR
        );
        if ($creatorsLastContent) {
            $this->view->setVar('last_content', $creatorsLastContent['content']);
        }
        $errors = [];
        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->redirect('/creador/oportunidad/'.$idOpportunity.'/social/'.$idFactorNetwork.'/contenido');
            }
            $post = $this->getPost();
            if (isset($post['decline'])) {
                $this->redirect('/creador/oportunidad/'.$idOpportunity.'/social/'.$idFactorNetwork.'/declinar');
            }
            if (!isset($post['content']) || !strlen(trim($post['content']))) {
                $this->success('Please enter your content correctly');
                $this->redirect('/creador/oportunidad/'.$idOpportunity.'/social/'.$idFactorNetwork.'/contenido');
            }
            switch ($opportunity['network']) {
                case 'Twitter':
                    if (strlen($post['content']) > \Frontend\Services\Network::NETWORK_TWITTER_CONTENT_LIMIT) {
                        $errors[] = Translate::_('The character limit for twitter is').' '
                            .\Frontend\Services\Network::NETWORK_TWITTER_CONTENT_LIMIT;
                    }
                    break;
            }
            if ($opportunity['link_required']
                && !preg_match(
                    ('@http://'.str_replace('http://', '', $opportunity['link']).'@'),
                    $post['content']
                )
            ) {
                $errors[] = Translate::_('Your content must contain the required website link');
            }
            if ($opportunity['hashtag_required']
                && !preg_match(
                    '/\#'.str_replace('#', '', $opportunity['hashtag']).'/',
                    $post['content']
                )
            ) {
                $errors[] = Translate::_('Your content must contain the required hashtag');
            }
            if ($opportunity['mention_required']
                && !preg_match(
                    '/\@'.str_replace('@', '', $opportunity['mention']).'/',
                    $post['content']
                )
            ) {
                $errors[] = Translate::_('Your content must contain the required mention');
            }
            if (empty($errors)) {
                if (!$actionService->createContentCreatorCampaignOpportunityParticipation(
                    $this->get('id'),
                    $idOpportunity,
                    $idFactorNetwork,
                    trim($post['content'])
                )
                ) {
                    $errors[] = Translate::_('Could not submit your content');
                } else {
                    $this->success(Translate::_('Your content has been sent for review'));
                    $this->redirect('/creador');
                }
            }
        }
        $this->view->setVars(
            [
                'messages'        => $errors,
                'idOpportunity'   => $idOpportunity,
                'idFactorNetwork' => $idFactorNetwork,
                'opportunity'     => $opportunity
            ]
        );
    }
}