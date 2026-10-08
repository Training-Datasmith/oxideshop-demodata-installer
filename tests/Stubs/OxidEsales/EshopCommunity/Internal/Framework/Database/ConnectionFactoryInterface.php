<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Framework\Database;

use Doctrine\DBAL\Connection;

interface ConnectionFactoryInterface
{
    public function create(): Connection;
}
