<?php

namespace Ritechoice23\Reactions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('reactions.table_name', 'reactions');
    }

    /**
     * Get the reactor (who reacted).
     */
    public function reactor(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the reactable (what was reacted to).
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter reactions by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('reaction_type', $type);
    }

    /**
     * Scope: Filter reactions by reactor.
     */
    public function scopeByReactor($query, Model $reactor)
    {
        return $query->where('reactor_type', $reactor->getMorphClass())
            ->where('reactor_id', $reactor->getKey());
    }

    /**
     * Scope: Filter reactions by reactable.
     */
    public function scopeByReactable($query, Model $reactable)
    {
        return $query->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey());
    }
}
