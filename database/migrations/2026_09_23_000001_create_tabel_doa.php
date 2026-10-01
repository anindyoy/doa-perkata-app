<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('doa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('teks_arab');
            $table->text('transliterasi')->nullable();
            $table->text('terjemahan');
            $table->unsignedInteger('urutan')->default(0);
            $table->text('catatan')->nullable();
            $table->string('referensi_sumber')->nullable();
            $table->string('id_eksternal')->nullable()->unique();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        Schema::create('kata_doa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doa_id')->constrained('doa')->cascadeOnDelete();
            $table->string('kata_arab');
            $table->string('transliterasi_kata')->nullable();
            $table->string('arti_kata');
            $table->unsignedInteger('urutan')->default(0);
            $table->enum('status', ['diterjemahkan_ai', 'terverifikasi'])->default('diterjemahkan_ai');
            $table->index(['doa_id', 'urutan']);
        });

        Schema::create('doa_tersimpan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('pengguna')->cascadeOnDelete();
            $table->foreignId('doa_id')->constrained('doa')->cascadeOnDelete();
            $table->timestamp('disimpan_pada')->useCurrent();
            $table->unique(['pengguna_id', 'doa_id']);
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('kunci')->unique();
            $table->string('nilai')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();
        });

        DB::table('pengaturan')->insert([
            'kunci' => 'doa_acak_hanya_terverifikasi',
            'nilai' => '1',
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('doa_tersimpan');
        Schema::dropIfExists('kata_doa');
        Schema::dropIfExists('doa');
        Schema::dropIfExists('kategori');
    }
};
