<?php

namespace Cachet\Models;

use Cachet\Enums\ScheduleStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'message',
        'scheduled_at',
        'completed_at',
    ];

    public function getStatusAttribute(): ScheduleStatusEnum
    {
        $now = Carbon::now();

        if ($this->scheduled_at >= $now) {
            return ScheduleStatusEnum::upcoming;
        }

        if ($this->completed_at >= $now || $this->completed_at === null) {
            return ScheduleStatusEnum::in_progress;
        }

        return ScheduleStatusEnum::complete;
    }

    /**
     * Get the components affected by this schedule.
     */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(
            Component::class,
            'schedule_components',
        );
    }

    /**
     * Render the Markdown message.
     */
    public function formattedMessage(): string
    {
        return Str::of($this->message)->markdown();
    }

    /**
     * Scope schedules that are incomplete.
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->whereDate('completed_at', '>=', Carbon::now())
        ->orWhereNull('completed_at');
    }

    /**
     * Scope schedules that are in progress.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', '<=', Carbon::now())
        ->where(function (Builder $query) {
            $query->whereDate('completed_at', '>=', Carbon::now())
            ->orWhereNull('completed_at');
        });
    }

    /**
     * Scopes schedules to those in the future.
     */
    public function scopeInTheFuture(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', '>=', Carbon::now());
    }

    /**
     * Scopes schedules to those scheduled in the past.
     */
    public function scopeInThePast(Builder $query): Builder
    {
        return $query->where('completed_at', '<=', Carbon::now());
    }
}
