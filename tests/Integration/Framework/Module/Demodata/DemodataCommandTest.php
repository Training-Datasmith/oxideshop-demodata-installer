<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use Doctrine\DBAL\Exception as DbalException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataCommand;
use OxidEsales\DemoDataInstaller\Tests\Support\DatabaseTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Path;

final class DemodataCommandTest extends DatabaseTestCase
{
    public function testExecuteInstallsDemodataAndReturnsSuccess(): void
    {
        $fixture = $this->createFixture();
        $tester = new CommandTester(new DemodataCommand($this->createDao($fixture['context'])));

        $status = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $status);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Running precondition checks...', $display);
        $this->assertStringContainsString('Applying demodata', $display);
        $this->assertSame('First', $this->fetchArticleTitle('demo-1'));
        $this->assertSame(
            'demo-picture',
            file_get_contents(Path::join($fixture['outPath'], 'pictures', 'item.txt')),
        );
    }

    public function testExecuteReturnsFailureAndPrintsEveryPrecondition(): void
    {
        $fixture = $this->createFixture();
        $this->insertUser();
        $this->insertArticle();
        $this->insertCategory();

        $tester = new CommandTester(new DemodataCommand($this->createDao($fixture['context'])));
        $status = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('submitted users', $display);
        $this->assertStringContainsString('oxarticles', $display);
        $this->assertStringContainsString('oxcategories', $display);
        $this->assertStringContainsString(' - ', $display);
        $this->assertFileDoesNotExist(Path::join($fixture['outPath'], 'pictures', 'item.txt'));
        $this->assertNull($this->fetchArticleTitle('demo-1'));
    }

    public function testExecuteLetsDatabaseErrorsPropagate(): void
    {
        $fixture = $this->createFixture("INSERT INTO `oxarticles` (`OXID`, `OXTITLE`) VALUES ('broken', 'Broken');\nNOT VALID SQL;");
        $tester = new CommandTester(new DemodataCommand($this->createDao($fixture['context'])));

        $this->expectException(DbalException::class);
        $tester->execute([]);
    }

    public function testExecuteLetsMissingOutDirectoryPropagate(): void
    {
        $fixture = $this->createFixture(withOutDirectory: false);
        $tester = new CommandTester(new DemodataCommand($this->createDao($fixture['context'])));

        $this->expectException(IOExceptionInterface::class);
        $tester->execute([]);
    }

    public function testDescriptionMentionsDemodata(): void
    {
        $fixture = $this->createFixture();
        $command = new DemodataCommand($this->createDao($fixture['context']));

        $this->assertStringContainsString('demodata', strtolower((string) $command->getDescription()));
    }
}
