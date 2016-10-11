<?php
namespace Frontend\Services\Search;

use Entities\Segmentation;
use Frontend\Services\AbstractService;

class Filter extends AbstractService
{

    public static $filters
        = [
            'age',
            'education',
            'gender',
            'income',
            'interests',
            'language',
            'family',
            'civil_state'
        ];


    /**
     * Returns an array of Factor Network attributes, used for filtering search results in Elastic
     *
     * @return array
     */
    public function getHtmlFilters()
    {
        //@todo once we can extract data from networks about number of followers, include them here also
        $segmentation = Segmentation::find(
            'segment IN ("'.implode('","', self::$filters).'")'
        );
        $segments = [];
        foreach ($segmentation as $segment) {
            if (!isset($segments[$segment->segment])) {
                $segments[$segment->segment] = [];
            }
            //don't give away the id, for security and friendly browsing
            $segments[$segment->segment][/*$segment->id*/] = $segment->name;
        }
        $segments['price'] = 0;

        //$segments['statistics_following'] = 0;

        return $segments;
    }
}