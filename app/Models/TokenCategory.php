<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'default_fee',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'default_fee' => 'decimal:2'];
    }

    public function isDemandLetterSubmission(): bool
    {
        return strtoupper($this->code) === 'DLS';
    }

    public function isVisaAttestation(): bool
    {
        return strtoupper($this->code) === 'VA';
    }

    public function isChangePreWorker(): bool
    {
        return strtoupper($this->code) === 'CPA';
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }
}
