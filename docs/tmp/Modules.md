# Modularización HorariosWFM — ADR-0001 Flat Modular Monolith

## TL;DR

15 módulos planos en `app/Modules/{Module}/` con frontera pública/privada (`Internal/`), infraestructura transversal en `app/Shared/`, y validación mecánica de boundaries via Pest. Cada módulo es dueño de sus tablas, rutas, UI y lógica. Cero dependencias cruzadas a `Internal/` de otro módulo.

---

## 1. Mapa de módulos → tablas del DDL

| #   | Módulo             | Tablas (DDL actual)                                                                                                                                                                                                                                                                                                                                  | Rol                            |
| --- | ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ |
| 1   | **Core**           | `users`, `roles`, `permissions`, `model_has_*`, `role_has_*`, `personal_access_tokens`, `sessions`, `cache*`, `jobs`, `job_batches`, `failed_jobs`, `migrations`, `password_reset_tokens`, `notifications`, `user_tour_progress`, `app_settings`, `operational_settings`, `notification_configs`, `alert_rules`, `alert_events`, `alert_escalations` | Auth, RBAC, settings, alertas  |
| 2   | **Organization**   | `directorates`, `departments`, `positions`, `employment_statuses`, `provinces`, `districts`, `townships`, `organizational_units` *(nueva)*                                                                                                                                                                                                           | Estructura institucional + geo |
| 3   | **Personnel**      | `employees`, `teams`, `team_members`, `employee_positions`, `skills`, `employee_skills`, `skill_history`, `employee_dependents`, `employee_disabilities`, `employee_diseases`, `disability_types`, `disease_types`, `employee_import_batches`                                                                                                        | Capital humano                 |
| 4   | **Wfm**            | `schedules`, `weekly_schedules`, `weekly_schedule_assignments`, `weekly_team_assignments`, `absence_reason_codes`, `schedule_exceptions`, `shift_swap_requests`, `shift_swap_approvals`, `leave_requests`, `leave_request_approvals`, `temporal_assignments`                                                                                         | Planificación de horarios      |
| 5   | **Connect**        | `channels`, `call_queues`, `queue_skills`, `case_subtypes`, `agent_states`, `agent_realtime_states`, `agent_state_transitions`, `call_records`, `chat_records`, `agent_call_performance`, `uploaded_files`, `csq_realtime_stats`                                                                                                                     | Integración Cisco              |
| 6   | **Operations**     | `activity_types`, `scheduled_activity_definitions`, `approved_intraday_periods`, `intraday_activities`, `incident_types`, `attendance_incidents`, `agent_daily_metrics`, `queue_daily_metrics`, `agent_interval_metrics`                                                                                                                             | Métricas y adherencia          |
| 7   | **Quality**        | `quality_criteria`, `quality_criteria_versions`, `quality_red_flag_criteria`, `quality_queue_criteria`, `quality_evaluations`, `quality_evaluation_scores`, `quality_evaluation_red_flags`, `quality_feedback`, `quality_calibration_log`                                                                                          | Evaluación de calidad          |
| 8   | **Forecast**       | `forecast_groups`, `forecast_versions`, `forecast_scenarios`, `forecast_intervals`, `forecast_accuracy`, `capacity_plans`, `capacity_results`, `capacity_intervals`, `staffing_requirements`, `shrinkage_categories`, `historical_shrinkage`                                                                                                         | Pronóstico y capacidad         |
| 9   | **Workflow**       | `workflow_requests`, `workflow_approvals`, `workflow_delegations`                                                                                                                                                                                                                                                                                    | Motor de aprobaciones          |
| 10  | **Audit**          | `audit_logs`                                                                                                                                                                                                                                                                                                                                         | Trazabilidad                   |
| 11  | **Communications** | `categories`, `categorizables`, `tags`, `taggables`, `news`, `comments`, `polls`, `poll_responses`, `shoutouts`, `reactions`, `mentions`                                                                                                                                                                                                             | Red interna                    |
| 12  | **Knowledge**      | `knowledge_categories`, `knowledge_tags`, `knowledge_articles`, `knowledge_article_versions`, `knowledge_article_tag`, `knowledge_queues`, `knowledge_article_queue`, `documentation_articles`                                                                                                                                         | Base de conocimiento           |
| 13  | **Helpdesk**       | `helpdesk_categories`, `helpdesk_tickets`, `helpdesk_ticket_comments`                                                                                                                                                                                                                                                                                | Soporte interno                |
| 14  | **Directory**      | `directory_buildings`, `directory_units`, `directory_services`                                                                                                                                                                                                                                                                                       | Directorio institucional       |
| 15  | **Filesystem**     | `media`, `folders`, `files`, `file_shares`, `storage_quotas`                                                                                                                                                                                                                                                                                         | Gestión de archivos            |
| 16  | **Reporting**      | `analytics_calendar_dimension`, `analytics_time_interval_dimension`, `analytics_employee_snapshot`, `fact_*` (7)                                                                                                                                                                                                                | DW y reportes                  |

