<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Policies;

use App\Modules\AuditModule\Policies\AuditLogPolicy;
use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CommunicationsModule\Policies\CommentPolicy;
use App\Modules\CommunicationsModule\Policies\NewsPolicy;
use App\Modules\CommunicationsModule\Policies\ReactionPolicy;
use App\Modules\CoreModule\Models\User;
use App\Modules\CoreModule\Policies\UserPolicy;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\HelpdeskModule\Policies\HelpdeskTicketPolicy;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Policies\AbsenceReasonCodePolicy;
use App\Modules\WfmModule\Policies\ActivityTypePolicy;
use App\Modules\WfmModule\Policies\AgentStatePolicy;
use App\Modules\WfmModule\Policies\ScheduledActivityDefinitionPolicy;
use App\Modules\WfmModule\Policies\SchedulePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->userWithPermission = User::factory()->create();
});

// ---------------------------------------------------------------------------
// AuditLogPolicy
// ---------------------------------------------------------------------------
it('AuditLogPolicy denies viewAny without permission', function () {
    $policy = app(AuditLogPolicy::class);
    expect($policy->viewAny($this->user))->toBeFalse();
});

it('AuditLogPolicy denies export without permission', function () {
    $policy = app(AuditLogPolicy::class);
    expect($policy->export($this->user))->toBeFalse();
});

it('AuditLogPolicy allows viewAny with audit.view permission', function () {
    $this->user->givePermissionTo('audit.view');
    $policy = app(AuditLogPolicy::class);
    expect($policy->viewAny($this->user))->toBeTrue();
});

it('AuditLogPolicy allows export with audit.export permission', function () {
    $this->user->givePermissionTo('audit.export');
    $policy = app(AuditLogPolicy::class);
    expect($policy->export($this->user))->toBeTrue();
});

// ---------------------------------------------------------------------------
// HelpdeskTicketPolicy
// ---------------------------------------------------------------------------
it('HelpdeskTicketPolicy denies without helpdesk.view permission for viewAny', function () {
    $policy = app(HelpdeskTicketPolicy::class);
    expect($policy->viewAny($this->user))->toBeFalse();
});

it('HelpdeskTicketPolicy allows with helpdesk.view permission', function () {
    $this->user->givePermissionTo('helpdesk.view');
    $policy = app(HelpdeskTicketPolicy::class);
    expect($policy->viewAny($this->user))->toBeTrue();
});

it('HelpdeskTicketPolicy allows ticket creator to view own ticket', function () {
    $employee = Employee::factory()->create(['user_id' => $this->user->id]);
    $this->user->setRelation('employee', $employee);
    $ticket = HelpdeskTicket::factory()->create(['creator_id' => $employee->id]);

    $policy = app(HelpdeskTicketPolicy::class);
    expect($policy->view($this->user, $ticket))->toBeTrue();
});

it('HelpdeskTicketPolicy denies other users to view tickets they did not create', function () {
    $otherEmployee = Employee::factory()->create();
    $ticket = HelpdeskTicket::factory()->create(['creator_id' => $otherEmployee->id]);

    $policy = app(HelpdeskTicketPolicy::class);
    expect($policy->view($this->user, $ticket))->toBeFalse();
});

// ---------------------------------------------------------------------------
// WfmModule policies (formerly role-based, now permission-based)
// ---------------------------------------------------------------------------
it('catalog policy denies without permission', function (string $policyClass, string $permission) {
    $policy = app($policyClass);
    expect($policy->viewAny($this->user))->toBeFalse()
        ->and($policy->view($this->user))->toBeFalse()
        ->and($policy->create($this->user))->toBeFalse();
})->with([
    [AbsenceReasonCodePolicy::class, 'wfm.catalogs.absences'],
    [ActivityTypePolicy::class, 'wfm.catalogs.activities'],
    [AgentStatePolicy::class, 'wfm.catalogs.agent_states'],
    [ScheduledActivityDefinitionPolicy::class, 'wfm.catalogs.scheduled_defs'],
]);

