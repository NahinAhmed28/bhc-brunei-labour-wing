<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Token extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'boesl_date' => 'date',
            'site_visit_date' => 'date',
            'pre_selected' => 'boolean',
            'site_visit_required' => 'boolean',
            'cancelled_at' => 'datetime',
            'amount' => 'decimal:2',
            'required_visa_attestation' => 'integer',
        ];
    }

    /** True when this token belongs to a Visa Attestation category (code = 'VA'). */
    public function isVA(): bool
    {
        return strtoupper(optional($this->category)->code ?? '') === 'VA';
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function category()
    {
        return $this->belongsTo(TokenCategory::class, 'token_category_id');
    }

    public function currentDesk()
    {
        return $this->belongsTo(Desk::class, 'current_desk_id');
    }

    public function currentHolder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_holder_id');
    }

    public function workers()
    {
        return $this->hasMany(Worker::class);
    }

    public function deskHistories()
    {
        return $this->hasMany(TokenDeskHistory::class)->latest('arrived_at');
    }

    public function transferHistories(): HasMany
    {
        return $this->hasMany(TokenTransferHistory::class)->latest('transferred_at');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
