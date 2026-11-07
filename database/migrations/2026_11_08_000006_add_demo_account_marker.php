<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('is_active');
        });

        DB::table('users')
            ->whereIn('email', [
                'admin@example.com',
                'admission.staff@example.com',
                'registrar.staff@example.com',
                'accounting.staff@example.com',
                'student001@example.com',
                'student002@example.com',
                'student003@example.com',
            ])
            ->update(['is_demo' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
    }
};
