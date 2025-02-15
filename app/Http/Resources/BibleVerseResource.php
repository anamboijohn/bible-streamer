<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BibleVerseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    private $verse;
    public function __construct($resource)
    {
        parent::__construct($resource);
    }
    public function toArray(Request $request): array
    {
        return [
            'combined_text' => $this->resource->combined_text,
            'reference' => $this->resource->reference
        ];
    }
}
