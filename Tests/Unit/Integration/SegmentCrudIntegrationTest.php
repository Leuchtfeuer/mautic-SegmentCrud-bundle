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

        $this->integration = new class extends SegmentCrudIntegration {
            public function __construct()
            {
            }
        };
    }

    public function testGetNameReturnsPluginKey(): void
    {
        Assert::assertSame(SegmentCrudIntegration::PLUGIN_NAME, $this->integration->getName());
    }

    public function testGetDisplayNameIsNonEmpty(): void
    {
        Assert::assertNotSame('', $this->integration->getDisplayName());
    }

    public function testGetAuthenticationTypeIsNonEmpty(): void
    {
        Assert::assertNotSame('', $this->integration->getAuthenticationType());
    }
}
