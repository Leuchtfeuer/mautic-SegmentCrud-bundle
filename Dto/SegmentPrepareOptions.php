<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto;

/**
 * Options for {@see \MauticPlugin\LeuchtfeuerSegmentCrudBundle\Services\SegmentPrepareService::prepare()}.
 */
final class SegmentPrepareOptions
{
    public function __construct(
        public readonly ?string $alias,
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly bool $noUpdate,
        public readonly bool $noCreate,
        /** Hard clear: DELETE membership rows from lead_lists_leads */
        public readonly bool $clear,
        /** Soft clear: SET manually_removed = 1; keeps rows (mutually exclusive with {@see $clear}) */
        public readonly bool $softClear,
    ) {
    }
}
