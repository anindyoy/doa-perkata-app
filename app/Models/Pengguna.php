<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'pengguna';

    protected $fillable = ['nama', 'email', 'kata_sandi'];

    protected $hidden = ['kata_sandi', 'token_ingat'];

    protected function casts(): array
    {
        return ['kata_sandi' => 'hashed'];
    }

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getAuthPassword(): string
    {
        return $this->kata_sandi;
    }

    public function getRememberTokenName(): string
    {
        return 'token_ingat';
    }

    public function doaTersimpan(): BelongsToMany
    {
        return $this->belongsToMany(Doa::class, 'doa_tersimpan', 'pengguna_id', 'doa_id')
            ->withPivot('disimpan_pada')
            ->orderByPivot('disimpan_pada', 'desc');
    }
}
