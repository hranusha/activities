<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'image_url', 'date', 'is_global'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

}
