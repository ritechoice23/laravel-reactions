<?php

namespace Ritechoice23\Reactions\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ritechoice23\Reactions\Traits\CanReact;

class User extends Model
{
    use CanReact;

    protected $guarded = [];
}