---

## 2. Estructura de directorios completa

```
app/
├── Shared/                              ← Infraestructura transversal (NO es módulo)
│   ├── Models/
│   │   └── BaseModel.php                ← bigserial, timestamps, casts base
│   ├── Concerns/
│   │   ├── Auditable.php                ← Trait de auditoría
│   │   └── HasEncryptedAttributes.php   ← Trait PII cifrada
│   ├── DTOs/
│   │   └── DataTransferObject.php       ← Base inmutable (o usar spatie/laravel-data)
│   ├── Exceptions/
│   │   └── ModuleBoundaryViolation.php
│   ├── Http/
│   │   └── Middleware/
│   │       ├── ForcePasswordChange.php
│   │       └── EnsureTwoFactor.php
│   └── Support/
│       └── helpers.php
│
├── Modules/
│   ├── Core/
│   │   ├── Actions/
│   │   │   ├── CreateUser.php
│   │   │   ├── AssignRole.php
│   │   │   └── ToggleAlertRule.php
│   │   ├── Contracts/
│   │   │   └── NotifiableEntity.php
│   │   ├── DTOs/
│   │   │   ├── UserData.php
│   │   │   └── AlertRuleData.php
│   │   ├── Events/
│   │   │   ├── UserCreated.php
│   │   │   └── AlertTriggered.php
│   │   ├── Models/
│   │   │   ├── User.php
│   │   │   ├── Role.php
│   │   │   ├── Permission.php
│   │   │   ├── AppSetting.php
│   │   │   ├── OperationalSetting.php
│   │   │   ├── AlertRule.php
│   │   │   ├── AlertEvent.php
│   │   │   ├── AlertEscalation.php
│   │   │   ├── NotificationConfig.php
│   │   │   └── UserTourProgress.php
│   │   ├── Enums/
│   │   │   └── AlertLevel.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── SettingsController.php
│   │   │   │   │   └── AlertController.php
│   │   │   │   └── Requests/
│   │   │   │       └── StoreAlertRuleRequest.php
│   │   │   ├── Livewire/
│   │   │   │   ├── SettingsPanel.php
│   │   │   │   ├── AlertDashboard.php
│   │   │   │   └── Forms/
│   │   │   │       └── AlertRuleForm.php
│   │   │   ├── Routes/
│   │   │   │   ├── web.php
│   │   │   │   └── api.php
│   │   │   ├── Resources/
│   │   │   │   └── views/
│   │   │   ├── Policies/
│   │   │   │   ├── UserPolicy.php
│   │   │   │   └── AlertRulePolicy.php
│   │   │   ├── Listeners/
│   │   │   │   └── SendAlertNotification.php
│   │   │   └── Console/
│   │   │       └── PruneExpiredAlerts.php
│   │   ├── Database/
│   │   │   └── Migrations/
│   │   └── Providers/
│   │       └── ModuleServiceProvider.php
│   │
│   ├── Organization/
│   │   ├── Actions/
│   │   │   ├── CreateOrganizationalUnit.php
│   │   │   ├── MoveUnit.php
│   │   │   └── BuildOrganizationalChart.php
│   │   ├── DTOs/
│   │   │   └── OrganizationalUnitData.php
│   │   ├── Events/
│   │   │   └── UnitRestructured.php
│   │   ├── Models/
│   │   │   ├── Directorate.php
│   │   │   ├── Department.php
│   │   │   ├── Position.php
│   │   │   ├── OrganizationalUnit.php
│   │   │   ├── EmploymentStatus.php
│   │   │   ├── Province.php
│   │   │   ├── District.php
│   │   │   └── Township.php
│   │   ├── Enums/
│   │   │   └── UnitLevel.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── OrganizationalChart.php
│   │   │   │   └── UnitManager.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Personnel/
│   │   ├── Actions/
│   │   │   ├── OnboardEmployee.php
│   │   │   ├── TransferEmployee.php
│   │   │   ├── ImportEmployees.php
│   │   │   └── UpdateSkillLevel.php
│   │   ├── Contracts/
│   │   │   └── EmployeeRepository.php
│   │   ├── DTOs/
│   │   │   ├── EmployeeData.php
│   │   │   └── ImportBatchResult.php
│   │   ├── Events/
│   │   │   ├── EmployeeOnboarded.php
│   │   │   ├── EmployeeTransferred.php
│   │   │   └── SkillLevelChanged.php
│   │   ├── Models/
│   │   │   ├── Employee.php
│   │   │   ├── Team.php
│   │   │   ├── TeamMember.php
│   │   │   ├── EmployeePosition.php
│   │   │   ├── Skill.php
│   │   │   ├── EmployeeSkill.php
│   │   │   ├── SkillHistory.php
│   │   │   ├── DisabilityType.php
│   │   │   ├── DiseaseType.php
│   │   │   ├── EmployeeDependent.php
│   │   │   ├── EmployeeDisability.php
│   │   │   ├── EmployeeDisease.php
│   │   │   └── EmployeeImportBatch.php
│   │   ├── Concerns/
│   │   │   └── HasSkills.php
│   │   ├── Enums/
│   │   │   └── ImportStatus.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── EmployeeDirectory.php
│   │   │   │   ├── EmployeeProfile.php
│   │   │   │   ├── TeamManager.php
│   │   │   │   └── ImportWizard.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   ├── Policies/
│   │   │   │   └── EmployeePolicy.php
│   │   │   ├── Listeners/
│   │   │   └── Console/
│   │   │       └── ProcessEmployeeImport.php
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Wfm/
│   │   ├── Actions/
│   │   │   ├── PublishWeeklySchedule.php
│   │   │   ├── ValidateScheduleCollisions.php
│   │   │   ├── AssignShiftToEmployee.php
│   │   │   ├── RequestShiftSwap.php
│   │   │   ├── ApproveShiftSwap.php
│   │   │   ├── RequestLeave.php
│   │   │   ├── ApproveLeave.php
│   │   │   └── RecordScheduleException.php
│   │   ├── Contracts/
│   │   │   └── ScheduleValidator.php
│   │   ├── DTOs/
│   │   │   ├── ScheduleAssignmentData.php
│   │   │   ├── SwapRequestData.php
│   │   │   └── LeaveRequestData.php
│   │   ├── Events/
│   │   │   ├── SchedulePublished.php
│   │   │   ├── ShiftSwapRequested.php
│   │   │   ├── ShiftSwapApproved.php
│   │   │   ├── LeaveApproved.php
│   │   │   └── ScheduleExceptionRecorded.php
│   │   ├── Models/
│   │   │   ├── Schedule.php
│   │   │   ├── WeeklySchedule.php
│   │   │   ├── WeeklyScheduleAssignment.php
│   │   │   ├── WeeklyTeamAssignment.php
│   │   │   ├── AbsenceReasonCode.php
│   │   │   ├── ScheduleException.php
│   │   │   ├── ShiftSwapRequest.php
│   │   │   ├── ShiftSwapApproval.php
│   │   │   ├── LeaveRequest.php
│   │   │   ├── LeaveRequestApproval.php
│   │   │   └── TemporalAssignment.php
│   │   ├── Enums/
│   │   │   ├── ScheduleStatus.php
│   │   │   ├── SwapStatus.php
│   │   │   └── LeaveStatus.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── SchedulePlanner.php
│   │   │   │   ├── SchedulePublisher.php
│   │   │   │   ├── SwapRequestManager.php
│   │   │   │   ├── LeaveManager.php
│   │   │   │   └── Forms/
│   │   │   │       ├── SwapRequestForm.php
│   │   │   │       └── LeaveRequestForm.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   ├── Policies/
│   │   │   │   ├── WeeklySchedulePolicy.php
│   │   │   │   ├── ShiftSwapPolicy.php
│   │   │   │   └── LeaveRequestPolicy.php
│   │   │   └── Listeners/
│   │   │       ├── CreateExceptionFromLeave.php
│   │   │       └── NotifySwapRecipient.php
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Connect/
│   │   ├── Actions/
│   │   │   ├── SyncFinesseStates.php
│   │   │   ├── IngestCallRecords.php
│   │   │   ├── IngestChatRecords.php
│   │   │   ├── SyncCuicReports.php
│   │   │   └── UpdateRealtimeState.php
│   │   ├── Contracts/
│   │   │   ├── CtiProvider.php
│   │   │   └── FinesseClient.php
│   │   ├── DTOs/
│   │   │   ├── AgentStateData.php
│   │   │   ├── CallRecordData.php
│   │   │   └── CsqStatsData.php
│   │   ├── Events/
│   │   │   ├── AgentStateChanged.php
│   │   │   ├── CallRecordIngested.php
│   │   │   └── CsqStatsUpdated.php
│   │   ├── Models/
│   │   │   ├── Channel.php
│   │   │   ├── CallQueue.php
│   │   │   ├── QueueSkill.php
│   │   │   ├── CaseSubtype.php
│   │   │   ├── AgentState.php
│   │   │   ├── AgentRealtimeState.php
│   │   │   ├── AgentStateTransition.php
│   │   │   ├── CallRecord.php
│   │   │   ├── ChatRecord.php
│   │   │   ├── AgentCallPerformance.php
│   │   │   ├── CsqRealtimeStats.php
│   │   │   └── UploadedFile.php
│   │   ├── Enums/
│   │   │   └── ContactDisposition.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── RealtimeDashboard.php
│   │   │   │   └── QueueMonitor.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   ├── Policies/
│   │   │   ├── Listeners/
│   │   │   │   └── BroadcastAgentState.php
│   │   │   ├── Console/
│   │   │   │   ├── SyncFinesseLoop.php
│   │   │   │   ├── SyncCuicLoop.php
│   │   │   │   └── SyncCuicRealtime.php
│   │   │   └── Support/
│   │   │       ├── FinesseApiClient.php
│   │   │       └── CuicApiClient.php
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Operations/
│   │   ├── Actions/
│   │   │   ├── CalculateAdherence.php
│   │   │   ├── ComputeAgentDailyMetrics.php
│   │   │   ├── ComputeQueueDailyMetrics.php
│   │   │   ├── RecordIntradayActivity.php
│   │   │   └── RegisterAttendanceIncident.php
│   │   ├── DTOs/
│   │   │   ├── AdherenceResult.php
│   │   │   └── DailyMetricsData.php
│   │   ├── Events/
│   │   │   ├── AdherenceCalculated.php
│   │   │   └── DailyMetricsComputed.php
│   │   ├── Models/
│   │   │   ├── ActivityType.php
│   │   │   ├── ScheduledActivityDefinition.php
│   │   │   ├── ApprovedIntradayPeriod.php
│   │   │   ├── IntradayActivity.php
│   │   │   ├── IncidentType.php
│   │   │   ├── AttendanceIncident.php
│   │   │   ├── AgentDailyMetrics.php
│   │   │   ├── QueueDailyMetrics.php
│   │   │   └── AgentIntervalMetrics.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── AdherenceDashboard.php
│   │   │   │   ├── IntradayTracker.php
│   │   │   │   └── AttendanceManager.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   ├── Policies/
│   │   │   ├── Listeners/
│   │   │   │   └── RecalculateAdherenceOnStateChange.php
│   │   │   └── Console/
│   │   │       └── ComputeDailyMetrics.php
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Quality/
│   │   ├── Actions/
│   │   │   ├── CreateEvaluation.php
│   │   │   ├── CalibrateEvaluation.php
│   │   │   └── AddFeedback.php
│   │   ├── DTOs/
│   │   │   ├── EvaluationData.php
│   │   │   └── CalibrationData.php
│   │   ├── Events/
│   │   │   ├── EvaluationCompleted.php
│   │   │   └── EvaluationCalibrated.php
│   │   ├── Models/
│   │   │   ├── QualityCriteria.php
│   │   │   ├── QualityCriteriaVersion.php
│   │   │   ├── QualityRedFlagCriteria.php
│   │   │   ├── QualityQueueCriteria.php
│   │   │   ├── QualityEvaluation.php
│   │   │   ├── QualityEvaluationScore.php
│   │   │   ├── QualityEvaluationRedFlag.php
│   │   │   ├── QualityFeedback.php
│   │   │   └── QualityCalibrationLog.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   ├── EvaluationForm.php
│   │   │   │   ├── CalibrationPanel.php
│   │   │   │   └── CriteriaManager.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Forecast/
│   │   ├── Actions/
│   │   │   ├── GenerateForecast.php
│   │   │   ├── ComputeCapacityPlan.php
│   │   │   └── CalculateForecastAccuracy.php
│   │   ├── DTOs/
│   │   │   ├── ForecastIntervalData.php
│   │   │   └── CapacityResult.php
│   │   ├── Events/
│   │   │   └── ForecastGenerated.php
│   │   ├── Models/
│   │   │   ├── ForecastGroup.php
│   │   │   ├── ForecastVersion.php
│   │   │   ├── ForecastScenario.php
│   │   │   ├── ForecastInterval.php
│   │   │   ├── ForecastAccuracy.php
│   │   │   ├── CapacityPlan.php
│   │   │   ├── CapacityResult.php
│   │   │   ├── CapacityInterval.php
│   │   │   ├── StaffingRequirement.php
│   │   │   ├── ShrinkageCategory.php
│   │   │   └── HistoricalShrinkage.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   ├── Policies/
│   │   │   └── Console/
│   │   │       └── ComputeForecastAccuracy.php
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Workflow/
│   │   ├── Actions/
│   │   │   ├── SubmitRequest.php
│   │   │   ├── ApproveStep.php
│   │   │   ├── RejectStep.php
│   │   │   └── DelegateApproval.php
│   │   ├── Contracts/
│   │   │   └── Approvable.php
│   │   ├── DTOs/
│   │   │   └── WorkflowRequestData.php
│   │   ├── Events/
│   │   │   ├── WorkflowSubmitted.php
│   │   │   ├── WorkflowApproved.php
│   │   │   └── WorkflowRejected.php
│   │   ├── Models/
│   │   │   ├── WorkflowRequest.php
│   │   │   ├── WorkflowApproval.php
│   │   │   └── WorkflowDelegation.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   └── ApprovalInbox.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Audit/
│   │   ├── Actions/
│   │   │   └── RecordAuditEntry.php
│   │   ├── Models/
│   │   │   └── AuditLog.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   │   └── AuditTrail.php
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Communications/
│   │   ├── Actions/
│   │   │   ├── PublishNews.php
│   │   │   ├── CreatePoll.php
│   │   │   ├── SubmitShoutout.php
│   │   │   └── ReactToShoutout.php
│   │   ├── DTOs/
│   │   │   └── NewsData.php
│   │   ├── Events/
│   │   │   └── NewsPublished.php
│   │   ├── Models/
│   │   │   ├── Category.php
│   │   │   ├── Tag.php
│   │   │   ├── News.php
│   │   │   ├── Comment.php
│   │   │   ├── Poll.php
│   │   │   ├── PollResponse.php
│   │   │   ├── Shoutout.php
│   │   │   ├── Reaction.php
│   │   │   └── Mention.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Knowledge/
│   │   ├── Actions/
│   │   │   ├── PublishArticle.php
│   │   │   └── CreateArticleVersion.php
│   │   ├── Models/
│   │   │   ├── KnowledgeCategory.php
│   │   │   ├── KnowledgeTag.php
│   │   │   ├── KnowledgeArticle.php
│   │   │   ├── KnowledgeArticleVersion.php
│   │   │   ├── KnowledgeQueue.php
│   │   │   └── DocumentationArticle.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Helpdesk/
│   │   ├── Actions/
│   │   │   ├── CreateTicket.php
│   │   │   ├── AssignTicket.php
│   │   │   └── ResolveTicket.php
│   │   ├── Models/
│   │   │   ├── HelpdeskCategory.php
│   │   │   ├── HelpdeskTicket.php
│   │   │   └── HelpdeskTicketComment.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Directory/
│   │   ├── Models/
│   │   │   ├── DirectoryBuilding.php
│   │   │   ├── DirectoryUnit.php
│   │   │   └── DirectoryService.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   └── Resources/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   ├── Filesystem/
│   │   ├── Actions/
│   │   │   ├── UploadFile.php
│   │   │   └── ShareFile.php
│   │   ├── Models/
│   │   │   ├── Folder.php
│   │   │   ├── File.php
│   │   │   ├── FileShare.php
│   │   │   └── StorageQuota.php
│   │   ├── Internal/
│   │   │   ├── Http/
│   │   │   ├── Livewire/
│   │   │   ├── Routes/
│   │   │   ├── Resources/
│   │   │   └── Policies/
│   │   ├── Database/
│   │   └── Providers/
│   │
│   └── Reporting/
│       ├── Actions/
│       │   ├── BuildDailyReport.php
│       │   ├── ComputeFactTables.php
│       │   └── GeneratePdfReport.php
│       ├── Contracts/
│       │   └── Reportable.php
│       ├── DTOs/
│       │   └── ReportParameters.php
│       ├── Models/
│       │   ├── CalendarDimension.php
│       │   ├── TimeIntervalDimension.php
│       │   ├── EmployeeSnapshot.php
│       │   ├── FactCall.php
│       │   ├── FactSchedule.php
│       │   ├── FactAgentInterval.php
│       │   ├── FactAbsence.php
│       │   ├── FactQuality.php
│       │   ├── FactForecast.php
│       │   └── FactStaffing.php
│       ├── Internal/
│       │   ├── Http/
│       │   ├── Livewire/
│       │   │   ├── OperationsDashboard.php
│       │   │   ├── ExecutiveReport.php
│       │   │   └── ForecastAccuracyReport.php
│       │   ├── Routes/
│       │   ├── Resources/
│       │   ├── Policies/
│       │   └── Console/
│       │       └── RefreshFactTables.php
│       ├── Database/
│       └── Providers/
```

