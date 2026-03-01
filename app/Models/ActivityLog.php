<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Query scopes for filtering
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', 'like', "%{$action}%");
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query->where('model_type', $modelType);
        if ($modelId) $query->where('model_id', $modelId);
        return $query;
    }

    public function scopeBetween($query, $from, $to)
    {
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to) $query->whereDate('created_at', '<=', $to);
        return $query;
    }
}
