<?php

namespace AppBundle\Utility;

class DateTimeUtility
{
    public static function createDateTime(string $time = 'now') : \DateTime
    {
        return new \DateTime($time, new \DateTimeZone('UTC'));
    }
}
