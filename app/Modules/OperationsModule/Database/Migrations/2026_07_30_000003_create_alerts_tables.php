<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->bigSerial('id')->primary();
            $table->string('event_type')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('threshold_seconds')->nullable();
            $table->jsonb('escalation_minutes')->nullable()->comment('[5,15,30,60]');
            $table->jsonb('escalation_roles')->nullable()->comment('["supervisor","coordinator","chief","director"]');
            $table->jsonb('channels')->default('["database","broadcast"]');
            $table->unsignedInteger('cooldown_minutes')->default(15);
            $table->timestamps();
        });

        Schema::create('alert_events', function (Blueprint $table) {
            $table->bigSerial('id')->primary();
            $table->foreignId('alert_rule_id')->constrained('alert_rules')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('queue_id')->nullable();
            $table->string('source')->nullable();
            $table->string('message');
            $table->string('level')->default('warning');
            $table->jsonb('context')->nullable();
            $table->timestamp('first_triggered_at');
            $table->timestamp('last_triggered_at');
            $table->unsignedInteger('triggered_count')->default(1);
            $table->boolean('is_acknowledged')->default(false);
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['alert_rule_id', 'employee_id', 'resolved_at']);
            $table->index(['employee_id', 'level', 'resolved_at']);
        });

        Schema::create('alert_escalations', function (Blueprint $table) {
            $table->bigSerial('id')->primary();
            $table->foreignId('alert_event_id')->constrained('alert_events')->cascadeOnDelete();
            $table->unsignedTinyInteger('escalation_level');
            $table->string('escalated_to_role');
            $table->timestamp('escalated_at');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['alert_event_id', 'escalation_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_escalations');
        Schema::dropIfExists('alert_events');
        Schema::dropIfExists('alert_rules');
    }
};