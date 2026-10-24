<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnonceLecture extends Model
{
    public $timestamps = false;

    protected $fillable = ['annonce_id', 'user_id', 'lu_at'];

    protected $casts = ['lu_at' => 'datetime'];

    public function annonce() { return $this->belongsTo(Annonce::class); }
    public function user() { return $this->belongsTo(User::class); }
}