---

## 3. Reglas de acceso entre módulos

```
                    ┌─────────────────────────────────────┐
                    │         app/Shared/                 │
                    │  (BaseModel, Traits, Middleware)    │
                    │  ← Accesible por TODOS los módulos  │
                    └──────────────┬──────────────────────┘
                                   │
          ┌────────────────────────┼────────────────────────┐
          │                        │                        │
    ┌─────▼─────┐           ┌─────▼─────┐           ┌─────▼─────┐
    │  Module A │           │  Module B │           │  Module C │
    │           │           │           │           │           │
    │ Actions/  │◄──────────│ Actions/  │◄──────────│ Actions/  │
    │ DTOs/     │  PUEDE    │ DTOs/     │  PUEDE    │ DTOs/     │
    │ Events/   │  LEER     │ Events/   │  LEER     │ Events/   │
    │ Models/   │           │ Models/   │           │ Models/   │
    │ Contracts/│           │ Contracts/│           │ Contracts/│
    │           │           │           │           │           │
    │ Internal/ │ ✗ BLOQ.   │ Internal/ │ ✗ BLOQ.   │ Internal/ │
    │  Http/    │           │  Http/    │           │  Http/    │
    │  Livewire/│           │  Livewire/│           │  Livewire/│
    │  Routes/  │           │  Routes/  │           │  Routes/  │
    │  Policies/│           │  Policies/│           │  Policies/│
    └───────────┘           └───────────┘           └───────────┘
```

