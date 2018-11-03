<?php

namespace ReliQArts\Docweaver\Traits;

/**
 * Timed trait.
 */
trait Timed
{
    /**
     * Get seconds since a microtime start-time.
     *
     * @param int $startTime start time in microseconds
     *
     * @return string seconds since, to 2 decimal places
     */
    protected function secondsSince(int $startTime): string
    {
        $duration = microtime(true) - $startTime;
        $hours = (int) ($duration / 60 / 60);
        $minutes = (int) ($duration / 60) - $hours * 60;
        $seconds = $duration - $hours * 60 * 60 - $minutes * 60;

        return number_format((float) $seconds, 2, '.', '');
    }
}
