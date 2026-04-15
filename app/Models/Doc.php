<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Doc extends Model
{
    use Searchable;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'headings'
    ];

    public function casts(): array
    {
        return [
            'headings' => 'array',
        ];
    }

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'headings' => $this->headings,
            'content' => $this->content,
            'slug' => $this->slug,
        ];
    }
}