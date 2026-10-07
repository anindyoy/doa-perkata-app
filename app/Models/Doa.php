<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doa extends Model
{
    const CREATED_AT = 'dibuat_pada';
    const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'doa';

    protected $fillable = [
        'kategori_id', 'judul', 'slug', 'teks_arab', 'transliterasi', 'terjemahan',
        'urutan', 'catatan', 'referensi_sumber', 'id_eksternal',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function kataDoa(): HasMany
    {
        return $this->hasMany(KataDoa::class, 'doa_id')->orderBy('urutan');
    }

    public function disimpanOleh(): BelongsToMany
    {
        return $this->belongsToMany(Pengguna::class, 'doa_tersimpan', 'doa_id', 'pengguna_id');
    }

    /** Semua kata sudah terverifikasi (dan minimal ada satu kata). */
    public function scopeTerverifikasi($query)
    {
        return $query->whereHas('kataDoa')
            ->whereDoesntHave('kataDoa', fn ($q) => $q->where('status', '!=', 'terverifikasi'));
    }

    /** Punya minimal satu arti per kata (tanpa memandang status verifikasi). */
    public function scopeAdaArtiPerKata($query)
    {
        return $query->whereHas('kataDoa');
    }
}
