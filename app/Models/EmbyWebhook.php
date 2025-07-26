<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmbyWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'item_type',
        'item_name',
        'item_path',
        'user_name',
        'server_name',
        'metadata',
        'raw_payload'
    ];

    protected $casts = [
        'metadata' => 'array',
        'raw_payload' => 'array',
    ];

    /**
     * Boot the model and generate UUID on creation
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    public function isMediaAdded()
    {
        return in_array($this->event_type, ['library.new', 'item.added']);
    }

    /**
     * Determine if the card is "Recently Added" based on event type and time window.
     */
    public function isRecentlyAdded()
    {
        $minutes = config('webhook.new_card_minutes', env('NEW_CARD_MINUTES', 60));
        return $this->isMediaAdded() &&
            $this->created_at
                ->copy()
                ->gt(now()->subMinutes($minutes));
    }
}