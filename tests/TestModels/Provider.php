<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\IsProvider;

class Provider extends Model
{
    use IsProvider;

    protected $table = 'providers';

    protected $fillable = ['name'];
}
