<?php

use App\Events\BibleVerseRetrieved;
use App\Models\Nkjv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\postJson;

// it('handles LLM query with valid text', function () {
//     // Arrange
//     Event::fake();
//     Nkjv::where('book', 'Genesis')->where('chapter', 1)->where('verse', 1)->first()->andReturn([
//         'book_id' => 1,
//         'book' => 'Genesis',
//         'chapter' => 1,
//         'verse' => 1,
//         'text' => 'In the beginning God created the heaven and the earth.',
//     ]);

//     $data = [
//         'text' => 'Genesis 1:1',
//     ];

//     // Act
//     $response = postJson(route('transcribe.llm'), $data);

//     // Assert
//     $response->assertStatus(200);
//     Event::assertDispatched(BibleVerseRetrieved::class, function ($event) {
//         return $event->verseJson['combined_text'] === 'In the beginning God created the heaven and the earth.' &&
//             $event->verseJson['reference'] === 'Genesis 1:1';
//     });
// });

it('handles LLM query with invalid text', function () {
    // Arrange
    $data = [
        'text' => 'Invalid text',
    ];

    // Act
    $response = postJson(route('transcribe.llm'), $data);

    // Assert
    $response->assertStatus(200);
});
