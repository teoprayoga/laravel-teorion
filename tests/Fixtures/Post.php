<?php

namespace Teoprayoga\Teorion\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Teoprayoga\Teorion\QueryFilter;
use Teoprayoga\Teorion\Traits\Filterable;

class Post extends Model
{
    use Filterable, SoftDeletes;

    protected $guarded = [];

    public function newQueryFilter(): QueryFilter
    {
        return new PostQueryFilter();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePopular($query, $request)
    {
        $threshold = (int) ($request->view_threshold ?? 100);
        return $query->where('view_count', '>=', $threshold);
    }

    public function scopePublished($query, $request)
    {
        return $query->where('status', 'published');
    }
}
