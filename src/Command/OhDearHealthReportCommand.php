<?php declare(strict_types=1);

namespace Tadpole\OhDear\Command;

use Tadpole\OhDear\Service\OhDearHealthReportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand('ohdear:health-report')]
class OhDearHealthReportCommand extends Command
{
    protected static $defaultName = 'ohdear:health-report';

    public function __construct(public readonly OhDearHealthReportService $ohDearHealthReportService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Generate a health report for OhDear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $healthReport = $this->ohDearHealthReportService->getHealthChecksArray();

        $table = new Table($output);
        $table->setHeaders(['Check', 'Status', 'Message']);

        foreach($healthReport['checkResults'] as $check) {
            $table->addRow([$check['label'], $check['status'], $check['notificationMessage']]);
        }
        $table->render();
        return Command::SUCCESS;
    }

}