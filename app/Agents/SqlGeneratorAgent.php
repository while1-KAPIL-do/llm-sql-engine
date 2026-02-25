<?php

namespace App\Agents;

use function Laravel\Ai\agent;

class SqlGeneratorAgent
{
    protected string $schema;

    public function __construct()
    {
        $this->schema = "
        Tables:

        users:
        - id (bigint)
        - name (varchar)
        - email (varchar)
        - created_at (timestamp)

        investments:
        - id (bigint)
        - user_id (bigint)
        - amount (decimal)
        - created_at (timestamp)
        ";
    }

    public function generate(string $userPrompt)
    {
        return agent(
            instructions: "
            You are a senior MySQL engineer.

            Use ONLY the provided database schema.

            Return STRICT JSON format:

            {
                \"query\": \"valid mysql query\",
                \"tables_used\": [\"table1\", \"table2\"],
                \"explanation\": \"short explanation\"
            }

            Do not return markdown.
            Do not return text outside JSON.

            Database Schema:
            {$this->schema}
            "
        )->prompt($userPrompt);
    }
}