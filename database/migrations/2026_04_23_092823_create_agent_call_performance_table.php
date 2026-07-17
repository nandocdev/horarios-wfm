<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_call_performance', function (Blueprint $table) {
            $table->id();
            $table->string('agent_login_id')->index();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('agent_ext')->nullable();

            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->nullable();

            $table->unsignedInteger('total_duration')->default(0);
            $table->unsignedInteger('talk_time')->default(0);
            $table->unsignedInteger('hold_time')->default(0);
            $table->unsignedInteger('work_time')->default(0);

            $table->string('phone_number')->nullable(); // número_llamado
            $table->string('ani')->nullable(); // ani_de_llamada

            $table->string('csq_name')->nullable();
            $table->string('call_skill')->nullable();
            $table->string('call_type')->nullable();

            $table->string('raw_agent_name')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'start_time']);
            $table->unique(['agent_login_id', 'start_time'], 'agent_performance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_call_performance');
    }
};
