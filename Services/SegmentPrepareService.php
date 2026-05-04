<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Services;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto\SegmentPrepareOptions;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto\SegmentPrepareResult;
use Symfony\Component\Console\Output\OutputInterface;

final class SegmentPrepareService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ListModel $listModel,
        private readonly SegmentCountCacheHelper $segmentCountCacheHelper,
        private int $batchSize = 1000,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function prepare(SegmentPrepareOptions $options, OutputInterface $output): SegmentPrepareResult
    {
        if (null !== $options->alias && null !== $options->id) {
            throw new \InvalidArgumentException('Specify either --alias or --id, not both.');
        }

        if (null === $options->alias && null === $options->id) {
            throw new \InvalidArgumentException('Provide --alias or --id.');
        }

        $created           = false;
        $metadataUpdated   = false;
        $clearedMembers    = 0;

        if (null !== $options->id) {
            $segment = $this->findSegmentById($options->id);
            if (null === $segment) {
                throw new \RuntimeException(sprintf('Segment id %d was not found.', $options->id));
            }
        } else {
            $alias = $options->alias;
            \assert(null !== $alias);
            $segment = $this->findSegmentByAlias($alias);
            if (null === $segment) {
                if ($options->noCreate) {
                    throw new \RuntimeException(sprintf('Segment with alias %s does not exist (--nocreate).', $alias));
                }

                $segment = $this->createSegment($options, $alias);
                $created = true;
            }
        }

        if (!$created && !$options->noUpdate) {
            $metadataUpdated = $this->applyMetadataUpdate($segment, $options);
        }

        $clearMode = 'none';

        if ($options->clear && $options->softClear) {
            throw new \InvalidArgumentException('Use either --clear or --permanent-clear, not both.');
        }

        if ($options->clear) {
            $clearedMembers = $this->clearAllMembersHard($segment, $output);
            $clearMode      = 'hard';
        } elseif ($options->softClear) {
            $clearedMembers = $this->clearAllMembersSoft($segment, $output);
            $clearMode      = 'soft';
        }

        return new SegmentPrepareResult($segment, $created, $metadataUpdated, $clearedMembers, $clearMode);
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = max(1, $batchSize);
    }

    private function findSegmentById(int $id): ?LeadList
    {
        /** @var LeadList|null $segment */
        $segment = $this->em->getRepository(LeadList::class)->find($id);

        return $segment;
    }

    private function findSegmentByAlias(string $alias): ?LeadList
    {
        /** @var LeadList|null $segment */
        $segment = $this->em->getRepository(LeadList::class)->findOneBy(['alias' => $alias]);

        return $segment;
    }

    private function createSegment(SegmentPrepareOptions $options, string $alias): LeadList
    {
        $name        = $options->name ?? $alias;
        $description = $options->description ?? '';

        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $segment->setDescription($description);
        $segment->setFilters([]);
        $segment->setIsGlobal(true);
        $segment->setIsPublished(true);

        $this->listModel->saveEntity($segment);

        return $segment;
    }

    private function applyMetadataUpdate(LeadList $segment, SegmentPrepareOptions $options): bool
    {
        $changed = false;

        if (null !== $options->name && $segment->getName() !== $options->name) {
            $segment->setName($options->name);
            $segment->setPublicName($options->name);
            $changed = true;
        }

        if (null !== $options->description && $segment->getDescription() !== $options->description) {
            $segment->setDescription($options->description);
            $changed = true;
        }

        if ($changed) {
            $this->listModel->saveEntity($segment);
        }

        return $changed;
    }

    /**
     * Deletes segment membership rows in batches (hard remove from lead_lists_leads).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function clearAllMembersHard(LeadList $segment, OutputInterface $output): int
    {
        $segmentId = (int) $segment->getId();
        if ($segmentId < 1) {
            return 0;
        }

        $connection = $this->em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.'lead_lists_leads';
        $total      = 0;

        $output->writeln(sprintf('<comment>Deleting memberships for segment %d in batches of %d…</comment>', $segmentId, $this->batchSize));

        do {
            $sql = 'DELETE FROM '.$table.' WHERE leadlist_id = ? LIMIT '.$this->batchSize;

            $affected = (int) $connection->executeStatement(
                $sql,
                [$segmentId],
                [ParameterType::INTEGER]
            );

            $total += $affected;

            if ($affected > 0) {
                $output->writeln(sprintf('  … deleted %d row(s) (total %d)', $affected, $total));
            }

            usleep(50000);
        } while ($affected > 0);

        $this->segmentCountCacheHelper->invalidateSegmentContactCount($segmentId);
        gc_collect_cycles();

        $output->writeln(sprintf('<info>Deleted %d segment membership row(s); cache invalidated.</info>', $total));

        return $total;
    }

    /**
     * Sets manually_removed = 1 on every active membership (soft clear). Rows remain in lead_lists_leads.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function clearAllMembersSoft(LeadList $segment, OutputInterface $output): int
    {
        $segmentId = (int) $segment->getId();
        if ($segmentId < 1) {
            return 0;
        }

        $connection = $this->em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.'lead_lists_leads';
        $total      = 0;

        $output->writeln(sprintf('<comment>Marking segment %d memberships as manually removed, batches of %d…</comment>', $segmentId, $this->batchSize));

        do {
            $sql = 'UPDATE '.$table.' SET manually_removed = 1 WHERE leadlist_id = ? AND manually_removed = 0 LIMIT '.$this->batchSize;

            $affected = (int) $connection->executeStatement(
                $sql,
                [$segmentId],
                [ParameterType::INTEGER]
            );

            $total += $affected;

            if ($affected > 0) {
                $output->writeln(sprintf('  … marked %d row(s) (total %d)', $affected, $total));
            }

            usleep(50000);
        } while ($affected > 0);

        $this->segmentCountCacheHelper->invalidateSegmentContactCount($segmentId);
        gc_collect_cycles();

        $output->writeln(sprintf('<info>Marked %d segment membership row(s) as manually removed; cache invalidated.</info>', $total));

        return $total;
    }
}
