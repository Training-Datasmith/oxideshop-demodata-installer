<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Support;

use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

final class TestQueryBuilderFactory implements QueryBuilderFactoryInterface
{
    public function __construct(
        private readonly ConnectionFactoryInterface $connectionFactory,
    ) {
    }

    public function create(): QueryBuilder
    {
        return $this->connectionFactory->create()->createQueryBuilder();
    }
}
