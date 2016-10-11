<?php
namespace Frontend\Controllers\Account\Advertiser;

use Frontend\Controllers\Account\AccountControllerBase;
use Frontend\Module;
use Frontend\Services\Campaign\Pricing;
use Frontend\Services\Search\Elastic;
use Frontend\Services\Search\Filter;
use Frontend\Services\Search\Pagination;
use Frontend\Widgets\Translate;

/**
 * Class SearchController
 *
 * @package Frontend\Controllers\Account\Advertiser
 */
class SearchController extends AccountControllerBase
{

    /**
     * Leverages elastic search
     */
    public function indexAction()
    {
        if (!floor($this->get('account.balance')->getBalance())) {
            $this->warning(
                'Debes de contar con saldo a favor para poder ingresar al buscador <a class="btn blue" style="margin-left:20px" href="/anunciante/finanzas">Depositar Dinero</a>'
            );
            $this->redirect('/anunciante');
        }
        $this->addAssets(
            'css/brandme/advertiser-search.css',
            'js/jquery.maskMoney.min.js',
            'js/brandme/advertiser-search.js',
            'js/bootbox.min.js'
        );
        /** @var \Frontend\Services\Search\Elastic $elastic */
        $elastic = Module::getService('Search\Elastic');
        $get = $this->sanitize($_GET, ['string', 'striptags']);
        $network
            = isset($get['r']) && strlen(trim($get['r']))
            ? $get['r'] = preg_grep("/".$get['r']."/i", ['facebook', 'twitter']) ? strtolower($get['r']) : false
            : false;
        $criteria = isset($get['q']) ? $get['q'] : false;
        //Apply simple search restrictions to prevent people downloading our creators information massively
        if (!$criteria || strlen(trim($criteria)) < 3) {
            if (strlen(trim($criteria)) < 3) {
                $this->warning(Translate::_('Please enter at least 3 characters to search'));
            }
            $this->view->setVars(
                [
                    'hits'      => [],
                    'network'   => $network,
                    'paginator' => false
                ]
            );

            return;
        }
        $verticalFilters = [];
        foreach (array_values(array_intersect(Filter::$filters, array_keys($get))) as $filter) {
            $verticalFilters[$filter] = strtolower($get[$filter]);
        }

        $priceFilters = [];//price indexes are dealt with differently
        if (isset($get['price'])) {
            $priceFilters['price'] = intval(preg_replace("/[^\d\.\,]/", '', $get['price']));
            if (isset($get['price-direction']) && in_array($get['price-direction'], ['gte', 'lte'])) {
                $priceFilters['price-direction'] = $get['price-direction'];
            } else {
                $priceFilters['price-direction'] = 'gte';
            }
        }
        //Note: elastic pages are zero-indexed
        $page = isset($get['p']) ? abs(intval($get['p'])) : 1;
        if ($criteria === false && $network === false) {
            //no explicit query present - as in just opened search URL but hasn't initiated search

            $this->view->setVars(
                [
                    'hits'      => [],
                    'network'   => $network,
                    'paginator' => false
                ]
            );
        } else {
            //Note: the -1 on the $page variable, this helps calculate the pagination offset correctly due to the zero based array
            $hits = $elastic->search($network, $criteria, $verticalFilters, $priceFilters, $page - 1);
            $rawQuery = $this->request->getQuery();
            $filter = Module::getService('Search\Filter')->getHtmlFilters();
            $activeFilters = array_intersect(array_keys($filter), array_keys($rawQuery));
            $this->view->setVars(
                [
                    'hits'          => $hits['hits'],
                    'criteria'      => $criteria,
                    'paginator'     => $hits['total'] > Elastic::ELASTIC_SEARCH_PAGINATION_HITS_PER_PAGE
                        ? Pagination::getPaginator(
                            $page,
                            $hits['total'],
                            Elastic::ELASTIC_SEARCH_PAGINATION_HITS_PER_PAGE
                        ) : false,
                    'network'       => $network,
                    'filter'        => $filter,
                    'activeFilters' => $activeFilters
                ]
            );
        }
    }

    /**
     * Called via ajax from direct offer modal on load
     */
    public function getOpportunitiesAction()
    {
        if (!$this->request->isAjax()) {
            $this->redirect('/anunciante/buscador');
        }
        $post = $this->getPost();
        $idFactorNetwork = isset($post['id']) && is_numeric($post['id']) ? $post['id'] : null;
        $this->view->disable();
        $this->response->setContentType('application/json');
        $this->response->setContent(
            json_encode(Module::getService('Campaign/Opportunity')->getAdvertiserDirectOpportunities($this->get('id'), $idFactorNetwork))
        );

        return $this->response;
    }

