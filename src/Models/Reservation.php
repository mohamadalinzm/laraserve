<?php

namespace Nazemi\Laraserve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'agentable_type',
        'agentable_id',
        'clientable_type',
        'clientable_id',
        'start_time',
        'end_time',
        'note',
    ];

    public function agentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clientable(): MorphTo
    {
        return $this->morphTo();
    }
}
