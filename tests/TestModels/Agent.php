<?php

namespace Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory,Agentable;

    protected $table = 'agents';
    protected $fillable = ['name'];
}
