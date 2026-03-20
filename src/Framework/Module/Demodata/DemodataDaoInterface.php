<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata;

use Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception\Aggregate_Exception;
use Symfony\Component\Filesystem\Exception\Io_Exception;
interface Demodata_Dao_Interface
{
    /**
     * @throws AggregateException
     */
    public function check_preconditions(): void;
    /**
     * @throws IOException
     */
    public function apply_demodata(): void;
}