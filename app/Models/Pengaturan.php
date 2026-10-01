<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $table = 'pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    public static function get(string $kunci, mixed $default = null): mixed
    {
        $nilai = static::where('kunci', $kunci)->value('nilai');

        return $nilai === null ? $default : $nilai;
    }

    public static function aktif(string $kunci, bool $default = true): bool
    {
        $nilai = static::get($kunci);

        return $nilai === null ? $default : in_array($nilai, ['1', 'true', 'on'], true);
    }

    public function getNilaiTampilAttribute(): ?string
    {
        return $this->kunci === 'terjemahan_perkata_api_token' && $this->nilai !== null
            ? '********'
            : $this->nilai;
    }
}
