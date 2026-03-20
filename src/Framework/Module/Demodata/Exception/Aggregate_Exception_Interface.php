<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception;

use Throwable;
interface Aggregate_Exception_Interface extends Throwable
{
    public function add(Throwable $exception): void;
    /**
     * @return (Throwable)[]
     */
    public function get_exceptions(): array;
    public function has_exceptions(): bool;
    /**
     * @param (Throwable)[] $exceptions
     */
    public static function throw_exceptions(array $exceptions): void;
}