<?php

namespace Nzm\LaravelAppointment\Models;

use Appointment\database\factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Appointment extends Model
{
    use HasFactory;
    protected $fillable = [
        'agentable_type',
        'agentable_id',
        'clientable_type',
        'clientable_id',
        'start_time',
        'end_time',
    ];

    protected static function newFactory(): Factory
    {
        return AppointmentFactory::new();
    }

    public function agentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clientable(): MorphTo
    {
        return $this->morphTo();
    }

}
