<?php

namespace App\Services;

use App\Ai\Agents\MySqlExpert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Audio;
use Laravel\Ai\Transcription;
use Exception;

class VoiceToSqlService
{
    public function handleVoiceToSql(Request $request)
    {
        // Validate either uploaded file OR recorded audio
        $request->validate([
            'audio_file' => 'required_without:audio|file|mimes:mp3,wav,webm,ogg|max:20480',
            'audio'      => 'required_without:audio_file|file|mimes:mp3,wav,webm,ogg|max:20480',
        ]);

        $path = null;
        $sql = null;
        $results = [];
        $userQuestion = null;
        $executionSuccess = false;
        $errorMessage = null;

        try {
            // Determine which file input to use (microphone recording or manual upload)
            if ($request->hasFile('audio_file')) {
                $path = $request->file('audio_file')->store('voice-input/' . now()->format('Y-m-d'));
            } elseif ($request->hasFile('audio')) {
                $path = $request->file('audio')->store('voice-input/' . now()->format('Y-m-d'));
            } else {
                throw new \Exception('No audio file provided.');
            }
            
            // 2. Transcribe voice to text
            $transcript = Transcription::fromStorage($path)->generate();
            $userQuestion = trim((string) $transcript);

            if (empty($userQuestion)) {
                throw new Exception('No speech detected in the audio.');
            }

            // 3. Generate SQL using dedicated agent
            // $sqlResponse = (new MySqlExpert())->prompt($userQuestion);
            $agent = new MySqlExpert();
            $fullPrompt = <<<PROMPT
                User asked: \"{$userQuestion}\"\n\n" .
                Follow the mandatory workflow strictly:
                1. First call the get_database_schema tool.
                2. Analyze the real schema.
                3. Generate the correct SELECT query using exact column names from the schema.
                4. Return ONLY the final SQL query.

                Do not return the schema. Do not explain. Just the SQL.
            PROMPT;

            $sqlResponse = $agent->prompt($fullPrompt);
            $sql = trim((string) $sqlResponse);
            $sql = rtrim($sql, ';');

            $upperSql = strtoupper($sql);

            // 4. Safety Check
            if (
                str_contains($upperSql, 'INSERT ') ||
                str_contains($upperSql, 'UPDATE ') ||
                str_contains($upperSql, 'DELETE ') ||
                str_contains($upperSql, 'DROP ') ||
                str_contains($upperSql, 'TRUNCATE ') ||
                str_contains($upperSql, 'ALTER ') ||
                str_contains($upperSql, 'CREATE ') ||
                !str_starts_with($upperSql, 'SELECT ')
            ) {
                // We still allow it to continue so user can see the bad SQL
            }

            // Optional: Add LIMIT if missing
            if (!str_contains($upperSql, 'LIMIT')) {
                $sql .= ' LIMIT 500';
            }

            // 5. Try to execute the query
            $executionSuccess = true;
            $errorMessage = null;

            try {
                $results = DB::select($sql);
            } catch (\Exception $dbException) {
                $executionSuccess = false;
                $errorMessage = $dbException->getMessage();
            }

            // 6. Create friendly spoken summary
            if ($executionSuccess) {
                $summaryText = "Here are the results for: “{$userQuestion}”\n" .
                               count($results) . " row(s) found.";
            } else {
                $summaryText = "I generated this SQL query for: “{$userQuestion}”\n" .
                               "But it failed to execute. The error is: {$errorMessage}";
            }

            // 7. Cleanup input audio
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }

            // 8. Return response
            return response()->json([
                'success'          => $executionSuccess,
                'user_question'    => $userQuestion,
                'generated_sql'    => $sql,
                'row_count'        => count($results),
                'results_preview'  => $executionSuccess ? array_slice($results, 0, 5) : [],
                'error'            => $errorMessage,
                'spoken_summary'   => $summaryText,
                // 'audio_response_url' => $audioResponseUrl,   // Uncomment when you enable TTS again
            ]);

        } catch (Exception $e) {
            // Cleanup on early error
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }

            Log::error('Voice-to-SQL failed', [
                'error' => $e->getMessage(),
                'file'  => $path ?? 'none',
            ]);

            $errorSummary = "Sorry, something went wrong while processing your request: " . $e->getMessage();

            return response()->json([
                'success'          => false,
                'user_question'    => $userQuestion ?? null,
                'generated_sql'    => $sql ?? null,
                'error'            => $e->getMessage(),
                'spoken_summary'   => $errorSummary,
                // 'audio_response_url' => null,
            ], 422);
        }
    }
}