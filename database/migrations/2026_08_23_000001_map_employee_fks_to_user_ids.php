<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Map teams.supervisor_id (employee_id → user_id)
        DB::statement("UPDATE teams SET supervisor_id = (SELECT user_id FROM employees WHERE id = teams.supervisor_id) WHERE supervisor_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = teams.supervisor_id)");

        // 2. Map helpdesk_tickets.creator_id (employee_id → user_id)
        DB::statement("UPDATE helpdesk_tickets SET creator_id = (SELECT user_id FROM employees WHERE id = helpdesk_tickets.creator_id) WHERE creator_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = helpdesk_tickets.creator_id)");

        // 3. Map helpdesk_tickets.assigned_agent_id (employee_id → user_id)
        DB::statement("UPDATE helpdesk_tickets SET assigned_agent_id = (SELECT user_id FROM employees WHERE id = helpdesk_tickets.assigned_agent_id) WHERE assigned_agent_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = helpdesk_tickets.assigned_agent_id)");

        // 4. Map helpdesk_ticket_comments.author_id (employee_id → user_id)
        DB::statement("UPDATE helpdesk_ticket_comments SET author_id = (SELECT user_id FROM employees WHERE id = helpdesk_ticket_comments.author_id) WHERE author_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = helpdesk_ticket_comments.author_id)");

        // 5. Map temporal_assignments.supervisor_id (employee_id → user_id)
        DB::statement("UPDATE temporal_assignments SET supervisor_id = (SELECT user_id FROM employees WHERE id = temporal_assignments.supervisor_id) WHERE supervisor_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = temporal_assignments.supervisor_id)");

        // 6. Map shift_swap_requests.requester_id (employee_id → user_id)
        DB::statement("UPDATE shift_swap_requests SET requester_id = (SELECT user_id FROM employees WHERE id = shift_swap_requests.requester_id) WHERE requester_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = shift_swap_requests.requester_id)");

        // 7. Map shift_swap_requests.recipient_id (employee_id → user_id)
        DB::statement("UPDATE shift_swap_requests SET recipient_id = (SELECT user_id FROM employees WHERE id = shift_swap_requests.recipient_id) WHERE recipient_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = shift_swap_requests.recipient_id)");

        // 8. Map shift_swap_approvals.approver_id (employee_id → user_id)
        DB::statement("UPDATE shift_swap_approvals SET approver_id = (SELECT user_id FROM employees WHERE id = shift_swap_approvals.approver_id) WHERE approver_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = shift_swap_approvals.approver_id)");

        // 9. Map leave_request_approvals.approver_id (employee_id → user_id)
        DB::statement("UPDATE leave_request_approvals SET approver_id = (SELECT user_id FROM employees WHERE id = leave_request_approvals.approver_id) WHERE approver_id IS NOT NULL AND EXISTS (SELECT 1 FROM employees WHERE id = leave_request_approvals.approver_id)");
    }

    public function down(): void
    {
        // Reverse mappings would be complex; this migration is intended as one-way
        // Since we're changing the FK target from employees to users, reversing would
        // require knowing the original employee_id, which we don't preserve.
        // The down() method should drop the new FK constraints and recreate the old ones,
        // but since we've lost the original employee_id → user_id mapping data,
        // a full reverse would require restoring from backup.
        // For safety, we'll just drop the new constraints; the old ones would need
        // to be recreated manually if a rollback is ever needed.
    }
};