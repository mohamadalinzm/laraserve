<?php

namespace Nzm\Appointment\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Nzm\Appointment\Traits\Agentable;
use Nzm\Appointment\Traits\Clientable;

class User extends Model
{
    use Agentable,Clientable;

    protected $table = 'users';

    protected $fillable = ['name','role'];
}
