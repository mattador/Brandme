<?php

namespace Frontend\Services\Form\Campaign;

use Common\Services\Sql;

class Opportunity
{
    const NEXT = 'Next';
    const BACK = 'Back';

    /**
     * Order of opportunity creation partial views/steps
     *
     * @var array
     */
    public static $steps
        = [
            'type',
            'network',
            'instructions',
            'segmentation', //this step gets conditionally removed when the type field from the instructions step is = direct
            'confirmation'
        ];

    public static $contentType
        = [
            'image',
            'status_update',
            'blog',
            'video'
        ];

    public static $networks
        = [
            'twitter',
            'facebook',
            'instagram'
        ];

    /**
     * List of expected variables in multistep form
     *
     * @var array
     */
    public static $expectParams
        = [
            'type'         => ['type' => ''],
            'network'      => ['network' => ''],
            'instructions' => [
                'opportunity_name'  => '',
                'type'              => '',
                'max_offer'         => '',
                'about_opportunity' => '',
                'requirements'      => '',
                'ideal_candidate'   => '',
                'start_opportunity' => '',
                'image_logo'        => '',
                'minute'            => 0,
                'link'              => '',
                'link_required'     => '',
                'hashtag'           => '',
                'hashtag_required'  => false,
                'mention'           => '',
                'mention_required'  => false
            ],
            'segmentation' => [
                'statistics_followers' => '',
                'statistics_following' => '',
                'location_country'     => '',
                'location_state'       => '',
                //'location_postcode' => '',
                'gender'               => [],
                'age'                  => [],
                'interests'            => [],
                'family'               => [],
                'civil_state'          => [],
                'education'            => [],
                'language'             => [],
                'income'               => []
            ]
        ];

    public static function getSegmentationOptions()
    {
        $followers = [];
        foreach (
            Sql::find(
                'SELECT id, name as followers FROM segmentation WHERE segment = "statistics_followers" ORDER BY LENGTH(name), name ASC'
            ) as $follower
        ) {
            $followers[$follower['id']] = '+'.number_format($follower['followers'], 0, '', ',');
        }
        $following = [];
        foreach (
            Sql::find(
                'SELECT id, name as followers FROM segmentation WHERE segment = "statistics_following" ORDER BY LENGTH(name), name ASC'
            ) as $follower
        ) {
            $following[$follower['id']] = '+'.number_format($follower['followers'], 0, '', ',');
        }
        $states = [];
        foreach (Sql::find('SELECT id, name as state FROM segmentation WHERE segment = "location_state"') as $state) {
            $states[$state['id']] = $state['state'];
        }
        $countries = [];
        foreach (
            Sql::find(
                'SELECT id, name as country FROM segmentation WHERE segment = "location_country" ORDER BY SUBSTRING(name, 1, 1) ASC'
            ) as $country
        ) {
            $countries[$country['id']] = $country['country'];
        }
        $segmentation = [];
        foreach (
            Sql::find(
                'SELECT id, name, segment FROM segmentation WHERE segment IN ("age","education","gender","income","interests","language","family","civil_state")'
            ) as $segment
        ) {
            if (!isset($segmentation[$segment['segment']])) {
                $segmentation[$segment['segment']] = [];
            }
            $segmentation[$segment['segment']][] = ['id' => $segment['id'], 'name' => $segment['name']];
        }

        return [
            'statistics_followers' => $followers,
            'statistics_following' => $following,
            'location_state'       => $states,
            'location_country'     => $countries,
            'segmentation'         => $segmentation
        ];
    }

}