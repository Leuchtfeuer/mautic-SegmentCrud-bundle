<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class SegmentCrudIntegration extends AbstractIntegration
{
    public const PLUGIN_NAME = 'SegmentCrud';

    public function getName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName(): string
    {
        return 'Leuchtfeuer Segment CRUD';
    }

    public function getAuthenticationType(): string
    {
        return 'none';
    }
}