**Regla:** `Module A` puede consumir `Module B\Actions\*`, `Module B\DTOs\*`, `Module B\Events\*`, `Module B\Models\*`, `Module B\Contracts\*`. **Nunca** `Module B\Internal\*`.

---

## 4. ModuleServiceProvider (ejemplo: Wfm)

```php
<?php

declare(strict_types=1);

namespace App\Modules\Wfm\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Solo bindings de contenedor. Cero lógica de negocio.
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->loadMigrations();
        $this->loadViews();
    }

    private function loadRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Internal/Routes/web.php');
    }

    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Internal/Resources/views', 'wfm');
    }
}
```

**Registro en `bootstrap/providers.php`:**

```php
return [
    // ... Laravel defaults
    App\Modules\Core\Providers\ModuleServiceProvider::class,
    App\Modules\Organization\Providers\ModuleServiceProvider::class,
    App\Modules\Personnel\Providers\ModuleServiceProvider::class,
    App\Modules\Wfm\Providers\ModuleServiceProvider::class,
    App\Modules\Connect\Providers\ModuleServiceProvider::class,
    App\Modules\Operations\Providers\ModuleServiceProvider::class,
    App\Modules\Quality\Providers\ModuleServiceProvider::class,
    App\Modules\Forecast\Providers\ModuleServiceProvider::class,
    App\Modules\Workflow\Providers\ModuleServiceProvider::class,
    App\Modules\Audit\Providers\ModuleServiceProvider::class,
    App\Modules\Communications\Providers\ModuleServiceProvider::class,
    App\Modules\Knowledge\Providers\ModuleServiceProvider::class,
    App\Modules\Helpdesk\Providers\ModuleServiceProvider::class,
    App\Modules\Directory\Providers\ModuleServiceProvider::class,
    App\Modules\Filesystem\Providers\ModuleServiceProvider::class,
    App\Modules\Reporting\Providers\ModuleServiceProvider::class,
];
```

