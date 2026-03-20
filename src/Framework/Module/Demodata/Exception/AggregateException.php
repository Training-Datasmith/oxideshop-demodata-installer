<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception;

use function assert;
use function count;
use Exception;
use function reset;
use Throwable;
class Aggregate_Exception extends Exception implements Aggregate_Exception_Interface
{
    /** @var (Throwable)[] */
    private array $exceptions = [];
    /**
     * @param (Throwable)[] $exceptions
     */
    public function __construct(iterable $exceptions = [])
    {
        parent::__construct('Many exceptions have be thrown:');
        foreach ($exceptions as $exception) {
            $this->add($exception);
        }
    }
    public function add(Throwable $exception): void
    {
        $this->exceptions[] = $exception;
        $this->message .= "\n" . $exception->get_message();
    }
    /**
     * @return (Throwable)[]
     */
    public function get_exceptions(): array
    {
        return $this->exceptions;
    }
    public function has_exceptions(): bool
    {
        return !empty($this->exceptions);
    }
    /**
     * @param (Throwable)[] $exceptions
     */
    public static function throw_exceptions(array $exceptions): void
    {
        $count = count($exceptions);
        if ($count === 0) {
            return;
        }
        if ($count === 1) {
            $exception = reset($exceptions);
            assert($exception instanceof Throwable);
            throw $exception;
        }
        throw new self($exceptions);
    }
}