<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_records', function (Blueprint $table) {
            $table->id();

            $table->string('cisco_call_id');
            $table->unsignedInteger('sequence_number')->default(0);

            $table->foreignId('queue_id')->nullable()->constrained('call_queues')->nullOnDelete();

            $table->string('phone_number');
            $table->string('destination_number')->nullable();

            $table->timestamp('ivr_started_at');
            $table->timestamp('ivr_ended_at')->nullable();

            $table->unsignedInteger('talk_time')->default(0);
            $table->unsignedInteger('ring_time')->default(0);
            $table->unsignedInteger('work_time')->default(0);
            $table->unsignedInteger('queue_time')->default(0);
            $table->unsignedSmallInteger('contact_disposition')->nullable();

            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->string('raw_agent_name')->nullable();

            $table->string('citizen_identifier', 12)->nullable();

            $table->foreignId('case_subtype_id')
                ->nullable()
                ->constrained('case_subtypes')
                ->nullOnDelete();
            $table->text('description')->nullable();

            $table->string('status')->default('pending_operator');

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['cisco_call_id', 'sequence_number'], 'call_records_session_sequence_unique');
            $table->index(['employee_id', 'ivr_started_at']);
            $table->index(['queue_id', 'ivr_started_at']);
            $table->index(['case_subtype_id']);
            $table->index(['status']);
            $table->index(['citizen_identifier']);
            $table->index(['contact_disposition']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_records');
    }
};
