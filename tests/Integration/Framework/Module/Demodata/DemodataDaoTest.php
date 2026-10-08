<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Integration\Framework\Module\Demodata;

use Doctrine\DBAL\Exception as DbalException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDaoInterface;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\DemodataException;
use OxidEsales\DemoDataInstaller\Tests\Support\DatabaseTestCase;
use OxidEsales\DemoDataInstaller\Tests\Support\DemodataFixtureFactory;
use OxidEsales\DemoDataInstaller\Tests\Support\FakeBasicContext;
use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataDaoTest extends DatabaseTestCase
{
    public function testDaoImplementsInterface(): void
    {
        $fixture = $this->createFixture();
        $dao = $this->createDao($fixture['context']);

        $this->assertInstanceOf(DemodataDaoInterface::class, $dao);
        $this->assertInstanceOf(DemodataDao::class, $dao);
    }

    public function testCheckPreconditionsPassesWhenTablesEmptyAndDumpReadable(): void
    {
        $fixture = $this->createFixture();
        $dumpPath = Path::join(
            $fixture['vendorPath'],
            'oxid-esales',
            'oxideshop-demodata-ce',
            'src',
            'demodata.sql',
        );

        $this->assertFileExists($dumpPath);

        $this->createDao($fixture['context'])->checkPreconditions();

        $this->assertSame([], $this->fetchArticleIds());
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM oxuser'));
        $this->assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM oxcategories'));
    }

    public function testCheckPreconditionsRejectsExistingUsers(): void
    {
        $fixture = $this->createFixture();
        $this->insertUser();

        try {
            $this->createDao($fixture['context'])->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $this->assertCount(1, $aggregate->getExceptions());
            $this->assertInstanceOf(DemodataException::class, $aggregate->getExceptions()[0]);
            $this->assertSame(
                'There are some submitted users in the shop. Please delete them and their dependencies as well.',
                $aggregate->getExceptions()[0]->getMessage(),
            );
        }
    }

    public function testCheckPreconditionsRejectsExistingArticles(): void
    {
        $fixture = $this->createFixture();
        $this->insertArticle();

        try {
            $this->createDao($fixture['context'])->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $this->assertCount(1, $aggregate->getExceptions());
            $this->assertSame(
                'Please truncate the database table oxarticles.',
                $aggregate->getExceptions()[0]->getMessage(),
            );
        }
    }

    public function testCheckPreconditionsRejectsExistingCategories(): void
    {
        $fixture = $this->createFixture();
        $this->insertCategory();

        try {
            $this->createDao($fixture['context'])->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $this->assertCount(1, $aggregate->getExceptions());
            $this->assertSame(
                'Please truncate the database table oxcategories.',
                $aggregate->getExceptions()[0]->getMessage(),
            );
        }
    }

    public function testCheckPreconditionsCollectsAllThreeTableErrorsInOrder(): void
    {
        $fixture = $this->createFixture();
        $this->insertUser();
        $this->insertArticle();
        $this->insertCategory();

        try {
            $this->createDao($fixture['context'])->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $messages = array_map(
                static fn (\Throwable $exception): string => $exception->getMessage(),
                $aggregate->getExceptions(),
            );
            $this->assertCount(3, $messages);
            $this->assertSame(
                [
                    'There are some submitted users in the shop. Please delete them and their dependencies as well.',
                    'Please truncate the database table oxarticles.',
                    'Please truncate the database table oxcategories.',
                ],
                $messages,
            );
        }
    }

    public function testCheckPreconditionsReportsUnreadableDumpPath(): void
    {
        $fixture = $this->createFixture();
        $context = $fixture['context'];
        $context->setVendorPath(Path::join($fixture['root'], 'missing-vendor'));

        $expectedDump = Path::join(
            $context->getVendorPath(),
            'oxid-esales',
            'oxideshop-demodata-ce',
            'src',
            'demodata.sql',
        );
        $this->assertFileDoesNotExist($expectedDump);

        try {
            $this->createDao($context)->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $this->assertCount(1, $aggregate->getExceptions());
            $message = $aggregate->getExceptions()[0]->getMessage();
            $this->assertStringContainsString('Error reading Demodata SQL file', $message);
            $this->assertStringContainsString($expectedDump, $message);
        }
    }

    public function testCheckPreconditionsCombinesMissingDumpWithExistingUser(): void
    {
        $fixture = $this->createFixture();
        $this->insertUser();
        $context = $fixture['context'];
        $context->setVendorPath(Path::join($fixture['root'], 'missing-vendor'));

        try {
            $this->createDao($context)->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $messages = array_map(
                static fn (\Throwable $exception): string => $exception->getMessage(),
                $aggregate->getExceptions(),
            );
            $this->assertCount(2, $messages);
            $this->assertStringContainsString('submitted users', $messages[0]);
            $this->assertStringContainsString('Error reading Demodata SQL file', $messages[1]);
        }
    }

    #[DataProvider('editionPackageDirectoryProvider')]
    public function testCheckPreconditionsUsesEditionValueForPackagePath(Edition $edition, string $expectedDirectory): void
    {
        $factory = new DemodataFixtureFactory();
        $root = Path::join(sys_get_temp_dir(), 'demodata-installer-edition-' . bin2hex(random_bytes(4)));
        $vendorPath = Path::join($root, 'vendor');
        (new Filesystem())->mkdir($vendorPath);

        $context = new FakeBasicContext();
        $context->setVendorPath($vendorPath);
        $context->setOutPath(Path::join($root, 'out'));
        $context->setEdition($edition);
        $this->fixtureRoots[] = $root;

        try {
            $this->createDao($context)->checkPreconditions();
            $this->fail('Expected AggregateException was not thrown.');
        } catch (AggregateException $aggregate) {
            $message = $aggregate->getExceptions()[0]->getMessage();
            $this->assertStringContainsString($expectedDirectory, $message);
        }
    }

    /**
     * @return array<string, array{0: Edition, 1: string}>
     */
    public static function editionPackageDirectoryProvider(): array
    {
        return [
            'professional' => [Edition::Professional, 'oxideshop-demodata-pe'],
            'enterprise' => [Edition::Enterprise, 'oxideshop-demodata-ee'],
        ];
    }

    public function testApplyDemodataCopiesNestedOutFiles(): void
    {
        $fixture = $this->createFixture();
        $this->createDao($fixture['context'])->applyDemodata();

        $picture = Path::join($fixture['outPath'], 'pictures', 'item.txt');
        $nested = Path::join($fixture['outPath'], 'nested', 'other.txt');

        $this->assertFileExists($picture);
        $this->assertFileExists($nested);
        $this->assertSame('demo-picture', file_get_contents($picture));
        $this->assertSame('nested-demo', file_get_contents($nested));
    }

    public function testApplyDemodataInsertsEveryStatementInTheDump(): void
    {
        $fixture = $this->createFixture();
        $this->createDao($fixture['context'])->applyDemodata();

        $this->assertSame('First', $this->fetchArticleTitle('demo-1'));
        $this->assertSame('Second', $this->fetchArticleTitle('demo-2'));
        $this->assertSame([['shop_id' => 1, 'code' => 'de_DE']], $this->fetchLocales());
    }

    public function testApplyDemodataTruncatesExistingArticleRows(): void
    {
        $fixture = $this->createFixture();
        $this->insertArticle('stale-article', 'Stale');

        $this->createDao($fixture['context'])->applyDemodata();

        $this->assertSame(['demo-1', 'demo-2'], $this->fetchArticleIds());
    }

    public function testApplyDemodataTruncatesUnderscoredTables(): void
    {
        $fixture = $this->createFixture();
        $this->insertLocale(9, 'stale');

        $this->createDao($fixture['context'])->applyDemodata();

        $this->assertSame([['shop_id' => 1, 'code' => 'de_DE']], $this->fetchLocales());
    }

    public function testApplyDemodataIsIdempotent(): void
    {
        $fixture = $this->createFixture();
        $dao = $this->createDao($fixture['context']);

        $dao->applyDemodata();
        $dao->applyDemodata();

        $this->assertSame(['demo-1', 'demo-2'], $this->fetchArticleIds());
    }

    public function testApplyDemodataDoesNotCopyFilesWhenSqlFails(): void
    {
        $fixture = $this->createFixture("INSERT INTO `oxarticles` (`OXID`, `OXTITLE`) VALUES ('broken', 'Broken');\nNOT VALID SQL;");
        $destination = Path::join($fixture['outPath'], 'pictures', 'item.txt');

        try {
            $this->createDao($fixture['context'])->applyDemodata();
            $this->fail('Expected database exception was not thrown.');
        } catch (DbalException) {
            $this->assertFileDoesNotExist($destination);
        }
    }
}
