<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;

final class TestConnectionFactory implements ConnectionFactoryInterface
{
    private ?Connection $connection = null;

    public function create(): Connection
    {
        if ($this->connection === null) {
            $this->connection = DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => (int) (getenv('DB_PORT') ?: '3306'),
                'dbname' => getenv('DB_NAME') ?: 'demodata_test',
                'user' => getenv('DB_USER') ?: 'demodata',
                'password' => getenv('DB_PASSWORD') ?: 'demodata',
                'charset' => 'utf8mb4',
            ]);
        }

        return $this->connection;
    }
}
