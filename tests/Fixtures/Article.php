<?php

namespace Teoprayoga\Teorion\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Teoprayoga\Teorion\Traits\Filterable;

class Article extends Model
{
    use Filterable;

    protected $guarded = [];

    protected $table = 'posts';
}
