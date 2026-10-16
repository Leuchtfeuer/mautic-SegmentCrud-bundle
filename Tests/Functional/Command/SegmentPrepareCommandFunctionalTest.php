<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Component\Console\Command\Command;

final class SegmentPrepareCommandFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testClearSoftDeletesMembershipRowsWhenUsingAlias(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-prepare-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 4);
        $resolvedAlias = (string) $segment->getAlias();

        $this->assertSame(4, $this->countActiveMembershipRows((int) $segment->getId()));
        $this->assertSame(4, $this->countTotalMembershipRows((int) $segment->getId()));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias' => $resolvedAlias,
            '--clear' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(0, $this->countTotalMembershipRows((int) $segment->getId()));
    }

    public function testClearSoftDeletesMembershipRowsWhenUsingId(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias     = 'lf-seg-prepare-id-'.bin2hex(random_bytes(8));
        [$segment] = $this->createSegmentWithManualMembers($alias, 3);
        $segmentId = (int) $segment->getId();

        $this->assertSame(3, $this->countTotalMembershipRows($segmentId));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--id'    => (string) $segmentId,
            '--clear' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(0, $this->countTotalMembershipRows($segmentId));
    }

    public function testPrepareWithoutClearLeavesMembersUntouched(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-no-clear-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 2);
        $segmentId     = (int) $segment->getId();
        $resolvedAlias = (string) $segment->getAlias();

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias' => $resolvedAlias,
            '--name'  => 'Updated segment name',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(2, $this->countActiveMembershipRows($segmentId));

        $this->em->refresh($segment);
        $this->assertSame('Updated segment name', $segment->getName());
    }

    public function testCommandFailsWhenIntegrationIsNotPublished(): void
    {
        $this->installSegmentCrudPlugin(false);

        $alias         = 'lf-seg-off-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 2);
        $segmentId     = (int) $segment->getId();
        $resolvedAlias = (string) $segment->getAlias();

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias' => $resolvedAlias,
            '--clear' => true,
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('disabled', $tester->getDisplay());
        $this->assertSame(2, $this->countActiveMembershipRows($segmentId));
    }

    public function testPermanentClearMarksManuallyRemovedButKeepsRows(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-permanent-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 4);
        $resolvedAlias = (string) $segment->getAlias();
        $segmentId     = (int) $segment->getId();

        $this->assertSame(4, $this->countActiveMembershipRows($segmentId));
        $this->assertSame(4, $this->countTotalMembershipRows($segmentId));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias'           => $resolvedAlias,
            '--permanent-clear' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(0, $this->countActiveMembershipRows($segmentId));
        $this->assertSame(4, $this->countTotalMembershipRows($segmentId));
    }

    public function testCannotCombineClearAndPermanentClear(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-both-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 2);
        $resolvedAlias = (string) $segment->getAlias();

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias'           => $resolvedAlias,
            '--clear'           => true,
            '--permanent-clear' => true,
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('not both', $tester->getDisplay());
        $this->assertSame(2, $this->countActiveMembershipRows((int) $segment->getId()));
    }

    public function testNoCreateFailsWhenAliasDoesNotExist(): void
    {
        $this->installSegmentCrudPlugin(true);

        $missingAlias = 'missing-alias-'.bin2hex(random_bytes(8));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias'    => $missingAlias,
            '--nocreate' => true,
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCreatesSegmentThenClearsWhenAliasMissing(): void
    {
        $this->installSegmentCrudPlugin(true);

        $newAlias = 'lf-seg-new-'.bin2hex(random_bytes(8));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias' => $newAlias,
            '--clear' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        /** @var LeadList|null $segment */
        $segment = $this->em->getRepository(LeadList::class)->findOneBy(['alias' => strtolower($newAlias)]);
        $this->assertInstanceOf(LeadList::class, $segment);

        $this->assertSame(0, $this->countTotalMembershipRows((int) $segment->getId()));
    }

    public function testSmallBatchSizeStillClearsAllRows(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-batch-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 5);
        $resolvedAlias = (string) $segment->getAlias();

        $this->assertSame(5, $this->countTotalMembershipRows((int) $segment->getId()));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias'      => $resolvedAlias,
            '--clear'      => true,
            '--batch-size' => '2',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(0, $this->countTotalMembershipRows((int) $segment->getId()));
        $this->assertStringContainsString('batch', $tester->getDisplay());
    }

    public function testSmallBatchSizePermanentClearProcessesInChunks(): void
    {
        $this->installSegmentCrudPlugin(true);

        $alias         = 'lf-seg-permanent-batch-'.bin2hex(random_bytes(8));
        [$segment]     = $this->createSegmentWithManualMembers($alias, 5);
        $resolvedAlias = (string) $segment->getAlias();

        $this->assertSame(5, $this->countActiveMembershipRows((int) $segment->getId()));

        $tester = $this->testSymfonyCommand('leuchtfeuer:segment:prepare', [
            '--alias'           => $resolvedAlias,
            '--permanent-clear' => true,
            '--batch-size'      => '2',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(0, $this->countActiveMembershipRows((int) $segment->getId()));
        $this->assertSame(5, $this->countTotalMembershipRows((int) $segment->getId()));
        $this->assertStringContainsString('batch', $tester->getDisplay());
    }

    private function installSegmentCrudPlugin(bool $published): void
    {
        $plugin = $this->em->getRepository(Plugin::class)->findOneBy(['bundle' => 'LeuchtfeuerSegmentCrudBundle']);

        if (!$plugin instanceof Plugin) {
            $plugin = new Plugin();
            $plugin->setName('Leuchtfeuer Segment CRUD');
            $plugin->setBundle('LeuchtfeuerSegmentCrudBundle');
            $this->em->persist($plugin);
            $this->em->flush();
        }

        $integration = $this->em->getRepository(Integration::class)->findOneBy([
            'name'   => 'SegmentCrud',
            'plugin' => $plugin,
        ]);

        if (!$integration instanceof Integration) {
            $integration = new Integration();
            $integration->setPlugin($plugin);
            $integration->setName('SegmentCrud');
            $this->em->persist($integration);
        }

        $integration->setIsPublished($published);
        $this->em->flush();
    }

    /**
     * @return array{LeadList, Lead[]}
     */
    private function createSegmentWithManualMembers(string $alias, int $leadCount): array
    {
        /** @var ListModel $listModel */
        $listModel = self::getContainer()->get('mautic.lead.model.list');

        $segment = new LeadList();
        $segment->setName('Functional '.$alias);
        $segment->setAlias($alias);
        $segment->setFilters([]);
        $segment->setIsGlobal(true);
        $segment->setIsPublished(true);
        $listModel->saveEntity($segment);

        $batchUid = bin2hex(random_bytes(8));
        $leads    = [];
        for ($i = 0; $i < $leadCount; ++$i) {
            $lead = new Lead();
            $lead->setEmail(sprintf('lf-seg-functional-%s-%d@test.invalid', $batchUid, $i));
            $this->em->persist($lead);
            $leads[] = $lead;
        }
        $this->em->flush();

        foreach ($leads as $lead) {
            $listModel->addLead($lead, $segment, true);
        }

        return [$segment, $leads];
    }

    /**
     * Active memberships: `manually_removed = 0` (same as Mautic segment UI counts).
     */
    private function countActiveMembershipRows(int $segmentId): int
    {
        $sql = 'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads WHERE leadlist_id = ? AND manually_removed = 0';

        return $this->fetchCount($sql, [$segmentId]);
    }

    /** All rows in lead_lists_leads for the segment (including manually removed). */
    private function countTotalMembershipRows(int $segmentId): int
    {
        $sql = 'SELECT COUNT(*) FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads WHERE leadlist_id = ?';

        return $this->fetchCount($sql, [$segmentId]);
    }

    /**
     * @param array<int, int|string|null> $params
     */
    private function fetchCount(string $sql, array $params): int
    {
        $count = $this->em->getConnection()->fetchOne($sql, $params);
        if (!is_numeric($count)) {
            return 0;
        }

        return (int) $count;
    }
}
