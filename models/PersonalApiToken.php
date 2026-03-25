<?php

namespace Golem15\Apparatus\Models;

use Backend\Models\User;
use Carbon\Carbon;
use Winter\Storm\Database\Model;

class PersonalApiToken extends Model
{
    public $table = 'golem15_apparatus_personal_api_tokens';

    protected $fillable = ['name', 'token', 'expires_at'];

    protected $dates = ['last_used_at', 'expires_at'];

    protected $visible = ['id', 'name', 'last_used_at', 'expires_at', 'created_at'];

    public $belongsTo = [
        'user' => [User::class, 'key' => 'backend_user_id'],
    ];

    /**
     * Generate a new plain text token and set the hashed version on the model.
     *
     * @return string The plain text token (shown once)
     */
    public static function generateToken(): string
    {
        return 'g15_' . bin2hex(random_bytes(20));
    }

    /**
     * Hash a plain text token for storage.
     */
    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Find a token model by plain text token.
     */
    public static function findByToken(string $plainToken): ?self
    {
        return static::where('token', static::hashToken($plainToken))->first();
    }

    /**
     * Check if the token has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Update the last_used_at timestamp.
     */
    public function markAsUsed(): void
    {
        $this->forceFill(['last_used_at' => Carbon::now()])->saveQuietly();
    }
}
