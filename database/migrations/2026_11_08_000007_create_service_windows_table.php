<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tickets') && DB::table('tickets')
            ->whereDate('queue_date', now(config('app.timezone'))->toDateString())
            ->where('status', 'serving')
            ->exists()) {
            throw new \RuntimeException("Complete or skip today's serving tickets before adding service windows.");
        }

        Schema::create('service_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name', 80);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_on_break')->default(false);
            $table->timestamps();
            $table->unique(['department_id', 'position']);
            $table->unique(['department_id', 'name']);
        });

        foreach (DB::table('departments')->orderBy('id')->pluck('id') as $departmentId) {
            foreach (range(1, 3) as $position) {
                DB::table('service_windows')->insert([
                    'department_id' => $departmentId,
                    'name' => "Window {$position}",
                    'position' => $position,
                    'is_active' => true,
                    'is_on_break' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('service_window_id')
                ->nullable()
                ->after('handled_by')
                ->constrained('service_windows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_window_id');
        });

        Schema::dropIfExists('service_windows');
    }
};
