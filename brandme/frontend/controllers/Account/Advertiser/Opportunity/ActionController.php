<?php
namespace Frontend\Controllers\Account\Advertiser\Opportunity;

use Common\Services\Campaign\Statuses;
use Entities\Campaign;
use Entities\CampaignOpportunity;
use Entities\CampaignOpportunityParticipation;
use Entities\FactorActivity;
use Entities\FactorBalance;
use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Campaign\Negotiation;
use Frontend\Services\Campaign\Pricing;
use Frontend\Widgets\Currency;
use Frontend\Widgets\Translate;

class ActionController extends AccountControllerBase
{
    /**
     * Pause an opportunity
     *
     * @param $idCampaign
     * @param $idOpportunity
     */
    public function pauseAction($idCampaign, $idOpportunity)
    {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $service->pauseOpportunity($idOpportunity);
            $this->success(Translate::_('Opportunity Paused'));
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidades');
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * Resume an opportunity
     *
     * @param $idCampaign
     * @param $idOpportunity
     */
    public function resumeAction($idCampaign, $idOpportunity)
    {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            if (Module::getService('Campaign/Opportunity')->getCreditLeftInCampaign($idCampaign)
                < Pricing::STANDARD_MARKUP
            ) {
                $this->error(
                    Translate::_('There is not enough credit left in this campaign to resume the opportunity')
                );
                $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidades');
            }
            if ($service->resumeOpportunity($idOpportunity)) {
                $this->success(Translate::_('Opportunity Resumed'));
            } else {
                $this->error(Translate::_('The opportunity could not be resumed'));
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * Rename a campaign
     *
     * @param $idCampaign
     * @return \Phalcon\Http\Response|\Phalcon\HTTP\ResponseInterface
     */
    public function renameCampaignAction($idCampaign)
    {
        $isAjax = $this->request->isAjax();
        if (!Module::getService('Campaign\Opportunity\Actions')->checkCampaignOwnership(
                $this->get('id'),
                $idCampaign
            )
            || !$this->request->isPost()
        ) {
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'error' => true,
                            'msg'   => Translate::_('Invalid Campaign')
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->error(Translate::_('Invalid Campaign'));
                $this->redirect('/anunciante');
            }
        }
        $errors = [];
        $post = $this->getPost();
        if (!isset($post['name']) || !strlen($post['name'])) {
            $errors[] = Translate::_('Invalid campaign name');

        } elseif (intval(Campaign::count('id_factor = '.$this->get('id').' AND name ="'.$post['name'].'"')) > 0) {
            $errors[] = Translate::_('Please specify a unique campaign name');
        }
        if (count($errors)) {
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(['error' => true, 'msg' => $errors[0]])
                );

                return $this->response;
            } else {
                $this->error($errors[0]);
                $this->redirect('/anunciante');
            }
        }
        /** @var Campaign $campaign */
        Campaign::findFirst('id_factor = '.$this->get('id').' AND id = '.$idCampaign)
            ->setName(trim($post['name']))
            ->update();
        $this->success(Translate::_('You have renamed the campaign successfully'));
        if ($isAjax) {
            $this->response->setContent(
                json_encode(['error' => false])
            );

            return $this->response;
        } else {
            $this->redirect('/anunciante');
        }

    }

