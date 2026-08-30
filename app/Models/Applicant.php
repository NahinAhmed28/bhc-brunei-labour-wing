<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'passport_issue_date' => 'date', 'passport_expiry_date' => 'date', 'registration_date' => 'date', 'flight_date' => 'date', 'insurance_date' => 'date', 'pre_selected' => 'boolean', 'salary' => 'decimal:2'];
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ApplicantStatusHistory::class)->latest();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
