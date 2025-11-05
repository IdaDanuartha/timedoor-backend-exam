<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends BaseModel
{
    protected $guarded = ['id'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
