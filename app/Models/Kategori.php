<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'kategori';

    protected $fillable = ['nama', 'slug', 'urutan'];

    public function doa(): HasMany
    {
        return $this->hasMany(Doa::class, 'kategori_id');
    }
}
