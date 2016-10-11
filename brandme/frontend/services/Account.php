<?php

namespace Frontend\Services;

use Common\Services\Sql;
use Entities\Factor;
use Entities\FactorNetwork;
use Entities\FactorNetworkSegmentation;
use Entities\Plan;
use Entities\VerticalSlice;
use Frontend\Module;
use Frontend\Services\Campaign\Pricing;
use Phalcon\Exception;

/**
 * Class Account
 *
 * @package Frontend\Services
 */
class Account extends AbstractService
{

    /**
     * Social network segments
     *
     * @var array
     */
    public static $segments
        = [
            'interests',
            'language',
            'age',
            'education',
            'gender',
            'income',
            'family',
            'civil_state',
            'location_state',
            'statistics_following',
            'statistics_lists',
            'statistics_status_updates'
        ];

    /**
     * Factor entity alias shortcuts
     *
     * @var array
     */
    public static $alias
        = array(
            'auth'                => 'FactorAuth',
            'balance'             => 'FactorBalance',
            'fiscal'              => 'FactorFiscal',
            'meta'                => 'FactorMeta',
            'network'             => 'FactorNetwork',
            'networkMeta'         => 'FactorNetworkMeta',
            'networkOpportunity'  => 'CampaignOpportunityParticipation',
            'networkSegmentation' => 'FactorNetworkSegmentation',
            'reference'           => 'FactorReference',
            'region'              => 'FactorRegion',
            'session'             => 'FactorSession',
            'transaction'         => 'FactorTransaction'
        );

    public static $filter
        = [
            'all'    => 'Para todos',
            'adult'  => 'Adulto',
            'mature' => 'Maduro'
        ];

    /**
     * Returns an array of countries (in Spanish)
     *
     * @return array
     */
    public static function getCountries()
    {
        $countries = [];
        $sql = 'SELECT name FROM segmentation WHERE segment = "location_country" ORDER BY SUBSTRING(name, 1, 1) ASC';
        foreach (Sql::find($sql) as $country) {
            $countries[] = $country['name'];
        }

        return $countries;
    }

    /**
     * Loads user's session with data
     *
     * @param $session
     * @throws Exception
     */
    public function initSession($session)
    {
        $id = $session->get('id');
        $service = new Account();
        $account = $service->getAccount($id);
        $session->set('account', $account);
        $role = $account->getFactorAuth()->getRole()->getName();
        $session->set('role', $role);
        /** @var \Entities\FactorMeta $meta */
        $meta = $account->getFactorMeta();
        $session->set('firstname', $meta->getFirstName());
        $session->set('lastname', $meta->getLastName());
        $session->set('balance', number_format($account->getFactorBalance()->getBalance(), 2, '.', ','));
        $session->set('banner', $meta->getBanner());
        $session->set('avatar', $meta->getAvatar());
        //role specific session data
        switch ($role) {
            case 'creator':
                //getNetwork
                $session->set('networks', $this->getNetworks($account));
                //get root segments
                $query
                    = "SELECT COUNT(*) cnt FROM Entities\Segmentation s
                JOIN Entities\FactorNetworkSegmentation fns ON fns.id_segmentation = s.id
                JOIN Entities\FactorNetwork fn ON fn.id = fns.id_factor_network
                WHERE fn.id_factor = {$id} AND fn.id_network = 1"; //1 = Brandme
                /** @var \Phalcon\Mvc\Model\Resultset\Simple $result */
                $rootSegments = $this->getManager()->executeQuery($query);
                $session->set('rootSegments', $rootSegments->getFirst()->cnt > 0 ? true : false);
                break;
        }
    }

    /**
     * Retrieves factor entity by id
     *
     * @param $idFactor
     * @return \Phalcon\Mvc\Model
     * @throws Exception
     */
    public function getAccount($idFactor)
    {
        $factor = Factor::findFirst('id = '.$idFactor);
        if (!$factor) {
            throw new Exception('Factor not found by id '.$idFactor);
        }

        return $factor;
    }