---

## 5. Test de boundaries (Pest)

```php
<?php

declare(strict_types=1);

// tests/Arch/ModuleBoundariesTest.php

use PHPUnit\Framework\Attributes\Test;

arch('modules do not access Internal of other modules')
    ->expect('App\Modules')
    ->toOnlyUse([
        'App\Shared',
        'Illuminate',
        'Laravel',
        'Spatie',
        'Livewire',
        'App\Modules\*\Actions',
        'App\Modules\*\Contracts',
        'App\Modules\*\DTOs',
        'App\Modules\*\Events',
        'App\Modules\*\Models',
        'App\Modules\*\Enums',
        'App\Modules\*\Concerns',
    ])
    ->ignoring('App\Modules\*\Internal');
```

**Versión más estricta con PHPStan/Pest custom:**

```php
<?php

declare(strict_types=1);

// tests/Arch/ModuleBoundariesStrictTest.php

$modules = [
    'Core', 'Organization', 'Personnel', 'Wfm', 'Connect',
    'Operations', 'Quality', 'Forecast', 'Workflow', 'Audit',
    'Communications', 'Knowledge', 'Helpdesk', 'Directory',
    'Filesystem', 'Reporting',
];

foreach ($modules as $module) {
    $otherModules = array_diff($modules, [$module]);

    foreach ($otherModules as $other) {
        arch("{$module} does not access {$other}\\Internal")
            ->expect("App\\Modules\\{$module}")
            ->not->toUse("App\\Modules\\{$other}\\Internal");
    }
}
```

