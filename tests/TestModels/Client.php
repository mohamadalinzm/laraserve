<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\Clientable;

class Client extends Model
{
    use Clientable,HasFactory;

    protected $table = 'clients';

    protected $fillable = ['name'];
}
