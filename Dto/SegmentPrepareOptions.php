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
        public readonly bool $clear,
    ) {
    }
}
