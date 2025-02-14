<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranscribeRequest;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class TranscribeController extends Controller
{
    public function show(TranscribeRequest $request)
    {
        $audio = $request->file('audio');
        $audioName = $audio->getClientOriginalName();
        $audioPath = $audio->getPathname();

        // Convert to MP3 and save
        $mp3Path = storage_path('app/public/' . pathinfo($audioName, PATHINFO_FILENAME) . '.mp3');
        $ffmpeg = FFMpeg::create();
        $ffmpeg->open($audioPath)
            ->save(new \FFMpeg\Format\Audio\Mp3(), $mp3Path);

        // Send the MP3 file for transcription
        $apiUrl = 'https://api.groq.com/openai/v1/audio/transcriptions';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
        ])->attach('file', file_get_contents($mp3Path), basename($mp3Path))
            ->post($apiUrl, [
                'model' => 'whisper-large-v3-turbo',
                'language' => 'en',
                'temperature' => '0',
                'response_format' => 'json',
            ]);

        logger($response->json());

        // Delete the MP3 file
        unlink($mp3Path);

        return response()->json(['transcription' => $response->json('text')]);
    }
}
