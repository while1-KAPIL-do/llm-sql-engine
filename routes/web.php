<?php

use Illuminate\Support\Facades\Route;
use function Laravel\Ai\agent;
use App\Agents\SqlGeneratorAgent;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-ai', function () {

    $response = agent(
        instructions: 'You are a MySQL expert. Only return valid MySQL queries. No explanation.'
    )->prompt(
        'Get all users created today'
    );

    dd($response);

});

Route::get('/generate-sql', function () {

    $prompt = request('q');

    $agent = app(SqlGeneratorAgent::class);

    $response = $agent->generate($prompt);

    return response()->json(
        json_decode($response, true)
    );
});