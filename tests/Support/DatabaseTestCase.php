<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Support;

use Doctrine\DBAL\Connection;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class DatabaseTestCase extends TestCase
{
    protected ConnectionFactoryInterface $connectionFactory;

    protected QueryBuilderFactoryInterface $queryBuilderFactory;

    protected Connection $connection;

    protected DemodataFixtureFactory $fixtureFactory;

    /** @var list<string> */
    protected array $fixtureRoots = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionFactory = new TestConnectionFactory();
        $this->queryBuilderFactory = new TestQueryBuilderFactory($this->connectionFactory);
        $this->connection = $this->connectionFactory->create();
        $this->fixtureFactory = new DemodataFixtureFactory();
        $this->ensureSchema();
        $this->truncateTables();
    }

    protected function tearDown(): void
    {
        foreach ($this->fixtureRoots as $root) {
            $this->fixtureFactory->removeTree($root);
        }
        $this->fixtureRoots = [];

        parent::tearDown();
    }

    protected function createDao(FakeBasicContext $context): DemodataDao
    {
        return new DemodataDao(
            $this->connectionFactory,
            $this->queryBuilderFactory,
            $context,
            new Filesystem(),
        );
    }

    /**
     * @return array{vendorPath: string, outPath: string, context: FakeBasicContext, root: string}
     */
    protected function createFixture(
        ?string $sqlContents = null,
        bool $withOutDirectory = true,
    ): array {
        $fixture = $this->fixtureFactory->createFixtureTree(sqlContents: $sqlContents, withOutDirectory: $withOutDirectory);
        $this->fixtureRoots[] = $fixture['root'];

        return $fixture;
    }

    protected function insertUser(string $oxid = 'test-user'): void
    {
        $this->connection->executeStatement(
            'INSERT INTO oxuser (OXID) VALUES (:oxid)',
            ['oxid' => $oxid],
        );
    }

    protected function insertArticle(string $oxid = 'test-article', string $title = 'Stale'): void
    {
        $this->connection->executeStatement(
            'INSERT INTO oxarticles (OXID, OXTITLE) VALUES (:oxid, :title)',
            ['oxid' => $oxid, 'title' => $title],
        );
    }

    protected function insertCategory(string $oxid = 'test-category'): void
    {
        $this->connection->executeStatement(
            'INSERT INTO oxcategories (OXID) VALUES (:oxid)',
            ['oxid' => $oxid],
        );
    }

    protected function insertLocale(int $shopId, string $code): void
    {
        $this->connection->executeStatement(
            'INSERT INTO oxshop_locales (shop_id, code) VALUES (:shop_id, :code)',
            ['shop_id' => $shopId, 'code' => $code],
        );
    }

    /**
     * @return list<string>
     */
    protected function fetchArticleIds(): array
    {
        $rows = $this->connection->fetchFirstColumn('SELECT OXID FROM oxarticles ORDER BY OXID');

        return array_map('strval', $rows);
    }

    protected function fetchArticleTitle(string $oxid): ?string
    {
        $title = $this->connection->fetchOne(
            'SELECT OXTITLE FROM oxarticles WHERE OXID = :oxid',
            ['oxid' => $oxid],
        );

        return $title === false ? null : (string) $title;
    }

    /**
     * @return list<array{shop_id: int, code: string}>
     */
    protected function fetchLocales(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT shop_id, code FROM oxshop_locales ORDER BY shop_id, code',
        );

        return array_map(
            static fn (array $row): array => [
                'shop_id' => (int) $row['shop_id'],
                'code' => (string) $row['code'],
            ],
            $rows,
        );
    }

    private function ensureSchema(): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS oxuser (OXID VARCHAR(32) NOT NULL PRIMARY KEY)',
        );
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS oxarticles (OXID VARCHAR(32) NOT NULL PRIMARY KEY, OXTITLE VARCHAR(255) NOT NULL)',
        );
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS oxcategories (OXID VARCHAR(32) NOT NULL PRIMARY KEY)',
        );
        $this->connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS oxshop_locales (shop_id INT NOT NULL, code VARCHAR(16) NOT NULL, PRIMARY KEY (shop_id, code))',
        );
    }

    private function truncateTables(): void
    {
        foreach (['oxshop_locales', 'oxarticles', 'oxcategories', 'oxuser'] as $table) {
            $this->connection->executeStatement('TRUNCATE TABLE ' . $table);
        }
    }
}
