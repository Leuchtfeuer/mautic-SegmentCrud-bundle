<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerSegmentCrudBundle\Command;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Dto\SegmentPrepareOptions;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Integration\SegmentCrudIntegration;
use MauticPlugin\LeuchtfeuerSegmentCrudBundle\Services\SegmentPrepareService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SegmentPrepareCommand extends Command
{
    public function __construct(
        private readonly SegmentPrepareService $segmentPrepareService,
        private readonly IntegrationHelper $integrationHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('leuchtfeuer:segment:prepare')
            ->setDescription('Create or update a segment by alias or id, optionally remove all members (for Databridge prep).')
            ->addOption('alias', null, InputOption::VALUE_OPTIONAL, 'Segment alias; creates segment if missing (unless --nocreate)')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Segment id; must already exist')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Name for create / update (default name on create: alias)')
            ->addOption('desc', null, InputOption::VALUE_OPTIONAL, 'Description for create / update (default on create: empty; on update: leave unchanged if omitted)')
            ->addOption('noupdate', null, InputOption::VALUE_NONE, 'If segment exists, do not change name or description')
            ->addOption('nocreate', null, InputOption::VALUE_NONE, 'With --alias: fail if the segment does not exist')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Remove all contacts from the segment (batched; safe for large segments)')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Rows per delete batch when using --clear', '1000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->isSegmentCrudIntegrationPublished()) {
            $io->error('Leuchtfeuer Segment CRUD is disabled. Enable the integration under Plugins (integration must be published).');

            return Command::FAILURE;
        }

        try {
            $alias = $input->getOption('alias');
            $alias = is_string($alias) && '' !== trim($alias) ? trim($alias) : null;

            $id = $this->parseIdOption($input->getOption('id'));

            $nameRaw = $input->getOption('name');
            $name    = is_string($nameRaw) && '' !== $nameRaw ? $nameRaw : null;

            $descRaw     = $input->getOption('desc');
            $description = null;
            if (null !== $descRaw) {
                $description = is_string($descRaw) ? $descRaw : '';
            }

            $batchSizeRaw = $input->getOption('batch-size');
            if (is_numeric($batchSizeRaw)) {
                $this->segmentPrepareService->setBatchSize((int) $batchSizeRaw);
            }

            $options = new SegmentPrepareOptions(
                $alias,
                $id,
                $name,
                $description,
                (bool) $input->getOption('noupdate'),
                (bool) $input->getOption('nocreate'),
                (bool) $input->getOption('clear'),
            );

            $result = $this->segmentPrepareService->prepare($options, $output);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        } catch (\RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $segment = $result->segment;

        $io->success(
            sprintf(
                'Segment %d (%s) — %s%s%s.',
                (int) $segment->getId(),
                $segment->getAlias(),
                $result->created ? 'created' : 'loaded',
                $result->metadataUpdated ? ', metadata updated' : '',
                $result->clearedMembers > 0 ? sprintf(', %d membership row(s) removed', $result->clearedMembers) : ''
            )
        );

        return Command::SUCCESS;
    }

    private function parseIdOption(mixed $raw): ?int
    {
        if (null === $raw || '' === $raw) {
            return null;
        }

        if (!is_numeric($raw)) {
            throw new \InvalidArgumentException('Option --id must be a positive integer.');
        }

        $id = (int) $raw;

        if ($id < 1) {
            throw new \InvalidArgumentException('Option --id must be a positive integer.');
        }

        return $id;
    }

    private function isSegmentCrudIntegrationPublished(): bool
    {
        $integration = $this->integrationHelper->getIntegrationObject(SegmentCrudIntegration::PLUGIN_NAME);

        if (false === $integration || !$integration instanceof AbstractIntegration) {
            return false;
        }

        return (bool) $integration->getIntegrationSettings()->getIsPublished();
    }
}
