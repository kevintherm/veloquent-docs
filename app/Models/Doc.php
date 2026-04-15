<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Doc extends Model
{
    use Searchable;

    protected $fillable = [
        'version',
        'title',
        'slug',
        'content',
        'searchable_content',
        'headings',
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
            'version' => $this->version,
            'title' => $this->title,
            'content' => $this->searchable_content,
            'headings' => $this->headings,
            'slug' => $this->slug,
        ];
    }

    public function scopeForVersion($query, $version)
    {
        return $query->where('version', $version);
    }
}
