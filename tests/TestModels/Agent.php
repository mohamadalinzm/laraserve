<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\Agentable;

class Agent extends Model
{
    use Agentable,HasFactory;

    protected $table = 'agents';

    protected $fillable = ['name'];
}
