<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'type',
        'description',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $type, ?string $description = null, ?array $payload = null): void
    {
        self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'type' => $type,
            'description' => $description,
            'payload' => $payload,
        ]);
    }
}
