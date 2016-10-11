<?php
namespace Frontend\Controllers\Account\Creator;

use Entities\FactorNetwork;
use Entities\FactorNetworkMeta;
use Entities\Network;
use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Account;
use Frontend\Services\Social;
use Frontend\Widgets\Translate;
use Phalcon\Validation;

/**
 * Creator controller, to administer social network accounts with Brandme
 * Class NetworkController
 *
 * @package     Frontend\Controllers
 * @todo        implement correct ACL
 */
class NetworkController extends AccountControllerBase
{

    public function indexAction()
    {
        $service = new Account();
        $networks = $this->get('networks');
        if (!$networks) {
            //There are no connected networks - this shouldn't really ever happen
            $this->redirect('/creador');
        }
        if ($this->request->isPost()) {
            if (!$this->security->checkToken()) {
                $this->redirect('/creador/redes-sociales');
            }
            $post = $this->getPost();
            $service = new Account();
            $messages = [];

            //Verify creator ownership of target network account
            if (!$networks) {
                //User is trying to update a network which does not exist!
                $this->destroy();
            }
            $idsFactorNetwork = [];
            foreach ($networks as $network => $accounts) {
                foreach ($accounts as $i => $account) {
                    $idsFactorNetwork[] = $account['network']['id'];
                }
            }
            if (!isset($post['id_network']) || !in_array($post['id_network'], $idsFactorNetwork)) {
                $this->redirect('/creador/redes-sociales');
            }

            //now let's process the post values one by one
            if (isset($post['tags'])) {
                if (strlen(trim($post['tags'])) && !preg_match('/^[a-zA-Z\-\,\s]+$/', $post['tags'])) {
                    $messages[] = 'Valid tags contain only letters and dashes, and are separated by commas';
                } else {
                    $service->setTags($post['id_network'], $post['tags']);
                }
            }
            if (isset($post['price'])) {
                $price = preg_replace('/[^\d\.]/', '', $post['price']);
                if (intval($price) < 1) {
                    //$messages[] = 'The minimum negotiating amount is $1 USD';
                    $this->warning(
                        $this->_(
                            'Setting a base price for negotiating is optional, but useful for when Advertisers want to contact you directly. If you want to set a base price it must be greater than $1.00 USD'
                        )
                    );
                } elseif (intval($price) > 10000) {
                    $messages[] = 'For negotiating amounts over $1000 USD please contact the BrandMe team';

                } else {
                    $service->setNegotiatingPrice($post['id_network'], $price);
                }
            }
            //now process verticals
            foreach (Account::$segments as $segment) {
                if (isset($post[$segment])) {
                    if (count($post[$segment]) > 5) {
                        $messages[] = 'You may only choose up to 5 '.$segment;
                    } else {
                        $service->setSegmentation($post['id_network'], [$segment => $post[$segment]]);
                    }
                } else {
                    //No options are set for the current segment, therefore we need to delete any existing options of the current segment which were set beforehand
                    $service->unsetSegmentation($post['id_network'], $segment);
                }
            }
            $this->view->setVar('messages', array_unique($messages));
            if (empty($messages)) {
                $this->success(Translate::_('Networks updated successfully'));
            }
            $this->refreshSession();
        } elseif (isset($_SESSION['connected'])) {
            //add new networks
            foreach ($_SESSION['connected'] as $networkName => $account) {
                if (isset($_SESSION['connected']) && !empty($_SESSION['connected'])) {
                    $factorNetworkCollection = [];
                    foreach ($_SESSION['connected'] as $networkName => $account) {
                        $factorNetwork = new FactorNetwork();
                        $factorNetwork->setIdFactor($this->get('id'))
                            ->setOauthToken($account['oauth_token'])
                            ->setOauthTokenSecret($account['oauth_token_secret'])
                            ->setAccountAlias($account['account_alias'])
                            ->setAccountId($account['account_id'])
                            ->setIdNetwork(Network::findFirst('name = "'.ucfirst($networkName).'"')->getId())
                            ->setStatus('linked')
                            ->setCreatedAt(date('Y-m-d H:i:s'));
                        $factorNetworkMeta = new FactorNetworkMeta();
                        $factorNetworkMeta->setJson(json_encode($account['meta_json']))
                            ->setStatisticsFollowers($account['statistics_followers'])
                            ->setStatisticsFollowing($account['statistics_following'])
                            ->setStatisticsStatusUpdates($account['statistics_status_updates'])
                            ->setStatisticsLists($account['statistics_lists'])
                            ->setCreatedAt(date('Y-m-d H:i:s'));
                        $factorNetwork->FactorNetworkMeta = $factorNetworkMeta;
                        $factorNetwork->create();
                    }
                }
                //copy segmentation from brandme to network
                $service->copyDefaultSegmentationIntoEmptyNetworks($this->get('id'));
            }
            $this->success(Translate::_('Network added Successfully'));
            $this->refreshSession();
        }
        //finally unset the session index
        unset($_SESSION['connected']);
        //log out of networks
        /** @var \Hybrid\Auth $hybrid */
        $hybrid = $this->di->get('hybrid');
        $hybrid->logoutAllProviders();

        $networks = $this->get('networks');
        if ($networks) {
            foreach ($networks as $network => $accounts) {
                foreach ($accounts as $i => $account) {
                    //get verticals
                    $segmentation = $service->getSegmentation($account['network']['id']);
                    $account['segmentation'] = $segmentation;
                    $account['meta']['negotiating_price'] = number_format($account['meta']['negotiating_price'], 2);
                    //replace verticals with full map of verticals
                    $networks[$network][$i] = $account;
                }
            }
        }
        //merge all active networks with users accounts
        $activeNetworks = [];
        foreach (Network::find('status = "active" AND name !="Brandme"') as $activeNetwork) {
            $activeNetworks[$activeNetwork->getName()] = [];
        }
        //$activeNetworks['Instagram'] = [];
        $this->view->setVar('networks', array_merge($activeNetworks, $networks));
        $this->addAssets('css/brandme/creator-networks.css', 'js/jquery.maskMoney.min.js', 'js/brandme/creator-network.js');
    }

