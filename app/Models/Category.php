<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends BaseModel
{
    protected $guarded = ['id'];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
