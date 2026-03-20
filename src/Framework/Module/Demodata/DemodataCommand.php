<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata;

use Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception\Aggregate_Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
class Demodata_Command extends Command
{
    public function __construct(private readonly Demodata_Dao_Interface $demodata_dao)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->set_description('Performs installation of demodata for active shopversion');
    }
    protected function execute(Input_Interface $input, Output_Interface $output): int
    {
        $output->writeln('<info>Running precondition checks...</info>');
        try {
            $this->demodata_dao->check_preconditions();
            $output->writeln('<info>Applying demodata</info>');
            $this->demodata_dao->apply_demodata();
        } catch (Aggregate_Exception $aggregate_exception) {
            $message = 'We found problems which prevent the execution of the command, please fix them:';
            $output->writeln('<error>' . $message . '</error>');
            foreach ($aggregate_exception->get_exceptions() as $exception) {
                $output->writeln('<error> - ' . $exception->get_message() . '</error>');
            }
        }
        return 0;
    }
}