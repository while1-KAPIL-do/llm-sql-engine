<?php

namespace App\Ai\Tools;

use Laravel\Ai\Contracts\Tool;
use Illuminate\Support\Facades\DB;

class GetDatabaseSchema implements Tool
{
    public function name(): string
    {
        return 'get_database_schema';
    }

    public function description(): string
    {
        return 'Returns the complete list of all tables and their columns with data types from the current database. Use this when you need to know the exact schema before writing a SQL query.';
    }

    public function schema(): array
    {
        return []; // No input parameters needed
    }

    public function handle(array $input): string
    {   
        $output = "=== REAL DATABASE SCHEMA ===\n";
        $output .= "Database: default_db (or sql_ai as per your error)\n\n";

        $tables = DB::select("SHOW TABLES");

        foreach ($tables as $tableRow) {
            $tableName = array_values((array)$tableRow)[0];
            $output .= "TABLE: `{$tableName}`\n";

            $columns = DB::select("DESCRIBE `{$tableName}`");

            foreach ($columns as $col) {
                $extra = $col->Key === 'PRI' ? ' [PRIMARY KEY]' : '';
                $output .= "   `{$col->Field}`  {$col->Type}{$extra}\n";
            }
            $output .= "\n";
        }

        $output .= "Use only the columns shown above. Do not guess any column names.\n";
        return $output;
    }
}