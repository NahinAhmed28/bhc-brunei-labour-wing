<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Token extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'boesl_date' => 'date', 'site_visit_date' => 'date', 'pre_selected' => 'boolean', 'site_visit_required' => 'boolean', 'cancelled_at' => 'datetime', 'amount' => 'decimal:2'];
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

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }

    public function deskHistories()
    {
        return $this->hasMany(TokenDeskHistory::class)->latest('arrived_at');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
