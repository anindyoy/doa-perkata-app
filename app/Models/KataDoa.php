<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KataDoa extends Model
{
    public $timestamps = false;

    protected $table = 'kata_doa';

    protected $fillable = ['doa_id', 'kata_arab', 'transliterasi_kata', 'arti_kata', 'urutan', 'status'];

    public function doa(): BelongsTo
    {
        return $this->belongsTo(Doa::class, 'doa_id');
    }
}
