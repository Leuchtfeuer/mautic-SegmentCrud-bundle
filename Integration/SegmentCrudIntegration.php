<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

final class SegmentCrudIntegration extends BasicIntegration implements BasicInterface
{
    public const NAME = 'SegmentCrud';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Leuchtfeuer Segment CRUD';
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerSegmentCrudBundle/Assets/img/segmentcrud.png';
    }
}
