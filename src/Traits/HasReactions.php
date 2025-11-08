<?php

namespace Ritechoice23\Reactions\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Ritechoice23\Reactions\Models\Reaction;

trait HasReactions
{
    /**
     * Get all reactions for this model.
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Get the total number of reactions.
     */
    public function reactionsCount(): int
    {
        return $this->reactions()->count();
    }

    /**
     * Get reaction count breakdown by type.
     */
    public function reactionsBreakdown(): array
    {
        return $this->reactions()
            ->select('reaction_type', DB::raw('count(*) as count'))
            ->groupBy('reaction_type')
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    /**
     * Check if a model has reacted to this.
     */
    public function isReactedBy(Model $reactor): bool
    {
        return $this->reactions()
            ->where('reactor_type', $reactor->getMorphClass())
            ->where('reactor_id', $reactor->getKey())
            ->exists();
    }

    /**
     * Get a model's reaction to this.
     */
    public function reactionBy(Model $reactor): ?Reaction
    {
        return $this->reactions()
            ->where('reactor_type', $reactor->getMorphClass())
            ->where('reactor_id', $reactor->getKey())
            ->first();
    }

    /**
     * Remove a specific model's reaction.
     */
    public function removeReaction(Model $reactor): bool
    {
        return $this->reactions()
            ->where('reactor_type', $reactor->getMorphClass())
            ->where('reactor_id', $reactor->getKey())
            ->delete() > 0;
    }

    /**
     * Scope: Eager load reaction count.
     */
    public function scopeWithReactionsCount($query)
    {
        return $query->withCount('reactions');
    }

    /**
     * Scope: Order by most reacted.
     */
    public function scopeMostReacted($query, int $limit = 10)
    {
        return $query->withCount('reactions')
            ->orderByDesc('reactions_count')
            ->limit($limit);
    }

    /**
     * Scope: Add reaction status for a specific reactor.
     */
    public function scopeWithReactionStatus($query, Model $reactor)
    {
        return $query->addSelect([
            'has_reacted' => Reaction::selectRaw('1')
                ->whereColumn('reactable_id', $query->getModel()->getTable().'.id')
                ->where('reactable_type', $query->getModel()->getMorphClass())
                ->where('reactor_type', $reactor->getMorphClass())
                ->where('reactor_id', $reactor->getKey())
                ->limit(1),
            'reactor_reaction_type' => Reaction::select('reaction_type')
                ->whereColumn('reactable_id', $query->getModel()->getTable().'.id')
                ->where('reactable_type', $query->getModel()->getMorphClass())
                ->where('reactor_type', $reactor->getMorphClass())
                ->where('reactor_id', $reactor->getKey())
                ->limit(1),
        ]);
    }
}
