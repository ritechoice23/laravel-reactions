<?php

namespace Ritechoice23\Reactions\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ritechoice23\Reactions\Models\Reaction;

trait CanReact
{
    /**
     * Get all reactions made by this model.
     */
    public function reactionsGiven(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactor');
    }

    /**
     * React to a model.
     */
    public function react(Model $model, ?string $type = null): Reaction
    {
        return Reaction::updateOrCreate(
            [
                'reactor_type' => $this->getMorphClass(),
                'reactor_id' => $this->getKey(),
                'reactable_type' => $model->getMorphClass(),
                'reactable_id' => $model->getKey(),
            ],
            [
                'reaction_type' => $type ?? config('reactions.default_reaction_type', 'like'),
            ]
        );
    }

    /**
     * Remove reaction from a model.
     */
    public function unreact(Model $model): bool
    {
        return Reaction::where('reactor_type', $this->getMorphClass())
            ->where('reactor_id', $this->getKey())
            ->where('reactable_type', $model->getMorphClass())
            ->where('reactable_id', $model->getKey())
            ->delete() > 0;
    }

    /**
     * Check if this model has reacted to another model.
     */
    public function hasReactedTo(Model $model): bool
    {
        return Reaction::where('reactor_type', $this->getMorphClass())
            ->where('reactor_id', $this->getKey())
            ->where('reactable_type', $model->getMorphClass())
            ->where('reactable_id', $model->getKey())
            ->exists();
    }

    /**
     * Get the reaction type to a model.
     */
    public function reactionTo(Model $model): ?string
    {
        return Reaction::where('reactor_type', $this->getMorphClass())
            ->where('reactor_id', $this->getKey())
            ->where('reactable_type', $model->getMorphClass())
            ->where('reactable_id', $model->getKey())
            ->value('reaction_type');
    }

    /**
     * Scope: Models that reacted to a specific model.
     */
    public function scopeReactedTo($query, Model $model)
    {
        return $query->whereHas('reactionsGiven', function ($q) use ($model) {
            $q->where('reactable_type', $model->getMorphClass())
                ->where('reactable_id', $model->getKey());
        });
    }

    /**
     * Scope: Models that reacted with a specific type.
     */
    public function scopeReactedWith($query, Model $model, string $type)
    {
        return $query->whereHas('reactionsGiven', function ($q) use ($model, $type) {
            $q->where('reactable_type', $model->getMorphClass())
                ->where('reactable_id', $model->getKey())
                ->where('reaction_type', $type);
        });
    }
}
