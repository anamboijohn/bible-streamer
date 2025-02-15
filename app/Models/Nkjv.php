<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nkjv extends Model
{
    use HasFactory;

    protected $table = 'kjv';

    protected $primaryKey = ['book_id', 'chapter', 'verse']; // Composite primary key

    public $incrementing = false;

    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = ['book_id', 'book', 'chapter', 'verse', 'text'];

    /**
     * Override the default behavior for composite primary keys.
     */
    public function getKeyName()
    {
        return null; // Disable default key lookup
    }

    protected function setKeysForSaveQuery($query)
    {
        return $query->where('book_id', $this->book_id)
            ->where('chapter', $this->chapter)
            ->where('verse', $this->verse);
    }
}