    /**
     * Don't confuse the action name, this is just to view the creator social media account
     *
     * @todo move this into a more agnostic location - since this is linked from both search participations
     * @param $referralCode
     * @param $idfactorNetworkAccountId
     */
    public function accountAction($referralCode, $idfactorNetworkAccountId)
    {
        $factorNetworks = Module::getService('Account')
            ->getFactorNetworksByReferralCode($referralCode);
        if (!$factorNetworks) {
            $this->redirect('/anunciante/buscador');
        }
        $this->addAssets(
            'css/brandme/advertiser-search.css',
            'assets/global/plugins/amcharts/amcharts/amcharts.js',
            'assets/global/plugins/amcharts/amcharts/serial.js',
            'assets/global/plugins/amcharts/amcharts/themes/light.js',
            'js/jquery.maskMoney.min.js',
            'js/bootbox.min.js',
            'js/brandme/advertiser-search.js'
        );

        $accounts = [];
        $account = [];
        /**
         * Build an array of accounts ready for view presentation
         */
        foreach ($factorNetworks as $networkAccount) {
            //@todo it's redundant to load all the accounts like this
            if ($idfactorNetworkAccountId != $networkAccount['account_id']) {
                if (!in_array($networkAccount['network'], ['Twitter', 'Facebook'])) {
                    continue;
                }
                $accounts[] = [
                    'network' => $networkAccount['network'],
                    'alias'   => $networkAccount['alias'],
                    'link'    => '/anunciante/creador/perfil/'.$referralCode.'/'.$networkAccount['account_id']
                ];
                continue;
            }
            $networkAccountJson = json_decode($networkAccount['json']);
            $account = [
                'bio'               => $networkAccount['bio'],
                'id_factor_network' => $networkAccount['id_factor_network'],
                'alias'             => $networkAccount['alias'],
                'network'           => $networkAccount['network'],
                'name'              => $networkAccount['name'],
                'photo'             => $networkAccountJson->photoURL,
                'price'             => is_null($networkAccount['price']) ? 0.00 : Pricing::creatorToAdvertiser($networkAccount['price']),
                'url'               => $networkAccountJson->profileURL,
                'tags'              => array_filter(explode(',', $networkAccount['tags'])),
                'followers'         => $networkAccount['followers'],
                'following'         => $networkAccount['following']
            ];
            $segmentation = [];
            foreach (explode(',', $networkAccount['segmentation']) as $segment) {
                $segment = explode(':', $segment);
                if (count($segment) != 2) {
                    continue;
                }
                if (!isset($segmentation[$segment[0]])) {
                    $segmentation[$segment[0]] = [];
                }
                $segmentation[$segment[0]][] = $segment[1];
            }
            $account['segmentation'] = $segmentation;
            //pull in some date from the target network via API
            $account['posts'] = [];
            switch ($networkAccount['network']) {
                case 'Twitter':
                    $adapter = Module::getService('Network')
                        ->getHybridNetworkAdapter(
                            $networkAccount['network'],
                            $networkAccount['oauth_token'],
                            $networkAccount['oauth_token_secret'].'sdsd'
                        );
                    $tweets = $adapter->api()->get('statuses/user_timeline.json', ['count' => 10]);
                    if (property_exists($tweets, 'errors')) {
                        $tweets = [];
                    }
                    foreach (!$tweets ? [] : $tweets as $tweet) {
                        $account['posts'][] = [
                            'message'    => $tweet->text,
                            'created_at' => $tweet->created_at,
                        ];
                    }
                    $userStats = $adapter->api()->get('/account/verify_credentials.json');
                    if (!property_exists($userStats, 'errors')) {
                        $account['followers'] = $userStats->followers_count;
                        $account['following'] = $userStats->friends_count;
                    }
                    $this->addAssets('js/brandme/advertiser-search-twitter-charts.js');
                    break;
                case 'Facebook':
                    $adapter = Module::getService('Network')
                        ->getHybridNetworkAdapter(
                            $networkAccount['network'],
                            $networkAccount['oauth_token'],
                            $networkAccount['oauth_token_secret']
                        );
                    try {
                        $posts = $adapter->api()->api('/me/posts');
                        foreach ($posts['data'] as $post) {
                            $data = [
                                'message'    => $post['message'],
                                'created_at' => date('d/m/Y h:i A', strtotime($post['created_time'])),
                            ];
                            if ($post['picture']) {
                                $data['picture'] = $post['picture'];
                            }
                            $account['posts'][] = $data;
                        }
                    } catch (\FacebookApiException $e) {
                    }
                    $this->addAssets('js/brandme/advertiser-search-facebook-charts.js');
                    break;
            }

        }
        //even if other accounts are found using the referral code, if none match with the account id then redirect the advertiser with an error message
        if(!$account){
            $this->error(Translate::_('The specified network account no longer exists'));
            $this->redirect('/anunciante');
        }
        $this->view->setVars(
            [
                'account'  => $account,
                'accounts' => $accounts
            ]
        );


    }

    /**
     * Returns json data for statistics graph
     *
     * @param $referralCode
     * @param $idfactorNetwork
     * @return \Phalcon\Http\Response|\Phalcon\HTTP\ResponseInterface
     */
    public function accountDataAction($referralCode, $idfactorNetworkAccountId)
    {
        //dummy data for now
        $data = [];
        for ($i = 31; $i >= 1; $i--) {
            $data[] = ['date' => date('Y-m-d', strtotime('NOW -'.$i.' DAY')), 'value' => 0];
        }
        $this->response->setContent(
            json_encode(
                $data
            )
        );

        return $this->response;
    }
}