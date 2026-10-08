<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Database;

use Doctrine\DBAL\Query\QueryBuilder;

interface QueryBuilderFactoryInterface
{
    public function create(): QueryBuilder;
}
