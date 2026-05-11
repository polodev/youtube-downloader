<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Download extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['title', 'link', 'category', 'type', 'resolution', 'status'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('videos')->singleFile();
    }
}
