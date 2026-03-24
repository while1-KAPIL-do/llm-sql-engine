<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DescribeTable implements Tool
{
    public function name(): string { return 'describe_table'; }

    public function description(): string 
    { 
        return 'Get detailed column information for one specific table.'; 
    }

    public function schema(): array 
    {
        return [
            'table' => $this->string()->required()->description('Name of the table, e.g. users'),
        ];
    }

    public function handle(array $input): string 
    {
        $table = $input['table'];
        $columns = DB::select("DESCRIBE `{$table}`");

        $output = "Table: `{$table}`\nColumns:\n";
        foreach ($columns as $col) {
            $output .= "  `{$col->Field}` ({$col->Type})";
            if ($col->Key === 'PRI') $output .= " [PK]";
            $output .= "\n";
        }
        return $output;
    }
}
