<?php

namespace Nazemi\Laraserve\Tests\TestModels;

use Illuminate\Database\Eloquent\Model;
use Nazemi\Laraserve\Traits\IsRecipient;

class Recipient extends Model
{
    use IsRecipient;

    protected $table = 'recipients';

    protected $fillable = ['name'];
}
