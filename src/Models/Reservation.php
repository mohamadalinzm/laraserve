<?php

namespace Nazemi\Laraserve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reservation extends Model
{
    protected $fillable = [
        'provider_type',
        'provider_id',
        'recipient_type',
        'recipient_id',
        'start_time',
        'end_time',
        'note',
    ];

    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
