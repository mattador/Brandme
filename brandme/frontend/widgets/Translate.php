<?php

namespace Frontend\Widgets;

use Phalcon\Translate\Adapter\Csv;
use Phalcon\Validation\Message;

class Translate
{
    /**
     * Translation adaptor
     *
     * @return \Phalcon\Translate\Adapter\Csv
     */
    public static function _($query, $placeholders = [])
    {
        $translate = new Csv(
            [
                'content'   => APPLICATION_LANG_DIR.'/es_MX.csv', // required
                'delimiter' => ';', // optional, default - ;
                'length'    => '0', // optional, default - 0
                'enclosure' => '"', // optional, default - "
            ]
        );
        if (is_object($query) && $query instanceof Message) {
            $query = $query->getMessage();
        }

        return $translate->query($query, $placeholders);
    }
}