<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenCategory extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'default_fee' => 'decimal:2'];
    }

    public function tokens()
    {
        return $this->hasMany(Token::class);
    }
}
