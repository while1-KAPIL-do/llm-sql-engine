<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL AI Voice Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #f8fafc; }
        .card { background: white; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">🎤 SQL AI Voice Assistant</h1>
            <p class="text-gray-600">Speak your question → Get instant SQL + Results</p>
        </div>

        <!-- Input Card -->
        <div class="card p-8 mb-10">
            <form action="{{ route('voice-to-sql') }}" method="POST" enctype="multipart/form-data" id="voiceForm">
                @csrf
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Voice Recording (mp3, wav)
                    </label>
                    <input type="file" name="audio" accept="audio/*" 
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer"
                           required>
                </div>
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition">
                    Send Voice Query
                </button>
            </form>
        </div>

        <!-- Result Section (Shows only after submission) -->
        @if(session('result'))
        <div class="card p-8" id="resultSection">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Result</h2>

            <!-- User Question -->
            <div class="mb-6">
                <p class="text-sm text-gray-500">You asked:</p>
                <p class="text-lg font-medium text-gray-800">"{{ session('result')['user_question'] }}"</p>
            </div>

            <!-- Generated SQL -->
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">Generated SQL:</p>
                <pre class="bg-gray-900 text-green-400 p-5 rounded-xl overflow-auto text-sm font-mono">
{{ session('result')['generated_sql'] }}
                </pre>
            </div>

            <!-- Status -->
            @if(session('result')['success'])
                <div class="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-xl mb-6">
                    ✅ Query executed successfully • {{ session('result')['row_count'] }} rows found
                </div>
            @else
                <div class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-xl mb-6">
                    ❌ Query failed • {{ session('result')['error'] }}
                </div>
            @endif

            <!-- Results Preview -->
            @if(session('result')['success'] && session('result')['row_count'] > 0)
                <div class="mb-6">
                    <p class="text-sm text-gray-500 mb-3">Results Preview (first 5 rows):</p>
                    <div class="overflow-auto max-h-96">
                        <pre class="bg-gray-50 p-5 rounded-xl text-sm">{{ json_encode(session('result')['results_preview'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @elseif(session('result')['row_count'] == 0)
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-6 py-4 rounded-xl">
                    No records found for this query.
                </div>
            @endif

            <!-- Spoken Summary -->
            <div class="mt-8 pt-6 border-t">
                <p class="text-sm text-gray-500 mb-2">Spoken Summary:</p>
                <p class="text-gray-700 italic">"{{ session('result')['spoken_summary'] }}"</p>
            </div>
        </div>
        @endif
    </div>
</body>
</html>