    /**
     * Reconnect network
     */
    public function reconnectNetworkAction()
    {
        /** @var \Hybrid\Auth $hybrid */
        $hybrid = $this->di->get('hybrid');
        if (!$this->getPost()) {
            //Process callback
            if (!isset($_SESSION['reconnect'])) {
                $this->redirect('/creador/redes-sociales');
            }
            $networkAdapter = $hybrid->getAdapter($_SESSION['reconnect']['provider']);
            /** @var FactorNetwork $factorNetwork */
            $factorNetwork = FactorNetwork::findFirst('id = '.$_SESSION['reconnect']['idFactorNetwork']);
            if ($networkAdapter->isUserConnected() && $factorNetwork->getAccountId() == $networkAdapter->getUserProfile()->identifier) {
                $accessToken = $networkAdapter->getAccessToken();
                $userProfile = $networkAdapter->getUserProfile();
                $factorNetwork
                    ->setAccountAlias($userProfile->displayName)
                    ->setOauthToken($accessToken['access_token'])
                    ->setOauthTokenSecret($accessToken['access_token_secret'])
                    ->setStatus('linked')
                    ->update();
                $factorNetwork->FactorNetworkMeta
                    ->setJson(json_encode($userProfile))
                    ->update();
                //reindex network
                $elastic = Module::getService('Search\Elastic');
                $elastic->upsert($factorNetwork->getId());
                $this->success(Translate::_('Account reconnected successfully'));
            } else {
                $this->error(
                    Translate::_('The network account you authenticated with is not the same as the one you are trying to relink')
                );
            }
            unset($_SESSION['reconnect']);
            $this->refreshSession('/creador/redes-sociales');
        }
        $post = $this->getPost();
        if (!isset($post['refresh'])) {
            $this->redirect('/creador/redes-sociales');
        }
        //double check ownership and linkage status
        /** @var FactorNetwork $factorNetwork */
        $factorNetwork = FactorNetwork::findFirst(
            'id = '.$post['refresh'].' AND id_factor = '.$this->get('id').' AND status = "unlinked"'
        );
        if (!$factorNetwork) {
            $this->error(Translate::_('The network account cannot be linked right now, please try again later'));
            $this->redirect('/creador/redes-sociales');
        }
        //relink
        if (Module::getService('Hybrid')->isSocialAccountConnectionAlive($factorNetwork->getId())) {
            $factorNetwork->setStatus('linked')->update();
            $this->success(Translate::_('The network account is already linked'));
            $this->refreshSession('/creador/redes-sociales'); //automatically redirects
        }
        //anticipate for callback
        $provider = strtolower($factorNetwork->Network->getName());
        $_SESSION['reconnect'] = [
            'provider'        => $provider,
            'idFactorNetwork' => $factorNetwork->getId()
        ];
        $hybrid->getAdapter($provider)->logout();
        $hybrid->authenticate($provider);
    }
}