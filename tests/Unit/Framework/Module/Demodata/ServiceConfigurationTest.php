<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Unit\Framework\Module\Demodata;

use PHPUnit\Framework\TestCase;

final class ServiceConfigurationTest extends TestCase
{
    public function testRootServicesYamlImportsFrameworkServices(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 5) . '/services.yaml');

        $this->assertIsString($contents);
        $this->assertStringContainsString('src/Framework/services.yaml', $contents);
    }

    public function testFrameworkServicesYamlImportsDemodataServices(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 5) . '/src/Framework/services.yaml');

        $this->assertIsString($contents);
        $this->assertStringContainsString('Module/Demodata/services.yaml', $contents);
    }

    public function testDemodataServicesRegisterCommandAndDao(): void
    {
        $contents = file_get_contents(
            dirname(__DIR__, 5) . '/src/Framework/Module/Demodata/services.yaml',
        );

        $this->assertIsString($contents);
        $this->assertStringContainsString(
            'OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDaoInterface',
            $contents,
        );
        $this->assertStringContainsString(
            'class: OxidEsales\DemoDataInstaller\Framework\Module\Demodata\DemodataDao',
            $contents,
        );
        $this->assertStringContainsString("command: 'oe:setup:demodata'", $contents);
    }
}
