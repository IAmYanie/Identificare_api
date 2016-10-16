<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    protected $fillable = [
        'herbal_name', 'scientific_name', 'vernacular_name', 'properties', 'usage', 'process', 'image_url', 'is_accepted', 'is_deleted'
    ];
}
