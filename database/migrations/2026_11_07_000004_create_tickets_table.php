<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id');
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('number');
            $table->string('code', 20);
            $table->enum('status', ['waiting', 'serving', 'completed', 'cancelled', 'skipped'])->default('waiting');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['department_id', 'queue_date', 'number']);
            $table->foreign(['department_id', 'service_id'])
                ->references(['department_id', 'id'])
                ->on('services')
                ->restrictOnDelete();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
