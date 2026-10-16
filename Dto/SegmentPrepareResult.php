<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto;

use Mautic\LeadBundle\Entity\LeadList;

final readonly class SegmentPrepareResult
{
    /**
     * @param 'none'|'soft'|'permanent' $clearMode
     */
    public function __construct(
        public LeadList $segment,
        public bool $created,
        public bool $metadataUpdated,
        /** Rows affected by --clear (DELETE) or --permanent-clear (UPDATE); 0 if neither flag was used */
        public int $clearedMembers,
        public string $clearMode = 'none',
    ) {
    }
}
