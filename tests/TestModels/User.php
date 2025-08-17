<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\IsProvider;
use Nazemi\Laraserve\Traits\IsRecipient;

class User extends Model
{
    use IsProvider,IsRecipient;

    protected $table = 'users';

    protected $fillable = ['name','role'];
}