---

## 6. Ejemplo de Action pública (contrato entre módulos)

```php
<?php

declare(strict_types=1);

namespace App\Modules\Wfm\Actions;

use App\Modules\Wfm\DTOs\ScheduleAssignmentData;
use App\Modules\Wfm\Models\WeeklyScheduleAssignment;
use Illuminate\Support\Facades\DB;

final class AssignShiftToEmployee
{
    public function execute(ScheduleAssignmentData $data): WeeklyScheduleAssignment
    {
        return DB::transaction(function () use ($data) {
            // Validar colisiones (usa modelo del propio módulo)
            $collision = (new ValidateScheduleCollisions())->execute($data);

            if ($collision->hasCollision) {
                throw new \DomainException(
                    "Colisión de horario: {$collision->reason}"
                );
            }

            return WeeklyScheduleAssignment::create([
                'weekly_schedule_id' => $data->weeklyScheduleId,
                'employee_id' => $data->employeeId,
                'schedule_id' => $data->scheduleId,
                'day_of_week' => $data->dayOfWeek,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
            ]);
        });
    }
}
```

**Consumo desde otro módulo (ej: Operations):**

```php
<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Wfm\Actions\AssignShiftToEmployee;  // ✅ Público
use App\Modules\Wfm\DTOs\ScheduleAssignmentData;     // ✅ Público
// use App\Modules\Wfm\Internal\Support\ScheduleHelper; // ❌ BLOQUEADO

final class AutoAssignShiftBasedOnAdherence
{
    public function execute(int $employeeId, string $weekStartDate): void
    {
        $data = new ScheduleAssignmentData(
            weeklyScheduleId: $this->findSchedule($weekStartDate),
            employeeId: $employeeId,
            scheduleId: $this->determineOptimalShift($employeeId),
            dayOfWeek: 1,
        );

        (new AssignShiftToEmployee())->execute($data);
    }
}
```

