<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use MoonShine\Laravel\Models\MoonshineUserRole;

return new class extends Migration
{
    protected string $email = 'admin@mail.com';

    public function up(): void
    {
        // Pastikan role Admin default MoonShine tersedia (id = 1).
        if (! DB::table('moonshine_user_roles')->where('id', MoonshineUserRole::DEFAULT_ROLE_ID)->exists()) {
            DB::table('moonshine_user_roles')->insert([
                'id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                'name' => 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Password diambil dari env agar tidak hardcode di migration.
        $plainPassword = env('MOONSHINE_ADMIN_PASSWORD', 'password');

        if (empty($plainPassword)) {
            return;
        }

        $exists = DB::table('moonshine_users')->where('email', $this->email)->exists();

        if ($exists) {
            DB::table('moonshine_users')
                ->where('email', $this->email)
                ->update([
                    'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                    'name' => 'Administrator',
                    'password' => Hash::make($plainPassword),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('moonshine_users')->insert([
                'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                'email' => $this->email,
                'name' => 'Administrator',
                'password' => Hash::make($plainPassword),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('moonshine_users')->where('email', $this->email)->delete();
    }
};
