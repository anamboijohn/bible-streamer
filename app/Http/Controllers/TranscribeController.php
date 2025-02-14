<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranscribeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranscribeController extends Controller
{
    public function show(TranscribeRequest $request)
    {
        $audio = $request->file('audio');
        $audioName = $audio->getClientOriginalName();
        $audioStream = fopen($audio->getPathname(), 'r');

        // Send the audio file for transcription
        $apiUrl = 'https://api.groq.com/openai/v1/audio/transcriptions';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
        ])->attach('file', $audioStream, $audioName)
            ->post($apiUrl, [
                'model' => 'whisper-large-v3-turbo',
                'language' => 'en',
                'temperature' => '0',
                'response_format' => 'json',
            ]);

        fclose($audioStream);

        return response()->json(['transcription' => $response->json('text')]);
    }
}
