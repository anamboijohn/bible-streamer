<?php

namespace App\Http\Controllers;

use App\Events\BibleVerseRetrieved;
use App\Http\Requests\TranscribeRequest;
use App\Http\Resources\BibleVerseResource;
use App\Models\Nkjv;
use FFMpeg\FFMpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use LucianoTonet\GroqPHP\Groq;
use Illuminate\Support\Str;

class_alias(\App\Models\Nkjv::class, 'Nkjv');
class TranscribeController extends Controller
{

    public function handdleLLMQuery(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string',
        ]);

        $bibleVerse = $this->extractBibleVerses($data['text']);
        $eloquentQuery = $this->convertTextToEloquent($bibleVerse);
        if ($eloquentQuery) {
            $result = eval("return $eloquentQuery;");
            // Extract verse numbers and combine texts
            $versesText = '';
            $versesReference = '';

            if ($result instanceof \Illuminate\Database\Eloquent\Collection) {
                $versesText = $result->pluck('text')->implode(' ');
                $versesReference = $result->map(fn($v) => "{$v->book} {$v->chapter}:{$v->verse}")->implode(', ');
            } elseif ($result) {
                $versesText = $result->text;
                $versesReference = "{$result->book} {$result->chapter}:{$result->verse}";
            }
            // Wrap in a resource
            $versesResource = new BibleVerseResource((object) [
                'combined_text' => $versesText,
                'reference' => $versesReference
            ]);

            // Convert to JSON and broadcast
            $verseJson = $versesResource->response()->getData(true);
            broadcast(new BibleVerseRetrieved($verseJson));
        } else {
            echo "Invalid query.";
        }
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
                    'content' => 'Extract Bible Verses from the following context Only: \n[Context: ' . $text . ']. \nExample: "God spke to us again and again. If we look at the book of genesis there we find how god created us. specifically the chapters 2 and when you look at the verse 7" is extracted as "Genesis 2:7"'
                ]
            ],
        ]);

        return  $response['choices'][0]['message']['content'];
    }

    private function convertTextToEloquent($verse)
    {
        if (Str::lower($verse) == 'no') return;

        $groq = new Groq();


        $systemPrompt = <<<EOT
            You are an AI that converts Bible scripture references into Eloquent ORM queries for a Laravel application. The table structure is as follows:    #### **Table: `nkjv`**

            | Column      | Type          | Description |
            |------------|--------------|-------------|
            | `book_id`  | `int`         | Book identifier |
            | `book`     | `varchar(255)` | Book name (e.g., "Genesis") |
            | `chapter`  | `int`         | Chapter number |
            | `verse`    | `int`         | Verse number |

            | `text`     | `varchar(1000)` | Verse text |#### **Eloquent Model: `Nkjv.php`**

            ```

            namespace App\Models;use Illuminate\Database\Eloquent\Factories\HasFactory;


            use Illuminate\Database\Eloquent\Model;class Nkjv extends Model

            {
                use HasFactory;
                protected $ table = 'nkjv';

                protected $ primaryKey = ['book_id', 'chapter', 'verse'];
                public $ incrementing = false;
                protected $ keyType = 'int';
                public $ timestamps = false;

                protected $ fillable = ['book_id', 'book', 'chapter', 'verse', 'text'];    protected function setKeysForSaveQuery($ query)

                {
                    return $ query->where('book_id', $ this->book_id)
                                 ->where('chapter', $ this->chapter)
                                 ->where('verse', $ this->verse);
                }
            }
            ```

            ---### **Task:**

            1. Convert given scripture references into correct **Laravel Eloquent queries**.
            2. Return **only the query**, nothing else.

            3. If you **cannot** generate a valid query, return **NO** (without explanation).

            ---

            ### **Examples:**#### **Example 1:**

            **Input:**

            *"Genesis 1:1"***Output:**

            ```php
            Nkjv::where('book', 'Genesis')->where('chapter', 1)->where('verse', 1)->first();

            ```

            ---#### **Example 2:**

            **Input:**

            *"Genesis 1:2-5"***Output:**

            ```php
            Nkjv::where('book', 'Genesis')->where('chapter', 1)->whereBetween('verse', [2, 5])->orderBy('verse')->get();

            ```

            ---#### **Example 3:**

            **Input:**

            *"John 3:16"***Output:**

            ```php
            Nkjv::where('book', 'John')->where('chapter', 3)->where('verse', 16)->first();

            ```

            ---#### **Example 4:**

            **Input:**

            *"Psalms 23"***Output:**

            ```php
            Nkjv::where('book', 'Psalms')->where('chapter', 23)->orderBy('verse')->get();

            ```

            ---#### **Example 5:**

            **Input:**

            *"Mark 1:10, 1:12, 1:15"***Output:**

            ```php
            Nkjv::where('book', 'Mark')->where('chapter', 1)->whereIn('verse', [10, 12, 15])->orderBy('verse')->get();

            ```

            ---#### **Example 6:**

            **Input:**

            *"UnknownBook 5:6-10"***Output:**

            ```
            NO

            ```

            ---### **Additional Notes:**

            - The AI **must not** add explanations or additional text—only return the Eloquent query or **"NO"** if it is not possible.
            - **Ensure correct Laravel syntax** for `where`, `whereBetween`, `whereIn`, and `orderBy`.
            - **For single verses, use `first()`**; for multiple verses, use `get()`.

            - **For whole chapters, order by `verse`**.

            ---### **Final Constraints:**

            - If the scripture format is **invalid**, return `NO`.
            - If the book name **does not exist**, return `NO`.
            - If the chapter or verse **is missing**, return `NO`.
            - Do not add any text beyond the required Eloquent query.
EOT;

        $response = $groq->chat()->completions()->create([
            'model' => 'llama3-8b-8192',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => 'Convert the following Bible Verse to Eloquent Query: VERSE=' . $verse
                ]
            ],
        ]);

        return  $this->extractEloquentQuery($response['choices'][0]['message']['content']);
    }

    private function extractEloquentQuery($inputString)
    {
        // Match from 'Nkjv::' to the end of the query, ensuring 'get()' or 'first()' is present
        if (preg_match("/Nkjv::.*?(get\(\)|first\(\));/s", $inputString, $matches)) {
            return trim($matches[0]); // Extracted Eloquent query
        }

        return null; // Return null if no valid query is found
    }
}
