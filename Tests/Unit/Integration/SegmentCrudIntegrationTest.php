<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Tests\Unit\Integration;

use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class SegmentCrudIntegrationTest extends TestCase
{
    private SegmentCrudIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integration = new SegmentCrudIntegration();
    }

    public function testGetNameReturnsPluginKey(): void
    {
        Assert::assertSame(SegmentCrudIntegration::NAME, $this->integration->getName());
    }

    public function testGetDisplayNameIsNonEmpty(): void
    {
        Assert::assertNotSame('', $this->integration->getDisplayName());
    }

    public function testGetIconIsNonEmpty(): void
    {
        Assert::assertNotSame('', $this->integration->getIcon());
    }
}
