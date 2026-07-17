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
        Schema::create('agent_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('agent_login_id')->index();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('transition_time')->index();
            $table->string('agent_state');
            $table->string('reason_code')->nullable();
            $table->unsignedInteger('duration')->default(0);
            $table->timestamps();

            $table->index(['employee_id', 'transition_time']);
            $table->unique(['agent_login_id', 'transition_time', 'agent_state'], 'agent_transition_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_state_transitions');
    }
};
