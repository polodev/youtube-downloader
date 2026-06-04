<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Download extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'link', 'category', 'type', 'resolution', 'status'];

    public static function categories(): array
    {
        return [
            'tutorial',
            'review',
            'entertainment',
            'ai',
            'music',
            'news',
            'gaming',
            'documentary',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('videos')->singleFile();
        $this->addMediaCollection('captions')->singleFile();
    }
}
