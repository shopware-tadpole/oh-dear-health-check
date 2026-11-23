<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;
use Tadpole\OhDear\Command\OhDearHealthReportCommand;
use Tadpole\OhDear\Enum\HealthStatus;
use Tadpole\OhDear\Service\OhDearHealthReportService;
use Tadpole\OhDear\Tests\HealthCheckStub;

class OhDearHealthReportCommandTest extends TestCase
{
    private static function command(HealthCheckStub ...$checks): OhDearHealthReportCommand
    {
        return new OhDearHealthReportCommand(new OhDearHealthReportService($checks));
    }

    public function testNameAndDescription(): void
    {
        $command = self::command();

        self::assertSame('ohdear:health-report', $command->getName());
        self::assertSame('Generate a health report for OhDear', $command->getDescription());
    }

    public function testNoChecksRendersEmptyTable(): void
    {
        $tester = new CommandTester(self::command());

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertMatchesRegularExpression('/\|\s*Check\s*\|\s*Status\s*\|\s*Message\s*\|/', $tester->getDisplay());
    }

    public function testRendersTableOfChecks(): void
    {
        $tester = new CommandTester(self::command(
            new HealthCheckStub('Queue', HealthStatus::WARNING, 'The queue takes too long to be processed.'),
            new HealthCheckStub('Tasks', HealthStatus::OK, 'Scheduled tasks are fine'),
        ));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertMatchesRegularExpression('/\|\s*Check\s*\|\s*Status\s*\|\s*Message\s*\|/', $display);
        self::assertMatchesRegularExpression('/\|\s*Queue label\s*\|\s*warning\s*\|\s*The queue takes too long to be processed\.\s*\|/', $display);
        self::assertMatchesRegularExpression('/\|\s*Tasks label\s*\|\s*ok\s*\|\s*Scheduled tasks are fine\s*\|/', $display);
    }
}
