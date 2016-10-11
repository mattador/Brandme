<?php
namespace Frontend\Controllers;

use Entities\FactorNetwork;
use Frontend\Module;
use Frontend\Widgets\Translate;
use Hybrid\Endpoint;
use Phalcon\Logger;
use Phalcon\Mvc\View;

/**
 * Since Hybrid Auth uses it's own session management we use bare-bones sessions here
 * Class HybridController
 *
 * @package Frontend\Controllers
 */
class HybridController extends ControllerBase
{

    protected $isRegistering;
    protected $isLoggedIn;

    public function initialize()
    {
        $this->session->start(); //begins session
        $this->view->setRenderLevel(View::LEVEL_NO_RENDER);
        $this->isLoggedIn = $this->isLogged();
        $this->isRegistering = isset($_SESSION['registration']) ? true : false;
    }

    /**
     * Processes Hybrid network callback
     */
    public function callbackAction()
    {
        if (isset($_GET['denied']) || (count($_REQUEST) == 1 && isset($_REQUEST['_url']))) {
            $this->redirect();
        }
        Endpoint::process();
    }

    public function redirect($destination = '/')
    {
        if ($this->isRegistering) {
            parent::redirect('/registro');
        } elseif ($this->isLoggedIn) {
            parent::redirect('/creador/redes-sociales');
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Links accounts - We can count on only valid networks getting passed thanks to regex routing rule
     *
     * @param $network
     */
    public function linkAction($network)
    {
        $networkName = ucfirst(strtolower($network));
        $idNetwork = \Entities\Network::findFirst('name = "' . $networkName . '"')->getId();
        try {
            /** @var \Hybrid\Auth $hybrid */
            $hybrid = $this->di->get('hybrid');
            /**
             * Clean up to avoid conflicts
             */
            if (isset($_SESSION['registration']['connected'][$network])) {
                unset($_SESSION['registration']['connected'][$network]);
                if ($hybrid->isConnectedWith($networkName)) {
                    $hybrid->getAdapter($networkName)->logout();
                }
            }
            /** @var \Hybrid\Provider\Adapter $networkAdapter */
            $networkAdapter = $hybrid->getAdapter($network);
            if ($networkAdapter->isUserConnected()) {
                $accessToken = $networkAdapter->getAccessToken();
                $profile = $networkAdapter->getUserProfile();//manage 401
                if (FactorNetwork::count('account_id = "' . $profile->identifier . '" AND id_network = ' . $idNetwork)
                    > 0
                ) {
                    /** @todo parse dynamic phrases in view helper _() function */
                    $this->error(
                        Translate::_(
                            'La cuenta "' . $profile->displayName . '" de ' . ucfirst($network)
                            . ' ya esta asociado con Brandme.'
                        )
                    );
                    $hybrid->getAdapter($networkName)->logout();
                    $this->redirect();
                }
                $data = [
                    'oauth_token'        => $accessToken['access_token'],
                    'oauth_token_secret' => $accessToken['access_token_secret'],
                    'account_id'         => $profile->identifier,
                    'account_alias'      => $profile->displayName,
                    'meta_json'          => $profile
                ];

                switch ($network) {
                    case 'twitter':
                        $userStats = $networkAdapter->api()->get('/account/verify_credentials.json');
                        $data['statistics_followers'] = $userStats->followers_count;
                        $data['statistics_following'] = $userStats->friends_count;
                        $data['statistics_status_updates'] = $userStats->statuses_count;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'facebook':
                        //Facebook doesn't allow us to get the number of friends
                        $data['statistics_followers'] = 0;
                        $data['statistics_following'] = 0;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'google':
                        //people who have circled?
                        $data['statistics_followers'] = $networkAdapter->api()->api(
                            'https://www.googleapis.com/plus/v1/people/me?fields=circledByCount'
                        )->circledByCount;
                        //friends
                        $data['statistics_following'] = 0;
                        //$networkAdapter->api()->api('https://www.googleapis.com/plusDomains/v1/people/me/circles')->items[0]->people->totalItems;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'linkedin':
                        //In order to access this information we will need to apply here:
                        //https://help.linkedin.com/app/ask/path/api-dvr
                        $data['statistics_followers'] = 0;
                        $data['statistics_following'] = 0;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'foursquare':
                        $data['statistics_followers'] = 0;
                        $data['statistics_following'] = 0;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'tumblr':
                        $data['statistics_followers'] = 0;
                        $data['statistics_following'] = 0;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'instagram':
                        $data['statistics_followers'] = $profile->followed_by;
                        $data['statistics_following'] = $profile->follows;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                    case 'youtube':
                        $data['statistics_followers'] = 0;
                        $data['statistics_following'] = 0;
                        $data['statistics_status_updates'] = 0;
                        $data['statistics_lists'] = 0;
                        break;
                }
                if ($this->isRegistering) {
                    $_SESSION['registration']['connected'][$network] = $data;
                    $this->redirect('/registro');
                } elseif ($this->isLoggedIn) {
                    $_SESSION['connected'][$network] = $data;
                }
                $this->redirect();
            } else {
                $hybrid->authenticate($networkName);
            }
        } catch (\FacebookApiException $e) {
            $this->error(Translate::_('An error has occurred linking your account'));
        } catch (\HybridException $e) {
            $this->error(Translate::_('An error has occurred linking your account'));
        } catch (\Exception $e) {
            $this->error(Translate::_('An error has occurred linking your account'));
        }
        $this->redirect();
    }

    /**
     * This is used in registration when user wishes to unlink account
     *
     * @param $network
     */
    public function unlinkAction($network)
    {
        $networkName = ucfirst(strtolower($network));
        if (!isset($_SESSION['registration']['connected'][$network])) {
            $this->redirect('/registro');
        }
        $hybrid = $this->di->get('hybrid');
        if ($hybrid->isConnectedWith($networkName)) {
            $hybrid->getAdapter($networkName)->logout();
        }
        unset($_SESSION['registration']['connected'][$network]);
        $this->warning(Translate::_('You have disconnected your %network% account', ['network' => $networkName]));
        $this->redirect('/registro');
    }

    /**
     * Processes Hybrid network callback
     */
    public function dispatchAction()
    {
        //Request authenticity of origin must be verified
        if ($_SERVER['HTTP_TOKEN'] != $this->getDI()->get('token')) {
            echo 'Unauthenticated request';
            http_response_code(401);
            exit;
        }
        if ($this->request->isPost()) {
            $post = $this->getPost();
            if (!isset($post['id_factor_network']) || !isset($post['id_participation']) || !isset($post['content'])
                || !is_string(
                    $post['content']
                )
            ) {
                echo 'Invalid request format';
                http_response_code(500);
                exit;
            }
            /** @var \Hybrid\Provider\Adapter $networkAdaptor */
            $networkAdaptor = Module::getService('Hybrid')->getSocialAdapter($post['id_factor_network']);
            if (!$networkAdaptor->isUserConnected()) {
                //unlink network
                FactorNetwork::findFirst('id = ' . $post['id_factor_network'])
                    ->setStatus('unlinked')
                    ->update();
                echo 'Unauthorized network account';
                http_response_code(500);
                exit;
            }
            try {
                $networkAdaptor->setUserStatus($post['content']);
                http_response_code(200);
            } catch (\FacebookApiException $e) {
                echo $e->getMessage();
                http_response_code(500);
            } catch (\HybridException $e) {
                echo $e->getMessage();
                http_response_code(500);
            } catch (\Exception $e) {
                echo $e->getMessage();
                http_response_code(500);
            }
            exit;
        }
        http_response_code(404);
        exit;
    }

}