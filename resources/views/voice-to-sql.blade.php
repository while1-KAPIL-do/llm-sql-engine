<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL AI Voice Assistant</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(to bottom right, #f1f5f9, #eef2ff);
        }

        .card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .mic-btn {
            transition: all 0.3s ease;
        }

        .mic-btn:hover {
            transform: translateY(-2px) scale(1.03);
        }

        .mic-btn.recording {
            animation: pulse 1.5s infinite;
            background-color: #ef4444 !important;
            color: white;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="min-h-screen py-12">

    <div class="max-w-4xl mx-auto px-4">

        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-3">
                🎤 SQL AI Voice Assistant
            </h1>
            <p class="text-gray-600 text-lg">Speak naturally or upload a voice file</p>
        </div>

        <!-- Input Card -->
        <div class="card p-10 fade-in">
            <h2 class="text-2xl font-semibold text-gray-800 mb-8 text-center">
                How do you want to ask?
            </h2>

            <form action="{{ route('voice-to-sql') }}" method="POST" enctype="multipart/form-data" id="voiceForm">
                @csrf

                <!-- Microphone Section -->
                <div class="mb-12 text-center">

                    <label class="block text-sm font-medium text-gray-700 mb-5">
                        🎤 Speak Your Query (Recommended)
                    </label>

                    <div class="flex flex-col items-center gap-5">

                        <button type="button" id="recordBtn"
                            class="mic-btn w-28 h-28 flex items-center justify-center bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-3xl text-5xl shadow-2xl">
                            <i class="fas fa-microphone"></i>
                        </button>

                        <p id="recordingStatus" class="text-sm font-medium text-red-500 hidden">
                            🔴 Recording... Click again to stop
                        </p>

                    </div>

                </div>

                <!-- Divider -->
                <div class="relative my-10">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-5 text-sm text-gray-400">OR</span>
                    </div>
                </div>

                <!-- File Upload -->
                <div class="mb-8">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        📁 Upload MP3 or WAV File
                    </label>

                    <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-blue-400 transition">
                        <input type="file" name="audio_file" id="audioFile" accept="audio/*"
                            class="w-full text-sm text-gray-500 cursor-pointer
                            file:mr-4 file:py-3 file:px-6 file:rounded-xl
                            file:border-0 file:text-sm file:font-medium
                            file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-400 mt-2">Supported: MP3, WAV, WebM</p>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="submitBtn"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-5 rounded-2xl transition text-lg shadow-lg hover:shadow-xl active:scale-[0.98] disabled:opacity-70">
                    Send to AI
                </button>
            </form>
        </div>

        <!-- Result Section -->
        @if(session('result'))
        <div class="card p-10 mt-10 fade-in" id="resultSection">

            <h2 class="text-2xl font-semibold text-gray-800 mb-6">Result</h2>

            <div class="mb-6">
                <p class="text-sm text-gray-500">You asked:</p>
                <p class="text-lg font-medium text-gray-800 mt-1">
                    "{{ session('result')['user_question'] }}"
                </p>
            </div>

            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-2">Generated SQL:</p>
                <pre class="bg-gray-900 text-green-400 p-5 rounded-xl overflow-auto text-sm font-mono shadow-inner">
{{ session('result')['generated_sql'] }}
                </pre>
            </div>

            @if(session('result')['success'])
            <div class="bg-green-50 border border-green-300 text-green-700 px-6 py-4 rounded-xl mb-6">
                ✅ Query executed successfully • {{ session('result')['row_count'] }} rows found
            </div>
            @else
            <div class="bg-red-50 border border-red-300 text-red-700 px-6 py-4 rounded-xl mb-6">
                ❌ Query failed • {{ session('result')['error'] }}
            </div>
            @endif

            @if(session('result')['success'] && session('result')['row_count'] > 0)
            <div class="mb-6">
                <p class="text-sm text-gray-500 mb-3">Results Preview:</p>
                <pre class="bg-gray-50 p-5 rounded-xl text-sm overflow-auto border">
{{ json_encode(session('result')['results_preview'], JSON_PRETTY_PRINT) }}
                </pre>
            </div>
            @elseif(session('result')['row_count'] == 0)
            <div class="bg-yellow-50 border border-yellow-300 text-yellow-700 px-6 py-4 rounded-xl">
                No records found.
            </div>
            @endif

            <div class="mt-8 pt-6 border-t">
                <p class="text-sm text-gray-500 mb-2">Spoken Summary:</p>
                <p class="text-gray-700 italic">
                    "{{ session('result')['spoken_summary'] }}"
                </p>
            </div>

        </div>
        @endif
    </div>

    <!-- JS (UNCHANGED) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const recordBtn = document.getElementById('recordBtn');
            const recordingStatus = document.getElementById('recordingStatus');
            const submitBtn = document.getElementById('submitBtn');
            const voiceForm = document.getElementById('voiceForm');
            const audioFileInput = document.getElementById('audioFile');

            let mediaRecorder;
            let audioChunks = [];
            let isRecording = false;

            if (!recordBtn) return;

            recordBtn.addEventListener('click', async () => {
                if (!isRecording) {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];

                        mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

                        mediaRecorder.onstop = () => {
                            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                            const file = new File([audioBlob], "voice-query.webm", { type: "audio/webm" });

                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            audioFileInput.files = dataTransfer.files;

                            recordingStatus.classList.add('hidden');
                            recordBtn.classList.remove('bg-red-500', 'animate-pulse');
                            recordBtn.classList.add('bg-green-500');
                            recordBtn.innerHTML = '✅';
                        };

                        mediaRecorder.start();
                        isRecording = true;
                        recordingStatus.classList.remove('hidden');
                        recordBtn.classList.add('bg-red-500', 'animate-pulse');
                        recordBtn.innerHTML = '⏹️';

                    } catch (err) {
                        alert("Microphone access denied or not available.");
                    }
                } else {
                    if (mediaRecorder) mediaRecorder.stop();
                    isRecording = false;
                }
            });

            voiceForm.addEventListener('submit', () => {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Processing... Please wait';
            });
        });
    </script>

</body>
</html>