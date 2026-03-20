<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
declare (strict_types=1);
namespace Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata;

use Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception\Aggregate_Exception;
use Oxid_Esales\Demo_Data_Installer\Framework\Module\Demodata\Exception\Demodata_Exception;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Connection_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Framework\Database\Query_Builder_Factory_Interface;
use Oxid_Esales\Eshop_Community\Internal\Transition\Utility\Basic_Context_Interface;
use function sprintf;
use function strtolower;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
class Demodata_Dao implements Demodata_Dao_Interface
{
    public function __construct(private readonly Connection_Factory_Interface $connection_factory, private readonly Query_Builder_Factory_Interface $query_builder_factory, private readonly Basic_Context_Interface $basic_context, private readonly Filesystem $filesystem)
    {
    }
    public function check_preconditions(): void
    {
        $messages = [];
        if ($this->has_records('oxuser')) {
            $messages[] = 'There are some submitted users in the shop. Please delete them and their dependencies as well.';
        }
        if ($this->has_records('oxarticles')) {
            $messages[] = 'Please truncate the database table oxarticles.';
        }
        if ($this->has_records('oxcategories')) {
            $messages[] = 'Please truncate the database table oxcategories.';
        }
        if (!is_readable($this->get_demodata_sql_dump())) {
            $messages[] = sprintf('Error reading Demodata SQL file (%s). ' . 'Please make sure that demodata is available and file is readable.', $this->get_demodata_sql_dump());
        }
        if ($messages) {
            $aggregate_exception = new Aggregate_Exception();
            foreach ($messages as $message) {
                $aggregate_exception->add(new Demodata_Exception($message));
            }
            throw $aggregate_exception;
        }
    }
    public function apply_demodata(): void
    {
        $this->run_sql();
        $this->copy_out_files();
    }
    private function get_demodata_sql_dump(): string
    {
        return Path::join($this->get_demodata_source_path(), 'demodata.sql');
    }
    private function run_sql(): void
    {
        $db_connection = $this->connection_factory->create();
        $queries = file_get_contents($this->get_demodata_sql_dump());
        $tables = [];
        preg_match_all('/INSERT INTO `([a-z\d]*)` .*/m', $queries, $tables);
        $platform = $db_connection->get_database_platform();
        foreach ($tables[1] as $table_to_truncate) {
            $db_connection->execute_statement($platform->get_truncate_table_sql($table_to_truncate, true));
        }
        $db_connection->execute_statement($queries);
    }
    private function copy_out_files(): void
    {
        $this->filesystem->mirror(Path::join($this->get_demodata_source_path(), 'out'), $this->basic_context->get_out_path());
    }
    private function get_demodata_source_path(): string
    {
        return Path::join($this->basic_context->get_vendor_path(), $this->basic_context->get_composer_vendor_name(), sprintf('oxideshop-demodata-%s', strtolower((string) $this->basic_context->get_edition()->value)), 'src');
    }
    private function has_records(string $table): bool
    {
        return (bool) $this->query_builder_factory->create()->select('count(*) as count')->from($table)->fetch_one();
    }
}