    /**
     * Returns a user's connected networks, technically this should only be of use for users of role "creator"
     *
     * @param Factor $account
     * @return array|bool
     * @throws Exception
     */
    public function getNetworks(Factor $account)
    {
        $connected = [];
        $networks = $account->getFactorNetwork('id_network != 1'); //ignore brandme's psuedo network
        if (!$networks->count()) {
            return false;
        }
        /** @var FactorNetwork $n */
        foreach ($networks as $network) {
            if (!isset($connected[$network->getNetwork()->getName()])) {
                $connected[$network->getNetwork()->getName()] = [];
            }

            //get network specific vertical
            //@todo optimize this query, won't be fast with 100,000 users
            $networkSegment = [];
            foreach ($network->getFactorNetworkSegmentation() as $segment) {
                $segment = $segment->getSegmentation();
                if (!isset($networkSegment[$segment->getSegment()])) {
                    $networkSegment[$segment->getSegment()] = [];
                }
                $networkSegment[$segment->getSegment()][] = $segment->getName();
            }
            $networkMeta = [];
            $networkMeta = $network->getFactorNetworkMeta()->toArray();
            $networkMeta['json'] = (array)json_decode($networkMeta['json']);
            $connected[$network->getNetwork()->getName()][] = [
                'network'   => $network->toArray(),
                'is_linked' => $network->getStatus() == 'linked' ? true : false,
                'segment'   => $networkSegment,
                'meta'      => $networkMeta
            ];
        }

        return $connected;
    }

    /**
     * Set the negotiation price
     *
     * @param $idFactorNetwork
     * @param $price
     */
    public function setNegotiatingPrice($idFactorNetwork, $price)
    {
        $price = !strlen(trim($price)) ? null : $price;
        $query
            = "UPDATE Entities\FactorNetworkMeta SET negotiating_price = '{$price}' WHERE id_factor_network = $idFactorNetwork LIMIT 1";
        $this->getManager()->executeQuery($query);
        $elastic = Module::getService('Search\Elastic');
        $elastic->upsert($idFactorNetwork);
    }

    /**
     * Set's tags to a factor connected social network
     *
     * @param $idFactorNetwork
     * @param $tags
     */
    public function setTags($idFactorNetwork, $tags)
    {
        $tags = explode(',', trim($tags));
        array_filter(
            $tags,
            function (&$e) {
                $trim = explode(' ', $e);
                $trimmed = array_filter($trim, 'strlen');
                $e = implode(' ', $trimmed);
            }
        );
        $tags = array_filter($tags, 'strlen'); // remove empty values
        $tags = implode(',', $tags);
        //Don't check for string length, because maybe creator wishes to remove tags
        $query = "UPDATE Entities\FactorNetworkMeta SET tags = '$tags' WHERE id_factor_network = $idFactorNetwork";
        $this->getManager()->executeQuery($query);
        $elastic = Module::getService('Search\Elastic');
        $elastic->upsert($idFactorNetwork);
    }

    /**
     * Clean a FactorNetwork's related FactorNetworkSegmentation records for a specific segment
     *
     * @param $idFactorNetwork
     * @param $segment
     */
    public function unsetSegmentation($idFactorNetwork, $segment)
    {
        $result = Sql::find("SELECT GROUP_CONCAT(s.id) segment FROM segmentation s WHERE s.segment = '$segment'");
        $sql
            = "DELETE FROM Entities\FactorNetworkSegmentation WHERE id_factor_network = $idFactorNetwork AND id_segmentation IN ({$result[0]['segment']})";
        $this->getManager()->executeQuery($sql);
    }

    /**
     * Updates a collection of vertical values by first deleting exiting records, then inserting new ones
     *
     * @param $idFactorNetwork
     * @param $segmentation
     */
    public function setSegmentation($idFactorNetwork, $segmentation)
    {
        foreach ($segmentation as $segment => $segments) {
            if (!in_array($segment, self::$segments)) {
                continue;
            }
            //clean
            $this->unsetSegmentation($idFactorNetwork, $segment);
            //reinsert
            foreach ($segments as $id) {
                $query
                    = "INSERT INTO Entities\FactorNetworkSegmentation (id_segmentation, id_factor_network, created_at)
            VALUES ($id, $idFactorNetwork, '".date('Y-m-d H:i:s')."')";
                $this->getManager()->executeQuery($query);
            }

        }
        $elastic = Module::getService('Search\Elastic');
        $elastic->upsert($idFactorNetwork);
    }

