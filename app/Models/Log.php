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

    public static function record(string $action, string $type, ?string $description = null, ?array $payload = null, ?int $userId = null): void
    {
        self::create([
            'user_id' => $userId ?? (auth()->check() ? auth()->id() : null),
            'action' => $action,
            'type' => $type,
            'description' => $description,
            'payload' => $payload,
        ]);
    }
}
