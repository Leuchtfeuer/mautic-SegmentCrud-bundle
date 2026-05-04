<?php

return [
    'name'        => 'Leuchtfeuer Segment CRUD',
    'description' => 'Segment CRUD rozšíření (Leuchtfeuer).',
    'version'     => '0.1.0',
    'author'      => 'Leuchtfeuer',
    'services'    => [
        'integrations' => [
            'mautic.integration.segmentcrud' => [
                'class'     => MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
