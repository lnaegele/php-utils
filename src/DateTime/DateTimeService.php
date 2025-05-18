<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\DateTime;

use DateTime;
use DateTimeZone;

class DateTimeService
{
    private readonly DateTimeZone $utcTimezone;
    
    public function __construct()
    {
        $this->utcTimezone = new DateTimeZone("UTC");
    }

    /**
     * @deprecated
     */
    public function toUtcIsoString(DateTime $dateTime): string {
        $copy = clone $dateTime;
        $copy->setTimezone($this->utcTimezone);
        return $copy->format('Y-m-d H:i:s');
    }

    /**
     * @deprecated
     */
    public function toUtcIsoStringOrNull(?DateTime $dateTime): ?string {
        return $dateTime==null ? null : $this->toUtcIsoString($dateTime);
    }

    /**
     * @deprecated
     */
    public function fromUtcIsoString(string $isoDateTimeString): DateTime {
        return DateTime::createFromFormat('Y-m-d H:i:s', $isoDateTimeString, $this->utcTimezone);
    }

    /**
     * @deprecated
     */
    public function fromUtcIsoStringOrNull(?string $isoDateTimeString): ?DateTime {
        return $isoDateTimeString==null ? null : $this->fromUtcIsoString($isoDateTimeString);
    }
    
    public function toIsoString(DateTime $dateTime): string {
        return $dateTime->format('Y-m-d\TH:i:s.uP');
    }

    public function toIsoStringOrNull(?DateTime $dateTime): ?string {
        return $dateTime==null ? null : $this->toIsoString($dateTime);
    }

    public function fromIsoString(string $isoDateTimeString): DateTime {
        return DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $isoDateTimeString);
    }

    public function fromIsoStringOrNull(?string $isoDateTimeString): ?DateTime {
        return $isoDateTimeString==null ? null : $this->fromIsoString($isoDateTimeString);
    }
}