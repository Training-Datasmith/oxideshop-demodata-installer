<?php

declare(strict_types=1);

namespace OxidEsales\DemoDataInstaller\Tests\Unit\Framework\Module\Demodata\Exception;

use Exception;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateException;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\AggregateExceptionInterface;
use OxidEsales\DemoDataInstaller\Framework\Module\Demodata\Exception\DemodataException;
use PHPUnit\Framework\TestCase;

final class AggregateExceptionTest extends TestCase
{
    public function testFreshInstanceHasNoExceptions(): void
    {
        $aggregate = new AggregateException();

        $this->assertFalse($aggregate->hasExceptions());
        $this->assertSame([], $aggregate->getExceptions());
    }

    public function testAddStoresExceptionsInOrderAndExposesTheirMessages(): void
    {
        $first = new DemodataException('first problem');
        $second = new DemodataException('second problem');
        $aggregate = new AggregateException();

        $aggregate->add($first);
        $aggregate->add($second);

        $this->assertTrue($aggregate->hasExceptions());
        $this->assertSame([$first, $second], $aggregate->getExceptions());
        $this->assertStringContainsString('first problem', $aggregate->getMessage());
        $this->assertStringContainsString('second problem', $aggregate->getMessage());
    }

    public function testConstructorSeedsFromIterable(): void
    {
        $first = new DemodataException('alpha');
        $second = new Exception('beta');
        $aggregate = new AggregateException([$first, $second]);

        $this->assertSame([$first, $second], $aggregate->getExceptions());
    }

    public function testThrowExceptionsWithEmptyListReturns(): void
    {
        $called = false;
        AggregateException::throwExceptions([]);
        $called = true;

        $this->assertTrue($called);
    }

    public function testThrowExceptionsWithSingleExceptionRethrowsSameInstance(): void
    {
        $exception = new DemodataException('only one');

        try {
            AggregateException::throwExceptions([$exception]);
            $this->fail('Expected exception was not thrown.');
        } catch (DemodataException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function testThrowExceptionsWithMultipleExceptionsBuildsAggregate(): void
    {
        $first = new DemodataException('one');
        $second = new DemodataException('two');

        try {
            AggregateException::throwExceptions([$first, $second]);
            $this->fail('Expected aggregate exception was not thrown.');
        } catch (AggregateException $aggregate) {
            $this->assertSame([$first, $second], $aggregate->getExceptions());
        }
    }

    public function testClassImplementsAggregateExceptionInterface(): void
    {
        $aggregate = new AggregateException();

        $this->assertInstanceOf(AggregateExceptionInterface::class, $aggregate);
    }
}
