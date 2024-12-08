<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nzm\LaravelAppointment\Traits\Clientable;

class Client extends Model
{
    use HasFactory,Clientable;

    protected $table = 'clients';
    protected $fillable = ['name'];
}