it('catalog policy allows with corresponding permission', function (string $policyClass, string $permission) {
    $this->user->givePermissionTo($permission);
    $policy = app($policyClass);
    expect($policy->viewAny($this->user))->toBeTrue()
        ->and($policy->view($this->user))->toBeTrue()
        ->and($policy->create($this->user))->toBeTrue();
})->with([
    [AbsenceReasonCodePolicy::class, 'wfm.catalogs.absences'],
    [ActivityTypePolicy::class, 'wfm.catalogs.activities'],
    [AgentStatePolicy::class, 'wfm.catalogs.agent_states'],
    [ScheduledActivityDefinitionPolicy::class, 'wfm.catalogs.scheduled_defs'],
]);

// ---------------------------------------------------------------------------
// UserPolicy (hierarchy)
// ---------------------------------------------------------------------------
it('UserPolicy allows self-view', function () {
    $policy = app(UserPolicy::class);
    expect($policy->view($this->user, $this->user))->toBeTrue();
});

it('UserPolicy denies viewing other users without permission', function () {
    $other = User::factory()->create();
    $policy = app(UserPolicy::class);
    expect($policy->view($this->user, $other))->toBeFalse();
});

it('UserPolicy allows viewing other users with users.view permission', function () {
    $this->user->givePermissionTo('users.view');
    $other = User::factory()->create();
    $policy = app(UserPolicy::class);
    expect($policy->view($this->user, $other))->toBeTrue();
});

// ---------------------------------------------------------------------------
// SchedulePolicy
// ---------------------------------------------------------------------------
it('SchedulePolicy denies without schedules.manage', function () {
    $policy = app(SchedulePolicy::class);
    expect($policy->viewAny($this->user))->toBeFalse();
});

it('SchedulePolicy allows with schedules.manage permission', function () {
    $this->user->givePermissionTo('schedules.manage');
    $policy = app(SchedulePolicy::class);
    expect($policy->viewAny($this->user))->toBeTrue()
        ->and($policy->create($this->user))->toBeTrue();
});

// ---------------------------------------------------------------------------
// AuditLogPolicy - verify no hasRole() fallback (Gate::before handles admin)
// ---------------------------------------------------------------------------
it('AuditLogPolicy no longer uses hasRole admin bypass - Gate::before handles it', function () {
    $this->user->assignRole('admin');
    $policy = app(AuditLogPolicy::class);

    expect($policy->viewAny($this->user))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Ownership policies (News, Comment, Reaction, Mention, Notification)
// ---------------------------------------------------------------------------
it('NewsPolicy allows author to update own news', function () {
    $policy = app(NewsPolicy::class);
    $news = new News(['author_id' => $this->user->id]);

    expect($policy->update($this->user, $news))->toBeTrue();
});

it('NewsPolicy allows author to delete own news', function () {
    $policy = app(NewsPolicy::class);
    $news = new News(['author_id' => $this->user->id]);

    expect($policy->delete($this->user, $news))->toBeTrue();
});

it('CommentPolicy allows owner to update own comment', function () {
    $policy = app(CommentPolicy::class);
    $comment = new Comment(['user_id' => $this->user->id]);

    expect($policy->update($this->user, $comment))->toBeTrue();
});

it('ReactionPolicy allows owner to delete own reaction', function () {
    $policy = app(ReactionPolicy::class);
    $reaction = new Reaction(['user_id' => $this->user->id]);

    expect($policy->delete($this->user, $reaction))->toBeTrue();
});

it('MentionPolicy allows mentioned user to view', function () {
    $policy = app(\App\Modules\CommunicationsModule\Policies\MentionPolicy::class);
    $mention = new Mention(['mentioned_user_id' => $this->user->id]);

    expect($policy->view($this->user, $mention))->toBeTrue();
});
