<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Support;

use OxidEsales\EshopCommunity\Internal\Framework\Edition\Edition;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final class DemodataFixtureFactory
{
    public function __construct(
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @return array{vendorPath: string, outPath: string, context: FakeBasicContext}
     */
    public function createFixtureTree(
        Edition $edition = Edition::Community,
        ?string $sqlContents = null,
        bool $withOutDirectory = true,
    ): array {
        $root = Path::join(sys_get_temp_dir(), 'demodata-installer-' . bin2hex(random_bytes(8)));
        $vendorPath = Path::join($root, 'vendor');
        $packageDir = Path::join(
            $vendorPath,
            'oxid-esales',
            'oxideshop-demodata-' . strtolower($edition->value),
            'src',
        );
        $outPath = Path::join($root, 'shop-out');

        $this->filesystem->mkdir($packageDir);
        $this->filesystem->mkdir($outPath);

        $sql = $sqlContents ?? $this->defaultSql();
        $this->filesystem->dumpFile(Path::join($packageDir, 'demodata.sql'), $sql);

        if ($withOutDirectory) {
            $this->filesystem->dumpFile(
                Path::join($packageDir, 'out', 'pictures', 'item.txt'),
                'demo-picture',
            );
            $this->filesystem->dumpFile(
                Path::join($packageDir, 'out', 'nested', 'other.txt'),
                'nested-demo',
            );
        }

        $context = new FakeBasicContext();
        $context->setVendorPath($vendorPath);
        $context->setOutPath($outPath);
        $context->setEdition($edition);

        return [
            'vendorPath' => $vendorPath,
            'outPath' => $outPath,
            'context' => $context,
            'root' => $root,
        ];
    }

    public function removeTree(string $root): void
    {
        if ($this->filesystem->exists($root)) {
            $this->filesystem->remove($root);
        }
    }

    public function defaultSql(): string
    {
        return <<<'SQL'
INSERT INTO `oxarticles` (`OXID`, `OXTITLE`) VALUES ('demo-1', 'First');
INSERT INTO `oxarticles` (`OXID`, `OXTITLE`) VALUES ('demo-2', 'Second');
INSERT INTO `oxshop_locales` (`shop_id`, `code`) VALUES (1, 'de_DE');
SQL;
    }
}
