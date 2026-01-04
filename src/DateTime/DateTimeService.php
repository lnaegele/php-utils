<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\DateTime;

use DateTime;

class DateTimeService
{    
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