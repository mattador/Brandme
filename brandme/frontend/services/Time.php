<?php
namespace Frontend\Services;

use Frontend\Exception;

/**
 * All timezones change relative to UTC, so we have to store the location not the transitional offset to be precise
 *
 * Class Time
 * @package Frontend\Services
 */
class Time
{

    const DEFAULT_REGIONAL_TIMEZONE = 'America/Mexico_City';

    /**
     * Add more regions here if required
     *
     * @var array
     */
    public static $regions = array(
        'America' => \DateTimeZone::AMERICA,
        'Australia' => \DateTimeZone::AUSTRALIA,
        'Europe' => \DateTimeZone::EUROPE,
        'Pacific' => \DateTimeZone::PACIFIC
    );

    /**
     * Courtesy of https://gist.github.com/Xeoncross/1204255
     */
    public static function getTimezones()
    {
        $timezones = [];
        foreach (self::$regions as $name => $mask) {
            $zones = \DateTimeZone::listIdentifiers($mask);
            foreach ($zones as $timezone) {
                // Lets sample the time there right now
                $time = new \DateTime(null, new \DateTimeZone($timezone));
                // Remove region name and add a sample time
                $timezones[$name][$timezone] = substr(str_replace('_', ' ', $timezone), strlen($name) + 1) . ' - ' . $time->format('g:i a');
            }
        }
        return $timezones;
    }

    /**
     * Convert localized time to UTC MySQL timestamp
     *
     * @param $timezoneTime
     * @param $timezone
     * @return bool|string
     * @throws Exception
     */
    public function timezoneTimeToUtc($timezoneTime, $timezone)
    {
        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            throw new Exception($timezone . ' is an invalid timezone');
        }
        $time = strtotime($timezoneTime . ' ' . $timezone);
        if (!$time) {
            throw new Exception('Could not calculate the time/offset. Invalid timezone (' . $timezone . ') or time (' . $timezoneTime . ') provided.');
        }
        $utcTimeStamp = date('Y-m-d H:i:s', $time);
        return $utcTimeStamp;
    }

    /**
     * Covert UTC MySQL timestamp or (datetime format) to a timezone specific MySQL timestamp
     *
     * @param $utcTime
     * @param $timezone
     * @param bool $format
     * @return string
     * @throws Exception
     */
    public function utcToTimezone($utcTime, $timezone, $format = false)
    {
        if (!in_array($timezone, \DateTimeZone::listIdentifiers())) {
            throw new Exception($timezone . ' is an invalid timezone');
        }
        $utcDateTime = new \DateTime($utcTime);
        $utcDateTime->setTimezone(new \DateTimeZone($timezone));
        if (!$format) {
            return $utcDateTime->format('Y-m-d H:i:s');
        } else {
            return $utcDateTime->format($format);
        }
    }


}