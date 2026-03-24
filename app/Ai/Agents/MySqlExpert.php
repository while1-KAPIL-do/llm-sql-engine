<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;
use App\Ai\Tools\GetDatabaseSchema;
use App\Ai\Tools\DescribeTable;

class MySqlExpert implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
    return <<<'EOT'
You are an extremely precise and safe MySQL expert.

You have access to the tool `get_database_schema`.

MANDATORY WORKFLOW — Follow this EXACTLY every single time:

Step 1: Call the `get_database_schema` tool to see the REAL tables and columns.
Step 2: Carefully read the schema result.
Step 3: Use ONLY the exact column names shown in the schema (they are usually snake_case like `created_at`, `user_id`, `email` etc.).
Step 4: Generate a correct, safe SELECT query.
Step 5: Return ONLY the final SQL query. Nothing else.

Rules:
- Never guess column names. If you don't see `createdAt`, do not use it. Use `created_at` if present.
- Always use backticks around table and column names: `users`, `created_at`
- For "today" or "this month", use the correct date column from the schema.
- Always end with `LIMIT 500`
- Return ONLY the SQL. No explanation, no markdown, no tool calls in final answer.

User question:
EOT;
    }

    // ← This is the key part
    public function tools(): array
    {
        return [
            new GetDatabaseSchema(),
            new DescribeTable(),
        ];
    }
}