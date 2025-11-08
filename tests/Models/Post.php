<?php

namespace Ritechoice23\Reactions\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ritechoice23\Reactions\Traits\HasReactions;

class Post extends Model
{
    use HasReactions;

    protected $guarded = [];
}
