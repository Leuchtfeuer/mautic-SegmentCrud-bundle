<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration;

/**
 * Enables the IntegrationsBundle config modal (Details tab with isPublished toggle).
 */
final class ConfigSupport extends SegmentCrudIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
