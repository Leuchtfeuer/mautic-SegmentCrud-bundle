<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto;

use Mautic\LeadBundle\Entity\LeadList;

final class SegmentPrepareResult
{
    /**
     * @param 'none'|'hard'|'permanent' $clearMode
     */
    public function __construct(
        public readonly LeadList $segment,
        public readonly bool $created,
        public readonly bool $metadataUpdated,
        /** Rows affected by --clear (DELETE) or --permanent-clear (UPDATE); 0 if neither flag was used */
        public readonly int $clearedMembers,
        public readonly string $clearMode = 'none',
    ) {
    }
}
