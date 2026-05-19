<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration;

final class SegmentCrudIntegrationStatus
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function isPublished(): bool
    {
        /** @var Integration|null $integration */
        $integration = $this->em->getRepository(Integration::class)->findOneBy([
            'name' => SegmentCrudIntegration::NAME,
        ]);

        if (!$integration instanceof Integration) {
            return false;
        }

        return (bool) $integration->getIsPublished();
    }
}
