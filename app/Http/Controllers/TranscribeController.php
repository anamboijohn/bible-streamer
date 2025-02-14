<?php

namespace App\Http\Controllers;

use App\Http\Requests\TranscribeRequest;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use LucianoTonet\GroqPHP\Groq;

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

        $transcription = $this->transcribeAudio($mp3Path);
        logger($transcription);
    }

    private function transcribeAudio($mp3Path)
    {
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

        // Delete the MP3 file
        unlink($mp3Path);

        return $response->json('text');
    }

    public function handdleLLMQuery(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string',
        ]);

        $bibleVerse = $this->extractBibleVerses($data['text']);
    }

    private function extractBibleVerses($text)
    {
        $groq = new Groq();
        $response = $groq->chat()->completions()->create([
            'model' => 'llama3-8b-8192',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are to extract Bilbe Verses from a text. The text is an audio transcription. You must return the verses in the format: \nFORMAT `"Book Chapter:Verse" or "Book Chapter:Verse-Chapter:Verse" or "Book chapter:verse-verse"`\n. If no verses are found, return strictly with NO \n If a specific Translation of the bible was mensioned note it in your response like this example: EXAMPLE `NIV Luke 1:9 or KJV Luke 1:9 or NWT Luke 1:9`. \nYou must return either the verse or NO and nothing else'
                ],
                [
                    'role' => 'user',
                    'content' => 'Extract Bible Verses from the following context Only: \n[Context: ' . $data['text'] . ']. \nExample: "God spke to us again and again. If we look at the book of genesis there we find how god created us. specifically the chapters 2 and when you look at the verse 7" is extracted as "Genesis 2:7"'
                ]
            ],
        ]);

        return  $response['choices'][0]['message']['content'];
    }
}
