<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\Support\ConfigSupport;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Dto',
        'vendor',
    ];

    $services->load('MauticPlugin\\LeuchtfeuerSegmentCrudBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->get(SegmentCrudIntegration::class)
        ->tag('mautic.integration')
        ->tag('mautic.basic_integration');
    $services->get(ConfigSupport::class)
        ->tag('mautic.config_integration');

    // Required by PluginBundle IntegrationHelper (service id = strtolower('mautic.integration.'.$name))
    // so the Integration DB row is created and the Plugins UI opens the config modal.
    $services->alias('mautic.integration.segmentcrud', SegmentCrudIntegration::class);
};
