<?php

namespace Frontend\Widgets;

class Currency
{

    /**
     * Currency format helper
     */
    public static function format($amount)
    {
        $formated = number_format(preg_replace('/[^\d\.]/', '', $amount), 2);
        //For display friendly purposes if there is a 1 cent difference round to nearest whole number
        if (round(abs($formated - round($formated)), 2) === 0.01) {
            return number_format(round($formated), 2);
        }

        return $formated;
    }
}