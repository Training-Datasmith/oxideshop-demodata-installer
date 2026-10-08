<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Support;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContextInterface;

final class FakeBasicContext implements BasicContextInterface
{
    private string $vendorPath = '';

    private string $outPath = '';

    private Edition $edition = Edition::Community;

    private string $composerVendorName = 'oxid-esales';

    public function setVendorPath(string $vendorPath): void
    {
        $this->vendorPath = $vendorPath;
    }

    public function setOutPath(string $outPath): void
    {
        $this->outPath = $outPath;
    }

    public function setEdition(Edition $edition): void
    {
        $this->edition = $edition;
    }

    public function setComposerVendorName(string $composerVendorName): void
    {
        $this->composerVendorName = $composerVendorName;
    }

    public function getComposerVendorName(): string
    {
        return $this->composerVendorName;
    }

    public function getEdition(): Edition
    {
        return $this->edition;
    }

    public function getOutPath(): string
    {
        return $this->outPath;
    }

    public function getVendorPath(): string
    {
        return $this->vendorPath;
    }
}
