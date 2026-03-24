<?php

use Illuminate\Support\Facades\Route;
use function Laravel\Ai\agent;
use App\Agents\SqlGeneratorAgent;
use App\Ai\Agents\MySqlExpert;
use Laravel\Ai\Transcription;
use Laravel\Ai\Audio;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

use App\Http\Controllers\VoiceToSqlController;

Route::post('/voice-to-sql', [VoiceToSqlController::class, 'process'])->name('voice-to-sql');
Route::get('/voice-to-sql-view', function () {
    return view('voice-to-sql');
});

Route::get('/test-users', function () {
    $users = DB::table('users')
                ->select('id', 'name', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

                dd($users->toArray());
});

Route::get('/', function () {
    return view('welcome');
});


///////////////////////////////// TEST ROUTES /////////////////////////////////

Route::get('/test-sql-agent', function () {
    $response = (new MySqlExpert)->prompt('Get all users created today');
    dd($response);
});

// ELEVEN-LABS TTS
Route::get('/test-tts', function () {
    $audio = Audio::of("Get all users created today")
        ->voice('alloy')  // One of ElevenLabs' default voices; check their docs for more
        ->instructions('Speak in a warm, supportive, and clear tone like a helpful friend.')        
        ->generate()
        ->storeAs('sql_all_users_created_today.mp3');  // Saves to storage/app/public/test.mp3

    return response()->json(['audio_url' => Storage::url('sql_all_users_created_today.mp3')]);
});

Route::get('/test-stt', function (Request $request) {
    // 1. Validate the uploaded audio file
    // $request->validate([
    //     'audio' => 'required|file|mimes:mp3,wav,webm,ogg|max:10240', // max 10MB - adjust as needed
    // ]);

    try {
        // 2. Store the uploaded file temporarily
        // Better to use a dynamic name + original extension
        // $path = $request->file('audio')->store('voice-inputs'); // stores in storage/app/voice-inputs
        // $fullPath = Storage::path($path); // if fromPath() is needed (see notes)

        // 3. Transcribe using Laravel AI SDK
        // Most reliable & clean way: Transcription::fromUpload()
        // $transcript = Transcription::fromUpload(Storage::url('test.mp3'))
        //     ->generate();  // Uses your default STT provider (likely OpenAI Whisper)

        $transcript = Transcription::fromStorage('sql_q1.mp3')  // relative path in storage
            ->diarize()  // optional
            ->generate();

        // Optional: Add speaker diarization if multiple people might speak
        // $transcript = Transcription::fromUpload($request->file('audio'))
        //     ->diarize()
        //     ->generate();

        // 4. Optional: Clean up the file after transcription
        Storage::delete($path);

        // 5. Return the result
        return response()->json([
            'success'    => true,
            'transcript' => (string) $transcript,           // plain text
            'raw'        => $transcript->toArray(),         // if you want segments/timestamps
            // 'segments' => $transcript->segments ?? [],   // if diarize() used
        ]);

    } catch (\Exception $e) {
        // Log for debugging
        Log::error('Transcription failed: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => 'Transcription failed: ' . $e->getMessage(),
        ], 500);
    }
})->name('voice-to-text');

Route::get('/mp3-to-sql', function (Request $request) {
    
    // Transcribe voice to text
    $transcript = Transcription::fromStorage('sql_q1.mp3')
        ->diarize() // Optional: Detect speakers if multi-person
        ->generate();

    // Prompt the dedicated SQL agent with transcribed text
    $sqlResponse = (new MySqlExpert)->prompt((string) $transcript);

    // Optional: Synthesize response as voice (e.g., read back the SQL)
    $audioResponse = \Laravel\Ai\Audio::of((string) $sqlResponse)
        ->female() // Or ->male()
        ->instructions('Speak clearly and slowly') // Custom style
        ->generate()
        ->storeAs('response.mp3'); // Save audio file

    return response()->json([
        'transcript' => (string) $transcript,
        'sql_query' => (string) $sqlResponse,
        'audio_response_url' => Storage::url($audioResponse), // URL to synthesized audio
    ]);
});