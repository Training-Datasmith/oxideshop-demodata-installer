<?php

declare(strict_types=1);

namespace OxidEsales\EshopCommunity\Internal\Transition\Utility;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;

interface BasicContextInterface
{
    public function getComposerVendorName(): string;

    public function getEdition(): Edition;

    public function getOutPath(): string;

    public function getVendorPath(): string;
}
