<?php
namespace Frontend\Services\Search;

use Elasticsearch\Client;
use Frontend\Services\AbstractService;
use Frontend\Services\Account;

require APPLICATION_VENDOR_DIR . '/autoload.php';

/**
 * Class Elastic
 * We need to take care when there is nothing in the index, search or trying to delete on "nothing" will throws an exception
 * caught outside of Phalcon: @see index.php
 *
 * @see     http://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_dealing_with_json_arrays_and_objects_in_php.html
 * @see     http://www.elastic.co/guide/en/elasticsearch/reference/1.5/query-dsl-filters.html
 * @package Frontend\Services\Search
 */
class Elastic extends AbstractService
{

    const ELASTIC_SEARCH_PAGINATION_HITS_PER_PAGE = 20;
    const ELASTIC_SEARCH_INDEX                    = 'creators'; //the index get's prefixed with the environment
    const ELASTIC_SEARCH_TYPE                     = 'accounts';

    /** @var  \Elasticsearch\Client $client */
    protected $client;

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client(['hosts' => [$this->getConfig('elastic')->get(APPLICATION_ENV)]]);
        }

        return $this->client;
    }

    /**
     * Index a social network account
     *
     * @param $idFactorNetwork
     * @return array|bool
     */
    public function upsert(
        $idFactorNetwork
    ) {
        $account = new Account();
        $index = $account->getFactorNetworkForIndexation($idFactorNetwork);
        if (!$index) {
            return false;
        }
        $params = [
            'index' => APPLICATION_ENV . '-' . self::ELASTIC_SEARCH_INDEX,
            'type'  => self::ELASTIC_SEARCH_TYPE,
            'id'    => $idFactorNetwork,
            'body'  => $index,
        ];
        $indexed = $this->getClient()->index($params);

        return $indexed;
    }

    /**
     * Retrieve a specific document by indexed id
     */
    public function get(
        $idFactorNetwork
    ) {
        $params = [
            'index' => APPLICATION_ENV . '-' . self::ELASTIC_SEARCH_INDEX,
            'type'  => self::ELASTIC_SEARCH_TYPE,
            'id'    => $idFactorNetwork
        ];

        return $this->getClient()->get($params);
    }

    /**
     * Search elastic for social accounts matching criteria.
     * We use the nGram filter to match segments of words (@see readme.md).
     * ./plugin -install mobz/elasticsearch-head and http://localhost:9200/_plugin/head/ to get a visual editor
     *
     * @param       $network
     * @param       $criteria
     * @param array $verticalFilters
     * @param array $priceFilters
     * @param int   $fromPage
     * @return mixed
     */
    public function search(
        $network,
        $criteria,
        $verticalFilters = [],
        $priceFilters = [],
        $fromPage = 0
    ) {
        $criteria = preg_replace('/[^a-z0-9-\s_áéíóú]/', '', substr(trim(strtolower($criteria)), 0, 15));
        $query = [];

        if ($criteria) {
            //we are dealing with a single term, wild-carding is the safest bet
            if (count(explode(' ', $criteria)) == 1) {
                $query['query'][] = [
                    'bool' => [
                        'should' => [
                            ['wildcard' => ['accounts.alias' => '*' . $criteria . '*']],
                            ['wildcard' => ['accounts.name' => '*' . $criteria . '*']],
                            ['wildcard' => ['accounts.country' => '*' . $criteria . '*']],
                            ['wildcard' => ['accounts.tags' => '*' . $criteria . '*']],
                            ['wildcard' => ['accounts.bio' => '*' . $criteria . '*']]
                        ],
                    ],
                ];
            } else {
                //apply a term search query
                $query['query'][] = [
                    'bool' => [
                        'should' => [
                            ['query_string' => ['default_field' => 'accounts.alias', 'query' => $criteria]],
                            ['query_string' => ['default_field' => 'accounts.name', 'query' => $criteria]],
                            ['query_string' => ['default_field' => 'accounts.country', 'query' => $criteria]],
                            ['query_string' => ['default_field' => 'accounts.tags', 'query' => $criteria]],
                            ['query_string' => ['default_field' => 'accounts.bio', 'query' => $criteria]]
                        ]
                    ]
                ];

            }
        } else {
            //match all creators if no criteria was specified
            $query['query'] = [
                'bool' => [
                    'must' => [['match_all' => []]]
                ],
            ];
        }
        //filter the network out of results
        if ($network) {
            $query['filter']['and'] = [['term' => ['network' => $network]]];
        }
        //apply the vertical and demographic search filters
        foreach ($verticalFilters as $filter => $filterValue) {
            if (!strlen($filterValue)) {
                continue;
            }
            $query['filter']['and'][] = ['term' => ['segmentation_' . $filter => $filterValue]];
        }
        //apply price filters
        if (!empty($priceFilters)) {
            $query['filter']['and']
                = [['numeric_range' => ['price' => [$priceFilters['price-direction'] => $priceFilters['price']]]]];
        }
        $params = [
            'index' => APPLICATION_ENV . '-' . self::ELASTIC_SEARCH_INDEX,
            'type'  => self::ELASTIC_SEARCH_TYPE,
            'body'  => [
                'sort'  => [
                    'followers' => ['order' => 'desc']
                ],
                'from'  => $fromPage * self::ELASTIC_SEARCH_PAGINATION_HITS_PER_PAGE,
                'size'  => self::ELASTIC_SEARCH_PAGINATION_HITS_PER_PAGE,
                'query' => ['filtered' => $query]
            ]
        ];


        return $this->getClient()->search($params)['hits'];
    }

    /**
     * Remove factor's social network account from index
     *
     * @param $idFactorNetwork
     * @return array
     */
    public function delete(
        $idFactorNetwork
    ) {
        $params = [
            'index' => APPLICATION_ENV . '-' . self::ELASTIC_SEARCH_INDEX,
            'type'  => self::ELASTIC_SEARCH_TYPE,
            'id'    => $idFactorNetwork
        ];

        return $this->getClient()->delete($params);
    }

    /**
     * Rebuilds elastic index, but does not repopulate it
     */
    public function restructure()
    {
        //First clean up existing data by deleting index
        $url = 'http://' . $this->getConfig('elastic')->get(APPLICATION_ENV) . '/' . APPLICATION_ENV . '-'
            . 'creators/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_exec($ch);
        curl_close($ch);

        //Now recreate index
        $ch = curl_init($url);
        $data
            = '{
                   "aliases":{
                   },
                   "mappings":{
                      "accounts":{
                         "properties":{
                            "id_factor_network":{
                               "type":"string"
                            },
                            "alias":{
                               "type":"string"
                            },
                            "followers":{
                               "type":"string"
                            },
                            "name":{
                               "type":"string"
                            },
                            "network":{
                               "type":"string"
                            },
                            "photo":{
                               "type":"string"
                            },
                            "price":{
                               "type":"double"
                            },
                            "segmentation_age":{
                               "type":"string"
                            },
                            "segmentation_gender":{
                               "type":"string"
                            },
                            "segmentation_income":{
                               "type":"string"
                            },
                            "segmentation_interests":{
                               "type":"string"
                            },
                            "segmentation_language":{
                               "type":"string"
                            },
                            "segmentation_family":{
                               "type":"string"
                            },
                            "segmentation_civil_state":{
                               "type":"string"
                            },
                            "tags":{
                               "type":"string"
                            },
                            "bio":{
                               "type":"string"
                            }
                         }
                      }
                   },
                   "settings":{
                      "index":{
                         "number_of_shards":"5",
                         "number_of_replicas":"1"
                      }
                   },
                   "warmers":{
                   }
                }';
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        curl_close($ch);
    }
}