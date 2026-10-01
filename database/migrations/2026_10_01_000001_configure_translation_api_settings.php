<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan', function (Blueprint $table) {
            $table->text('nilai')->nullable()->change();
        });

        DB::table('pengaturan')->insert([
            ['kunci' => 'terjemahan_perkata_api_url', 'nilai' => 'https://api.openai.com/v1/chat/completions'],
            ['kunci' => 'terjemahan_perkata_api_token', 'nilai' => null],
            ['kunci' => 'terjemahan_perkata_model', 'nilai' => 'gpt-4o-mini'],
            ['kunci' => 'terjemahan_perkata_max_tokens', 'nilai' => '8000'],
        ]);
    }

    public function down(): void
    {
        DB::table('pengaturan')->whereIn('kunci', [
            'terjemahan_perkata_api_url',
            'terjemahan_perkata_api_token',
            'terjemahan_perkata_model',
            'terjemahan_perkata_max_tokens',
        ])->delete();

        Schema::table('pengaturan', function (Blueprint $table) {
            $table->string('nilai')->nullable()->change();
        });
    }
};
