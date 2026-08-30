<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function token()
    {
        return $this->belongsTo(Token::class);
    }
}