    /**
     * Transfer funds between account and campaign and vice versa
     * I was quit sick when I wrote this code, I apologise for the lack clarity
     *
     * @param $idCampaign
     * @return \Phalcon\Http\Response|\Phalcon\HTTP\ResponseInterface
     */
    public function transferFundsAction($idCampaign)
    {
        $isAjax = $this->request->isAjax();
        if (!Module::getService('Campaign\Opportunity\Actions')->checkCampaignOwnership(
            $this->get('id'),
            $idCampaign
        )
        ) {
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'error' => true,
                            'msg'   => Translate::_('Invalid Campaign')
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->error(Translate::_('Invalid Campaign'));
                $this->redirect('/anunciante');
            }
        }
        $this->refreshSession();
        $accountBalance = $this->get('account.balance')->getBalance();
        $campaignBalance = Module::getService('Campaign/Opportunity')->getCreditLeftInCampaign(
            $idCampaign
        );
        if (!$this->request->isPost()) {
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'account'  => Translate::_(
                                'Your balance: $%amount% USD',
                                ['amount' => Currency::format($accountBalance)]
                            ),
                            'campaign' => Translate::_(
                                'Campaign Funds $%amount% USD',
                                [
                                    'amount' => Currency::format($campaignBalance)
                                ]
                            ),
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->redirect('/anunciante');
            }
        }
        $post = $this->getPost();
        $errors = [];
        if (!isset($post['from'])
            || !in_array($post['from'], ['account', 'campaign'])
            || !isset($post['to'])
            || !in_array($post['to'], ['account', 'campaign'])
            || $post['from'] == $post['to']
        ) {
            $errors[] = Translate::_('The transfer was not successful');
        }
        if (!isset($post['amount']) || !preg_match('/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/', $post['amount'])) {
            $errors[] = Translate::_('Please enter a valid amount');
        }
        $post['amount'] = preg_replace('/[^\d\.]/', '', $post['amount']);
        if ($post['from'] == 'account' && (float)$post['amount'] > $accountBalance) {
            $errors[] = Translate::_('You cannot transfer more money than you have available in your account to the campaign');
        }
        if ($post['from'] == 'campaign' && (float)$post['amount'] > $campaignBalance) {
            $errors[] = Translate::_('You cannot transfer more money than you have available in the campaign to your account');
        }
        //Show one error at a time
        if (count($errors)) {
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'error' => true,
                            'msg'   => $errors[0],
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->error($errors[0]);
                $this->redirect('/anunciante');
            }
        }
        if ($post['from'] == 'account') {
            /** @var FactorBalance $factorBalance */
            $factorBalance = FactorBalance::findFirst('id_factor = '.$this->get('id'));
            $factorBalance->setBalance($factorBalance->getBalance() - (float)$post['amount']);
            $factorBalance->setReserved((float)$factorBalance->getReserved() + (float)$post['amount']);
            $factorBalance->update();

            /** @var Campaign $campaign */
            $campaign = Campaign::findFirst('id = '.$idCampaign);
            $campaign->setBudget($campaign->getBudget() + $post['amount'])
                ->update();

            $factorActivity = new FactorActivity();
            $factorActivity
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setIdFactor($this->get('id'))
                ->setMessage(
                    Translate::_(
                        'You have reserved $%amount% for the campaign "%campaign%"',
                        ['campaign' => $campaign->getName(), 'amount' => Currency::format($post['amount'])]
                    )
                )
                ->save();
            $this->refreshSession();
            $this->success(
                Translate::_(
                    '$%amount% USD has been reserved from your balance',
                    ['amount' => $post['amount']]
                )
            );
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'error' => false
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->redirect('/anunciante/campana/'.$campaign->getId().'/oportunidades');
            }
        } else {
            /** @var FactorBalance $factorBalance */
            $factorBalance = FactorBalance::findFirst('id_factor = '.$this->get('id'));
            $factorBalance->setBalance($factorBalance->getBalance() + (float)$post['amount']);
            $factorBalance->setReserved((float)$factorBalance->getReserved() - (float)$post['amount']);
            $factorBalance->update();

            /** @var Campaign $campaign */
            $campaign = Campaign::findFirst('id = '.$idCampaign);
            $campaign->setBudget($campaign->getBudget() - $post['amount'])
                ->update();

            $factorActivity = new FactorActivity();
            $factorActivity
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setIdFactor($this->get('id'))
                ->setMessage(
                    Translate::_(
                        'You have transferred $%amount% from the campaign "%campaign%" to your account',
                        ['campaign' => $campaign->getName(), 'amount' => Currency::format($post['amount'])]
                    )
                )
                ->save();
            $this->refreshSession();
            $this->success(
                Translate::_(
                    '$%amount% USD has been transferred back to your account balance',
                    ['amount' => $post['amount']]
                )
            );
            if ($isAjax) {
                $this->response->setContent(
                    json_encode(
                        [
                            'error' => false
                        ]
                    )
                );

                return $this->response;
            } else {
                $this->redirect('/anunciante/campana/'.$campaign->getId().'/oportunidades');
            }
        }
    }

    public function rejectParticipationAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            if ($service->rejectCampaignOpportunityParticipation($idParticipation)) {
                $this->success(Translate::_('Participation Rejected'));
            } else {
                $this->error(Translate::_('Invalid Participation'));
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * Performing an action on a participation belonging to a non-executing opportunity is prohibited
     *
     * @param $idCampaign
     * @param $idOpportunity
     */
    protected function validateOpportunityStatus($idCampaign, $idOpportunity)
    {
        if (CampaignOpportunity::findFirst('id = '.$idOpportunity)->getStatus() != Statuses::OPPORTUNITY_EXECUTING
        ) {
            $this->error(
                Translate::_('You can only perform actions on participations if the opportunity is executing')
            );
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        }
    }


    public function reactivateParticipationAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            if ($service->reactivateCampaignOpportunityParticipation($idParticipation)) {
                $this->success(Translate::_('Participation Reactivated'));
            } else {
                $this->error(Translate::_('Invalid Participation'));
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    public function approveParticipationBidAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            //If there is not enough money left we need to change the campaign status to OPPORTUNITY_PAUSED_NO_MONEY
            /** @var CampaignOpportunityParticipation $participation */
            $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
            if ((Module::getService('Campaign\Opportunity')->getCreditLeftInCampaign($idCampaign)
                    + $participation->getBidWithMarkupReserved()) < Pricing::STANDARD_MARKUP
            ) {
                $opportunities = CampaignOpportunity::find('id_campaign = '.$idCampaign);
                foreach ($opportunities as $opportunity) {
                    $service->pauseOpportunity($opportunity->getId(), Statuses::OPPORTUNITY_PAUSED_NO_MONEY);
                }
                $this->error(
                    Translate::_('This campaign has been paused as all assigned credit has been consumed')
                );
                $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
            }
            if ($service->approveCampaignOpportunityParticipationBid($idCampaign, $idParticipation)) {
                $this->success(Translate::_('Participation bid approved'));
            } else {
                $this->error(Translate::_('The participation bid cannot be approved'));
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * The advertiser can specify exactly when a message will dispatch
     *
     * @param $idCampaign
     * @param $idOpportunity
     * @param $idParticipation
     */
    public function approveParticipationContentAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $service = Module::getService('Campaign\Opportunity\Actions');
        if ($service->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            $dispatchAt = date('Y-m-d H:i:s');
            if ($this->request->isPost()) {
                $p = $this->getPost();
                if (isset($p['dispatch_at'])) {
                    $dispatchAt = Module::getService('Time')->timezoneTimeToUtc(
                        $p['dispatch_at'],
                        $this->get('account.meta')->getTimezone()
                    );
                }
            }
            if ((strtotime($dispatchAt) - time()) / 24 / 60 / 60 / 31 > 1) { //+1 for timezone tolerance
                $this->error(Translate::_('You cannot program a message to dispatch more than 30 days from now'));
                $this->redirect('/anunciante');
            }
            if ($service->approveCampaignOpportunityParticipationContent($idParticipation, $dispatchAt)) {
                $this->success(Translate::_('Participation Content Approved'));
            } else {
                $this->error(Translate::_('Invalid Participation Content'));
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * @param $idCampaign
     * @param $idOpportunity
     * @param $idParticipation
     */
    public function negotiateParticipationBidAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        if ($actionService->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            $errors = [];
            if ($this->request->isPost()) {
                //@todo This is probably not 100% safe against injection. Correct.
                $participation = CampaignOpportunityParticipation::findFirst('id = '.$idParticipation);
                $negotiation = new Negotiation($idParticipation);
                $bidProcess = $negotiation->getNegotiation(Negotiation::NEGOTIATION_STAGE_BID);
                $creatorsBid = 0.00;
                /*$advertisersLastSuggestedAmount = 0.00;
                for ($i = count($bidProcess) - 1; $i >= 0; $i--) {
                    if ($bidProcess[$i]['entity'] == Negotiation::NEGOTIATION_ENTITY_ADVERTISER) {
                        $advertisersLastSuggestedAmount = $bidProcess[$i]['bid_with_markup'];
                        break;
                    }
                }*/
                for ($i = count($bidProcess) - 1; $i >= 0; $i--) {
                    if ($bidProcess[$i]['entity'] == Negotiation::NEGOTIATION_ENTITY_CREATOR) {
                        $creatorsBid = $bidProcess[$i]['bid_with_markup'];
                        break;
                    }
                }
                /** @var CampaignOpportunity $campaignOpportunity */
                $campaignOpportunity = CampaignOpportunity::findFirst('id = '.$idOpportunity);
                $post = $this->getPost();
                $amount = preg_replace('/[^\d\.]/', '', $post['amount']);
                if (!is_numeric($amount)) {
                    $this->error(Translate::_('Please enter a valid bid amount'));
                } elseif ($amount < Pricing::STANDARD_MARKUP) {
                    $this->error(Translate::_('Please suggest a higher bid'));
                } elseif ($campaignOpportunity->getType() == 'open'
                    && $amount > $campaignOpportunity->getMaximumBid()
                ) {
                    $this->error(
                        Translate::_(
                            'You cannot suggest an amount larger than the maximum bid defined for this opportunity'
                        )
                    );
                } /* elseif ($post['amount'] < $advertisersLastSuggestedAmount) {
                        $this->error('Please suggest an amount larger than your last suggested bid'); */
                elseif ($amount >= $creatorsBid) {
                    $this->error(Translate::_('Please suggest an amount lower than the actual bid'));
                } else {
                    if (!$actionService->negotiateAdvertiserCampaignOpportunityParticipationBid(
                        $idCampaign,
                        $idParticipation,
                        $amount
                    )
                    ) {
                        $this->error(Translate::_('Your bid suggestion could not be sent'));
                    } else {
                        $this->success(Translate::_('Bid counter suggestion sent'));
                    }
                }
                $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
            }
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    public function negotiateParticipationContentAction(
        $idCampaign,
        $idOpportunity,
        $idParticipation
    ) {
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        if ($actionService->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            $this->validateOpportunityStatus($idCampaign, $idOpportunity);
            $errors = [];
            if ($this->request->isPost()) {
                //@todo This was lazy, it is probably not safe against injection. Correct.
                $post = $this->getPost();
                if (!isset($post['content_suggestion']) || !strlen(trim($post['content_suggestion']))) {
                    $this->error(Translate::_('Please enter a valid response, or content suggestion'));
                } else {
                    if (!$actionService->negotiateAdvertiserCampaignOpportunityParticipationContent(
                        $idParticipation,
                        trim($post['content_suggestion'])
                    )
                    ) {
                        $this->error(Translate::_('Your suggestion has not been sent'));
                    } else {
                        $this->success(Translate::_('Your suggestion has been sent'));
                    }
                }
            }
            $this->redirect('/anunciante/campana/'.$idCampaign.'/oportunidad/'.$idOpportunity);
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante');
        }
    }

    /**
     * Directly invites an influencer to partiicpate in an opportunity
     *
     * @param $idCampaign
     * @param $idOpportunity
     */
    public function directOfferInviteAction($idCampaign, $idOpportunity)
    {
        $actionService = Module::getService('Campaign\Opportunity\Actions');
        if ($actionService->checkCampaignOpportunityOwnership($this->get('id'), $idCampaign, $idOpportunity)) {
            if ($this->request->isPost()) {
                $post = $this->getPost();
                if (!isset($post['networkId'])) { //networkId actually refers to id_factor_network in this case, don't confuse it like I just did :s
                    $this->error(Translate::_('The selected account is invalid'));
                    $this->redirect('/anunciante/buscador');
                }
                $opportunity = Module::getService('Campaign/Opportunity')->getAdvertiserOpportunity(
                    $idOpportunity
                );
                if ($opportunity['type'] != 'direct') {
                    $this->error(Translate::_('The opportunity selected is not of type direct invite'));
                    $this->redirect('/anunciante/buscador');
                }
                $account = Module::getService('Account')->getFactorNetworkByOpportunityRelation(
                    $post['networkId'],
                    $idOpportunity
                );
                if (!$account) {
                    $this->error(Translate::_('The selected account is invalid'));
                    $this->redirect('/anunciante/buscador');
                }
                if ($opportunity['network'] != $account['network']) {
                    $this->error(
                        Translate::_('The selected account does not belong to the same network as the opportunity')
                    );
                    $this->redirect('/anunciante/buscador');
                }
                if (!is_null($account['id_opportunity'])) {
                    $this->error(Translate::_('You have already invited this creator to participate'));
                    $this->redirect('/anunciante/buscador');
                }
                /**
                 * If the base price is specified we use that. Else the Advertiser can make an offer.
                 * Note that the Pricing::STANDARD_MARKUP is not a random value, it is the minimum required for the pricing model
                 * to work out funds distribution correctly.
                 */
                $postOffer = false;
                if (isset($post['offer']) && preg_match('/^\$?\d+(,\d{3})*\.?[0-9]?[0-9]?$/', $post['offer'])) {
                    $postOffer = preg_replace('/[^\d\.]/', '', $post['offer']);
                    $postOffer = $postOffer >= Pricing::STANDARD_MARKUP ? $postOffer : false;
                }
                $price = !is_null($account['price']) ? $account['price'] * Pricing::STANDARD_MARKUP : $postOffer;
                if (!$price) {
                    $this->error(
                        Translate::_('Please only enter valid monetary amounts for negotiating starting prices')
                    );
                    $this->redirect('/anunciante/buscador');
                }
                /*
                 @deprecated - Direct offer opportunities don't have a maximum bid constraint, only open bid opportunities do
                 if ($account['price'] > $opportunity['maximum_bid']) {
                    $this->error(Translate::_('The participation cost defined by this influencer is greater than the maximum bid configured for this opportunity'));
                    $this->redirect('/anunciante/buscador');
                }*/
                if (Module::getService('Campaign/Opportunity')->getCreditLeftInCampaign($idCampaign) < $price) {
                    $this->error(
                        Translate::_(
                            'The campaign which this opportunity belongs to does not have enough credit available to meet the participation cost defined by the influencer'
                        )
                    );
                    $this->redirect('/anunciante/buscador');
                }
                //OK all requirements are met, invite the influencer and make first bid (but leave status in new)
                $idParticipation = Module::getService('Campaign/DirectInvite')->invite(
                    $idOpportunity,
                    $account['id_factor_network'],
                    $price
                );

                //finally send the invitation alert and record activity
                $activity = new FactorActivity();
                $activity
                    ->setIdFactor($account['id_factor'])
                    ->setMessage(
                        'Has recibido una nueva oferta directa para participar en "'.$opportunity['opportunity'].'"'
                    )
                    ->setCreatedAt('Y-m-d H:i:s')
                    ->save();

                $mail = $this->getMail();
                $mail->send(
                    $account['email'],
                    'new_direct_offer',
                    'Brandme - Recibiste Oferta Directa',
                    [
                        'name'             => $account['first_name'],
                        'referral_url'     => APPLICATION_HOST.'/registro/'.$account['referral_code'],
                        'opportunity_name' => $opportunity['opportunity'],
                        'login_url'        => APPLICATION_HOST.'/',
                        'avatar'           => json_decode($account['json'])->photoURL,
                        'expiry_date'      => Module::getService('Time')->utcToTimezone(
                            date('Y-m-d H:i:s', strtotime('NOW +3 DAY')),
                            $account['timezone'],
                            'd/m/Y'
                        ),
                        'offer'            => Currency::format($price / Pricing::STANDARD_MARKUP)
                    ]
                );

                $this->success(Translate::_('The influencer has been invited'));
            }
            $this->redirect('/anunciante/buscador');
        } else {
            $this->error(Translate::_('Invalid Opportunity'));
            $this->redirect('/anunciante/buscador');
        }
    }

}