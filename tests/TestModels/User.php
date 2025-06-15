<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\Agentable;
use Nazemi\Laraserve\Traits\Clientable;

class User extends Model
{
    use Agentable,Clientable;

    protected $table = 'users';

    protected $fillable = ['name','role'];
}