---

## 7. Rutas con middlewares explícitos (mitigación ADR)

```php
<?php

// app/Modules/Wfm/Internal/Routes/web.php

use App\Modules\Wfm\Internal\Livewire;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:wfm-analyst|admin'])
    ->prefix('wfm')
    ->name('wfm.')
    ->group(function () {
        Route::get('/schedules', Livewire\SchedulePlanner::class)
            ->name('schedules');

        Route::get('/schedules/{week}/publish', Livewire\SchedulePublisher::class)
            ->name('schedules.publish')
            ->middleware('role:wfm-admin|admin');  // ← Más restrictivo

        Route::get('/swaps', Livewire\SwapRequestManager::class)
            ->name('swaps');

        Route::get('/leaves', Livewire\LeaveManager::class)
            ->name('leaves');
    });
```

---

## 8. Comunicación entre módulos: flujo típico

```
ConnectModule                    OperationsModule                 WfmModule
     │                                │                              │
     │ SyncFinesseStates              │                              │
     │ ──► AgentStateChanged event    │                              │
     │                                │                              │
     │                    RecalculateAdherenceOnStateChange (listener)│
     │                                │                              │
     │                    CalculateAdherence action                   │
     │                    ──► lee Wfm\Models\WeeklyScheduleAssignment │
     │                    ──► lee Connect\Models\AgentRealtimeState   │
     │                    ──► escribe Operations\Models\AgentInterval │
     │                                │                              │
     │                                │  (sin tocar Wfm\Internal)    │
```

