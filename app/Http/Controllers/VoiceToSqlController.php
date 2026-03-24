<?php

namespace App\Http\Controllers;

use App\Services\VoiceToSqlService;
use Illuminate\Http\Request;

class VoiceToSqlController extends Controller
{
    protected $voiceToSqlService;

    public function __construct(VoiceToSqlService $voiceToSqlService)
    {
        $this->voiceToSqlService = $voiceToSqlService;
    }

    public function process(Request $request)
    {
        $response = $this->voiceToSqlService->handleVoiceToSql($request);

        // Convert JSON response to session so Blade can read it
        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 422) {
            $data = $response->getData(true);
            return redirect()->back()->with('result', $data);
        }

        return $response;
    }

}