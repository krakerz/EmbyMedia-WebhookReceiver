<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    public function isMediaAdded()
    {
        return in_array($this->event_type, ['library.new', 'item.added']);
    }
}