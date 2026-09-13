<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
            'required_worker_changes' => 'integer',
        ];
    }

    public function isVA(): bool
    {
        return $this->category?->isVisaAttestation() ?? false;
    }

    public function isDemandLetterSubmission(): bool
    {
        return $this->category?->isDemandLetterSubmission() ?? false;
    }

    public function isChangePreWorker(): bool
    {
        return $this->category?->isChangePreWorker() ?? false;
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

    public function latestTransfer(): HasOne
    {
        return $this->hasOne(TokenTransferHistory::class)->ofMany(['transferred_at' => 'max', 'id' => 'max']);
    }

    #[Scope]
    protected function onDeskOf(Builder $query, int|Expression $holder): void
    {
        $query->where(function (Builder $query) use ($holder): void {
            $query->whereHas('latestTransfer', fn (Builder $transfer): Builder => $transfer->where('new_holder_id', $holder))
                ->orWhere(function (Builder $legacyTokens) use ($holder): void {
                    $legacyTokens->doesntHave('transferHistories')->where('current_holder_id', $holder);
                });
        });
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
