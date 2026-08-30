<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenDeskHistory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['arrived_at' => 'datetime', 'departed_at' => 'datetime'];
    }

    public function previousDesk()
    {
        return $this->belongsTo(Desk::class, 'previous_desk_id');
    }

    public function newDesk()
    {
        return $this->belongsTo(Desk::class, 'new_desk_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
