<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto;

use Mautic\LeadBundle\Entity\LeadList;

final class SegmentPrepareResult
{
    public function __construct(
        public readonly LeadList $segment,
        public readonly bool $created,
        public readonly bool $metadataUpdated,
        /** Number of lead_lists_leads rows updated to manually_removed = 1 when --clear was used */
        public readonly int $clearedMembers,
    ) {
    }
}