    /**
     * Uses an idFactorNetwork to return segments chosen, matching them against segments available
     *
     * @param       $idFactorNetwork
     * @param array $criteria
     * @return array
     */
    public function getSegmentation($idFactorNetwork, $criteria = [])
    {
        if (empty($criteria)) {
            $criteria = self::$segments;
        }
        //get social specific vertical values
        $query
            = 'SELECT s.id, s.name, s.segment,
                  (SELECT fns.id FROM factor_network_segmentation fns WHERE fns.id_segmentation = s.id AND fns.id_factor_network = '
            .$idFactorNetwork.') id_factor_network_segmentation
                  FROM segmentation s
                  WHERE s.segment IN ("'.implode('","', $criteria).'")';
        $result = Sql::find($query);
        $segments = [];
        foreach ($result as $segment) {
            if (!isset($segments[$segment['segment']])) {
                $segments[$segment['segment']] = [];
            }
            $segments[$segment['segment']][] = [
                'name'    => $segment['name'],
                'id'      => $segment['id'],
                'checked' => !is_null($segment['id_factor_network_segmentation'])
            ];
        }

        return $segments;
    }

    /**
     * Copies Brandme network segmentation into all empty networks or specified network
     *
     * @param      $idFactor
     * @param bool $idFactorNetwork
     * @return bool
     */
    public function copyDefaultSegmentationIntoEmptyNetworks($idFactor, $idFactorNetwork = false)
    {
        $idBrandme = FactorNetwork::findFirst('id_network = 1 AND id_factor = '.$idFactor)->getId();
        if (!$idBrandme) {
            return false;
        }
        if ($idFactorNetwork == $idBrandme) {
            return false;
        }
        $idFactorNetworks = [];
        if ($idFactorNetwork) {
            $idFactorNetworks[] = $idFactorNetwork;
        }
        foreach (FactorNetwork::find('id_network != 1 AND id_factor ='.$idFactor) as $factorNetwork) {
            $idFactorNetworks[] = $factorNetwork->getId();
        }
        if (empty($idFactorNetworks)) {
            return false;
        }
        $phql
            = 'SELECT s.id FROM Entities\Segmentation s
                JOIN Entities\FactorNetworkSegmentation fns ON fns.id_segmentation = s.id
                WHERE fns.id_factor_network = '.$idBrandme;
        $segments = $this->getManager()->executeQuery($phql);
        if (!$segments->count()) {
            //brandme is not initialized either, so no point in continuing
            return false;
        }
        foreach ($idFactorNetworks as $idFnetwork) {
            //don't override factor network segmentation if not explicitly set in method arguments
            if (!$idFactorNetwork && FactorNetworkSegmentation::count('id_factor_network = '.$idFnetwork) > 0) {
                continue;
            }
            $phql = 'DELETE FROM Entities\FactorNetworkSegmentation WHERE id_factor_network = '.$idFnetwork;
            $this->getManager()->executeQuery($phql);

            foreach ($segments as $idSegment) {
                $query
                    = "INSERT INTO Entities\FactorNetworkSegmentation (id_segmentation, id_factor_network, created_at)
            VALUES ($idSegment->id, $idFnetwork, '".date('Y-m-d H:i:s')."')";
                $this->getManager()->executeQuery($query);
            }
            $elastic = Module::getService('Search\Elastic');
            $elastic->upsert($idFnetwork);
        }
    }

    /**
     * Returns Factor's linked network ids for a social network
     *
     * @param $idFactor
     * @param $network
     * @return array|bool
     */
    public function getIdFactorNetwork($idFactor, $network)
    {
        $query
            = "SELECT fn.id FROM Entities\FactorNetwork fn
                    JOIN Entities\Network n ON n.id = fn.id_network
                    WHERE n.name = '$network'
                    AND fn.id_factor = $idFactor AND fn.status = 'linked'";
        $result = $this->getManager()->executeQuery($query);
        if ($result->count() == 0) {
            return false;
        } else {
            $fsIds = [];
            foreach ($result as $fs) {
                $fsIds[] = $fs->id;
            }

            return $fsIds;
        }
    }

    /**
     * Returns current contracted plan
     *
     * @param $idFactor
     * @return mixed
     */
    public function getPlanByFactorId($idFactor)
    {
        $query
            = "SELECT p.name as plan FROM Entities\Plan p
        JOIN Entities\FactorMeta fm ON fm.id_plan = p.id
        WHERE fm.id_factor = ".$idFactor;
        $result = $this->getManager()->executeQuery($query);

        return $result->getFirst()->plan;

    }

    /**
     * Updates a plan
     *
     * @param        $idFactor
     * @param string $plan
     */
    public function setPlanByFactorId($idFactor, $plan = 'free')
    {
        $plan = Plan::findFirst('name = "'.$plan.'"');
        $idPlan = (int)$plan->getId();
        $query = 'UPDATE Entities\FactorMeta SET id_plan = '.$idPlan.' WHERE id_factor = '.$idFactor;
        $this->getManager()->executeQuery($query);
    }

    /**
     * Retrieve Factor Network by Id and LEFT link it to an opportunity
     *
     * @param $idFactorNetwork
     * @param $idOpportunity
     * @return mixed
     */
    public function getFactorNetworkByOpportunityRelation($idFactorNetwork, $idOpportunity)
    {
        $sql
            = '
            SELECT
                fn.id_factor,
                f.email,
                fn.id id_factor_network,
                n.name network,
                fnm.negotiating_price price,
                fm.first_name,
                fm.referral_code,
                fm.timezone,
                fnm.json,
                cop.id id_opportunity
            FROM factor f
                INNER JOIN factor_network fn ON fn.id_factor = f.id
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN factor_meta fm ON fm.id_factor = fn.id_factor
                INNER JOIN network n ON n.id = fn.id_network
                LEFT JOIN campaign_opportunity_participation cop ON cop.id_factor_network = fn.id AND cop.id = '.$idOpportunity.'
            WHERE
                n.name != "'.Network::NETWORK_BRANDME.'" AND
                n.status = "active" AND
                fn.id = '.$idFactorNetwork;
        $factorNetwork = Sql::find($sql);
        if (empty($factorNetwork)) {
            return false;
        }

        return $factorNetwork[0];
    }

    /**
     * Prepares a standard index structure for Elastic Search
     *
     * @param $idFactorNetwork
     * @return bool
     */
    public function getFactorNetworkForIndexation($idFactorNetwork)
    {
        //Use an inner join on segmentation so that only segmented accounts get indexed
        $sql
            = '
            SELECT
                fm.id_factor,
                n.name network,
                fm.bio bio,
                fn.id id_factor_network,
                fn.account_alias alias,
                fnm.negotiating_price price,
                fnm.statistics_followers followers,
                fnm.tags tags,
                fnm.json json,
                CONCAT(fm.referral_code,"/",fn.account_id) link,
                CONCAT(fm.first_name," ",fm.last_name) `name`,
                GROUP_CONCAT(s.segment,":",s.name) segmentation,
                fr.country
            FROM factor f
                INNER JOIN factor_meta fm ON fm.id_factor = f.id
                INNER JOIN factor_network fn ON fn.id_factor = f.id
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN factor_network_segmentation fns ON fns.id_factor_network = fn.id
                INNER JOIN segmentation s ON s.id = fns.id_segmentation
                INNER JOIN factor_region fr ON fr.id_factor = f.id
            WHERE '.
            //fn.status = "linked" AND
            'n.name != "'.Network::NETWORK_BRANDME.'" AND
                n.status = "active" AND
                fn.id = '.$idFactorNetwork;
        $index = Sql::find($sql)[0];
        //Find can return an array with empty indexes, so we still need to validate a field "at random" to see if there is actually information in the result set
        if (is_null($index['alias'])) {
            return false;
        }
        /**
         * We also mix in segmentation (excluding interests) from the brandme profile - even though it's technically not a part of this account,
         * we want to make as many creators available to a search as possible
         */
        $sql
            = '
            SELECT
                GROUP_CONCAT(s.segment,":",s.name) segmentation
            FROM factor f
                INNER JOIN factor_meta fm ON fm.id_factor = f.id
                INNER JOIN factor_network fn ON fn.id_factor = f.id
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                INNER JOIN factor_network_segmentation fns ON fns.id_factor_network = fn.id
                INNER JOIN segmentation s ON s.id = fns.id_segmentation
            WHERE
                n.name = "'.Network::NETWORK_BRANDME.'" AND
                s.segment != "interests" AND
                f.id = '.$index['id_factor'];
        $creatorsBrandmeAccount = Sql::find($sql)[0];
        $index['name'] = trim($index['name']);
        //since the search engine only exists for the advertiser we need to apply the pricing model markups
        $index['price'] = $index['price'] * Pricing::STANDARD_MARKUP;
        $index['alias'] = trim($index['alias']);
        $index['photo'] = json_decode($index['json'])->photoURL;
        if (!is_null($creatorsBrandmeAccount['segmentation'])) {
            $index['segmentation'] .= ','.$creatorsBrandmeAccount['segmentation'];
        }
        //now prepare segmentation
        $segmentation = [
            'age'         => [],
            'gender'      => [],
            'education'   => [],
            'income'      => [],
            'interests'   => [],
            'language'    => [],
            'family'      => [],
            'civil_state' => []
        ];
        foreach (explode(',', $index['segmentation']) as $segment) {
            $segment = explode(':', $segment);
            if (isset($segmentation[$segment[0]])) {
                $segmentation[$segment[0]][] = $segment[1];
            }
        }
        foreach ($segmentation as $k => $v) {
            $index['segmentation_'.$k] = implode(', ', $v);
        }
        unset($index['segmentation']);
        unset($index['id_factor']);

        return $index;
    }

    /**
     * Used in advertiser search engine
     *
     * @param $referralCode
     * @param $accountId
     * @return array
     */
    public function getFactorNetworksByReferralCode($referralCode)
    {
        $sql
            = 'SELECT
                fm.id_factor,
                fm.bio,
                n.name network,
                fn.id id_factor_network,
                fn.id_network,
                fn.account_id account_id,
                fn.account_alias alias,
                fn.oauth_token,
                fn.oauth_token_secret,
                fnm.negotiating_price price,
                fnm.statistics_followers followers,
                fnm.statistics_following following,
                fnm.tags tags,
                fnm.json json,
                CONCAT(fm.referral_code,"/",fn.account_id) link,
                CONCAT(fm.first_name," ",fm.last_name) `name`,
                GROUP_CONCAT(s.segment,":",s.name) segmentation
            FROM factor f
                INNER JOIN factor_meta fm ON fm.id_factor = f.id
                INNER JOIN factor_network fn ON fn.id_factor = f.id
                INNER JOIN network n ON n.id = fn.id_network
                INNER JOIN factor_network_meta fnm ON fnm.id_factor_network = fn.id
                LEFT JOIN factor_network_segmentation fns ON fns.id_factor_network = fn.id
                LEFT JOIN segmentation s ON s.id = fns.id_segmentation
            WHERE
                n.can_participate = 1 AND
                n.status = "active" AND
                fm.referral_code = "'.$referralCode.'" AND
                n.id > 1
            GROUP BY fn.id';
        //fn.status = "linked" AND
        $result = Sql::find($sql);
        if (is_null($result[0]['id_factor_network'])) {
            /**
             * @todo
             * Note that direct queries always return at least one result set even if there, are no actual results.
             * In this case it will return a result set of one record, with all columns as null values.
             * This will be rectified soon.
             */
            return [];
        }

        return $result;
    }

}