<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenTransferHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['transferred_at' => 'datetime'];
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    public function previousHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_holder_id');
    }

    public function newHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_holder_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