---

## 9. Notas sobre el DDL actual vs esta modularización

| Problema del DDL actual                                                                                 | Módulo afectado        | Acción                                                                   |
| ------------------------------------------------------------------------------------------------------- | ---------------------- | ------------------------------------------------------------------------ |
| `quality_evaluations.queue_id` referencia `quality_queues` (ULID) pero `call_queues` (bigserial) existe | Quality + Connect      | Eliminar `quality_queues`, usar `call_queues` con `is_quality_evaluable` |
| `daily_kpis` y `daily_operator_reports` duplican `agent_daily_metrics`                                  | Reporting + Operations | Eliminar, usar vistas sobre `agent_daily_metrics`                        |
| `dim_*` (6 tablas) duplican tablas OLTP                                                                 | Reporting              | Eliminar, facts referencian OLTP directo                                 |
| `notifications` tiene columnas custom + schema Laravel                                                  | Core                   | Unificar a schema Laravel puro                                           |
| IDs mixtos (bigserial/ULID)                                                                             | Todos                  | Migrar a bigserial consistente                                           |
| `cargos`, `funcionarios`, `relaciones_laborales` vacías                                                 | Organization           | Eliminar                                                                 |
| Nomenclatura bilingüe (`criterio_text`, `puntaje`, `dteval`)                                            | Quality                | Normalizar a inglés                                                      |

---

## 10. Próximos pasos

| Prioridad | Tarea                                                      | Módulos |
| --------- | ---------------------------------------------------------- | ------- |
| 1         | Crear estructura de directorios + `BaseModel` en `Shared/` | Todos   |
| 2         | Mover modelos existentes a sus módulos                     | Todos   |
| 3         | Escribir `ModuleServiceProvider` por módulo                | Todos   |
| 4         | Implementar `ModuleBoundariesTest` en Pest                 | CI/CD   |
| 5         | Refactorizar DDL (IDs, dims, duplicados)                   | DBA     |
| 6         | Implementar Actions core del WFM                           | Wfm     |
| 7         | Configurar rutas con middlewares explícitos                | Todos   |

¿Empezamos por la estructura de directorios + `BaseModel` + test de boundaries, o prefieres primero el refactor del DDL para alinear tablas con módulos?