app/Modules
├── AuditModule
│   ├── Actions
│   │   └── ExportAuditLogsAction.php
│   ├── Console
│   │   └── Commands
│   │       └── AuditPruneCommand.php
│   ├── DTOs
│   │   └── AuditLogExportDTO.php
│   ├── Http
│   │   └── Controllers
│   │       └── AuditExportController.php
│   ├── Listeners
│   │   ├── AuditLeaveRequestCreatedListener.php
│   │   ├── AuditLeaveRequestDecisionListener.php
│   │   ├── AuditShiftSwapApprovedListener.php
│   │   └── AuditWeeklySchedulePublishedListener.php
│   ├── Livewire
│   │   └── ListAuditLogs.php
│   ├── Models
│   │   └── AuditLog.php
│   ├── Policies
│   │   └── AuditLogPolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           └── list-audit-logs.blade.php
│   └── Routes
│       └── web.php
├── CommunicationsModule
│   ├── Actions
│   │   ├── AutoArchiveContentAction.php
│   │   ├── CreateCategoryAction.php
│   │   ├── CreateCommentAction.php
│   │   ├── CreateNewsAction.php
│   │   ├── CreatePollAction.php
│   │   ├── CreateShoutoutAction.php
│   │   ├── CreateTagAction.php
│   │   ├── DeleteCategoryAction.php
│   │   ├── DeletePollAction.php
│   │   ├── DeleteShoutoutAction.php
│   │   ├── DeleteTagAction.php
│   │   ├── ModerateContentAction.php
│   │   ├── ProcessMentionsAction.php
│   │   ├── PublishScheduledContentAction.php
│   │   ├── SendAutomaticNewsletterAction.php
│   │   ├── SendExpiredPollRemindersAction.php
│   │   ├── ToggleReactionAction.php
│   │   ├── UpdateCategoryAction.php
│   │   ├── UpdateNewsAction.php
│   │   ├── UpdatePollAction.php
│   │   ├── UpdateShoutoutAction.php
│   │   └── UpdateTagAction.php
│   ├── Database
│   │   └── Seeders
│   │       ├── CommunicationsPermissionSeeder.php
│   │       └── NewsSeeder.php
│   ├── DTOs
│   │   ├── CategoryDTO.php
│   │   ├── CommentDTO.php
│   │   ├── MentionDTO.php
│   │   ├── ModerationDTO.php
│   │   ├── NewsDTO.php
│   │   ├── PollDTO.php
│   │   ├── ReactionDTO.php
│   │   ├── ShoutoutDTO.php
│   │   └── TagDTO.php
│   ├── Events
│   │   ├── CommentCreated.php
│   │   ├── CommentDeleted.php
│   │   ├── MentionCreated.php
│   │   ├── ReactionAdded.php
│   │   └── ReactionRemoved.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── CategoryController.php
│   │   │   ├── CommentController.php
│   │   │   ├── ContentModerationController.php
│   │   │   ├── ReactionController.php
│   │   │   └── TagController.php
│   │   └── Requests
│   │       ├── ModerateContentRequest.php
│   │       ├── StoreCategoryRequest.php
│   │       ├── StoreCommentRequest.php
│   │       ├── StoreNewsRequest.php
│   │       ├── StorePollRequest.php
│   │       ├── StoreReactionRequest.php
│   │       ├── StoreShoutoutRequest.php
│   │       ├── StoreTagRequest.php
│   │       ├── UpdateCategoryRequest.php
│   │       ├── UpdateNewsRequest.php
│   │       ├── UpdatePollRequest.php
│   │       ├── UpdateShoutoutRequest.php
│   │       └── UpdateTagRequest.php
│   ├── Listeners
│   │   ├── SendCommentNotificationListener.php
│   │   ├── SendLeaveRequestCreatedNotification.php
│   │   ├── SendLeaveRequestDecisionNotification.php
│   │   ├── SendMentionNotificationListener.php
│   │   ├── SendReactionNotificationListener.php
│   │   ├── SendScheduleAssignmentUpdatedNotification.php
│   │   ├── SendShiftSwapApprovedNotification.php
│   │   ├── SendShiftSwapReceivedNotification.php
│   │   └── SendWeeklySchedulePublishedNotification.php
│   ├── Livewire
│   │   ├── CreateNews.php
│   │   ├── CreatePoll.php
│   │   ├── CreateShoutout.php
│   │   ├── EditNews.php
│   │   ├── EditShoutout.php
│   │   ├── Forms
│   │   │   ├── NewsForm.php
│   │   │   ├── PollForm.php
│   │   │   └── ShoutoutForm.php
│   │   ├── Home.php
│   │   ├── ListNews.php
│   │   ├── ListPolls.php
│   │   └── ListShoutouts.php
│   ├── Models
│   │   ├── Category.php
│   │   ├── Comment.php
│   │   ├── Mention.php
│   │   ├── News.php
│   │   ├── Notification.php
│   │   ├── Poll.php
│   │   ├── PollResponse.php
│   │   ├── Reaction.php
│   │   ├── Shoutout.php
│   │   └── Tag.php
│   ├── Notifications
│   │   ├── LeaveRequestCreatedNotification.php
│   │   ├── LeaveRequestDecisionNotification.php
│   │   ├── ScheduleAssignmentUpdatedNotification.php
│   │   ├── ShiftSwapApprovedNotification.php
│   │   ├── ShiftSwapReceivedNotification.php
│   │   └── WeeklySchedulePublishedNotification.php
│   ├── Observers
│   │   ├── CategoryObserver.php
│   │   ├── CommentObserver.php
│   │   ├── MentionObserver.php
│   │   ├── NewsObserver.php
│   │   ├── NotificationObserver.php
│   │   ├── PollObserver.php
│   │   ├── ReactionObserver.php
│   │   ├── ShoutoutObserver.php
│   │   └── TagObserver.php
│   ├── Policies
│   │   ├── CategoryPolicy.php
│   │   ├── CommentPolicy.php
│   │   ├── ContentModerationPolicy.php
│   │   ├── MentionPolicy.php
│   │   ├── NewsPolicy.php
│   │   ├── NotificationPolicy.php
│   │   ├── PollPolicy.php
│   │   ├── ReactionPolicy.php
│   │   ├── ShoutoutPolicy.php
│   │   └── TagPolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       ├── admin
│   │       │   ├── categories
│   │       │   │   ├── create.blade.php
│   │       │   │   ├── edit.blade.php
│   │       │   │   ├── index.blade.php
│   │       │   │   └── show.blade.php
│   │       │   ├── moderation
│   │       │   │   └── index.blade.php
│   │       │   └── tags
│   │       │       ├── create.blade.php
│   │       │       ├── edit.blade.php
│   │       │       ├── index.blade.php
│   │       │       └── show.blade.php
│   │       └── livewire
│   │           ├── home.blade.php
│   │           ├── list-news.blade.php
│   │           ├── list-polls.blade.php
│   │           ├── list-shoutouts.blade.php
│   │           ├── news-form.blade.php
│   │           ├── poll-form.blade.php
│   │           └── shoutout-form.blade.php
│   └── Routes
│       └── web.php
├── ConnectModule
│   ├── Actions
│   │   ├── CloseCallRecordAction.php
│   │   ├── CompleteCallRecordAction.php
│   │   ├── CreateCallQueueAction.php
│   │   ├── CreateCallRecordAction.php
│   │   ├── CreateCaseSubtypeAction.php
│   │   ├── CreateChannelAction.php
│   │   ├── CreateManualCallRecordAction.php
│   │   ├── DeleteCallQueueAction.php
│   │   ├── DeleteCaseSubtypeAction.php
│   │   ├── DeleteChannelAction.php
│   │   ├── FetchAgentDetailAction.php
│   │   ├── FetchAgentStateTransitionsAction.php
│   │   ├── FetchCiscoAgentSnapshotAction.php
│   │   ├── FetchCiscoFinesseResourceAction.php
│   │   ├── ImportUccxChatAction.php
│   │   ├── ImportUccxInboundAction.php
│   │   ├── ImportUccxPerformanceAction.php
│   │   ├── ImportUccxTransitionsAction.php
│   │   ├── SyncAgentRealtimeStateAction.php
│   │   ├── SyncCsqRealtimeStatsAction.php
│   │   ├── SyncCuicDataAction.php
│   │   ├── SyncFinesseUsersAction.php
│   │   ├── UpdateCallQueueAction.php
│   │   ├── UpdateCaseSubtypeAction.php
│   │   └── UpdateChannelAction.php
│   ├── Console
│   │   └── Commands
│   │       ├── AutoImportUccxCommand.php
│   │       ├── CuicBackfillCommand.php
│   │       ├── CuicRealtimeSyncCommand.php
│   │       ├── CuicSyncCommand.php
│   │       ├── FinesseSyncCommand.php
│   │       ├── ImportUccxDataCommand.php
│   │       └── TestCuicAgentDetailCommand.php
│   ├── Database
│   │   └── Migrations
│   │       └── 2026_05_04_132836_create_csq_realtime_stats_table.php
│   ├── DTOs
│   │   ├── CallCloseDTO.php
│   │   ├── CallCompleteDTO.php
│   │   ├── CallQueueDTO.php
│   │   ├── CallStartDTO.php
│   │   ├── CaseSubtypeDTO.php
│   │   ├── ChannelDTO.php
│   │   ├── ManualCallRecordDTO.php
│   │   └── UccxCallDataDTO.php
│   ├── Emails
│   │   ├── CuicBackfillReport.php
│   │   └── ImportErrorNotification.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── CallRecordController.php
│   │   │   └── CiscoFinesseController.php
│   │   └── Requests
│   │       ├── CloseCallRequest.php
│   │       ├── CompleteCallRequest.php
│   │       ├── CreateCallRequest.php
│   │       └── FetchCiscoAgentSnapshotRequest.php
│   ├── Livewire
│   │   ├── AgentDashboard.php
│   │   ├── CreateCallRecord.php
│   │   ├── EditCallRecord.php
│   │   ├── Forms
│   │   │   ├── CallQueueForm.php
│   │   │   ├── CaseSubtypeForm.php
│   │   │   ├── ChannelForm.php
│   │   │   ├── CompleteCallRecordForm.php
│   │   │   └── CreateCallRecordForm.php
│   │   ├── GeneralDashboard.php
│   │   ├── ListCallQueues.php
│   │   ├── ListCallRecords.php
│   │   ├── ListCaseSubtypes.php
│   │   └── ListChannels.php
│   ├── Models
│   │   ├── AgentCallPerformance.php
│   │   ├── AgentRealtimeState.php
│   │   ├── AgentStateTransition.php
│   │   ├── CallQueue.php
│   │   ├── CallRecord.php
│   │   ├── CaseSubtype.php
│   │   ├── Channel.php
│   │   ├── ChatRecord.php
│   │   └── CsqRealtimeStat.php
│   ├── Policies
│   │   ├── CallQueuePolicy.php
│   │   ├── CallRecordPolicy.php
│   │   ├── CaseSubtypePolicy.php
│   │   └── ChannelPolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       ├── emails
│   │       │   ├── backfill-report.blade.php
│   │       │   └── import-error.blade.php
│   │       └── livewire
│   │           ├── agent-dashboard.blade.php
│   │           ├── create-call-record.blade.php
│   │           ├── edit-call-record.blade.php
│   │           ├── general-dashboard.blade.php
│   │           ├── list-call-queues.blade.php
│   │           ├── list-call-records.blade.php
│   │           ├── list-case-subtypes.blade.php
│   │           ├── list-channels.blade.php
│   │           └── team-performance-summary.blade.php
│   ├── Routes
│   │   └── web.php
│   └── Services
│       ├── CiscoFinesseService.php
│       ├── CitizenValidationService.php
│       ├── CuicReportService.php
│       ├── FinesseService.php
│       └── TelemetryService.php
├── CoreModule
│   ├── Actions
│   │   ├── CreateRoleAction.php
│   │   ├── CreateUserAction.php
│   │   ├── DeleteUserAction.php
│   │   ├── Fortify
│   │   │   ├── CreateNewUser.php
│   │   │   └── ResetUserPassword.php
│   │   ├── SyncRolePermissionsAction.php
│   │   ├── ToggleUserStatusAction.php
│   │   └── UpdateUserAction.php
│   ├── Concerns
│   │   └── Auditable.php
│   ├── DTOs
│   │   ├── RoleDTO.php
│   │   └── UserDTO.php
│   ├── Http
│   │   └── Middleware
│   │       └── CheckMaintenanceMode.php
│   ├── Listeners
│   │   └── UpdateLastLoginAtListener.php
│   ├── Livewire
│   │   ├── Forms
│   │   │   └── UserForm.php
│   │   ├── Roles
│   │   │   └── ListRoles.php
│   │   ├── Shared
│   │   │   └── NotificationBell.php
│   │   ├── SystemMaintenance.php
│   │   ├── Toast.php
│   │   └── Users
│   │       ├── CreateUser.php
│   │       ├── EditUser.php
│   │       └── ListUsers.php
│   ├── Models
│   │   ├── AppSetting.php
│   │   ├── AuditLog.php
│   │   ├── Permission.php
│   │   ├── Role.php
│   │   └── User.php
│   ├── Notifications
│   │   ├── MaintenanceModeNotification.php
│   │   ├── PasswordChangedNotification.php
│   │   └── ResetPasswordNotification.php
│   ├── Observers
│   │   └── RoleObserver.php
│   ├── Policies
│   │   ├── RolePolicy.php
│   │   └── UserPolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       ├── auth
│   │       │   ├── confirm-password.blade.php
│   │       │   ├── forgot-password.blade.php
│   │       │   ├── login.blade.php
│   │       │   ├── register.blade.php
│   │       │   ├── reset-password.blade.php
│   │       │   ├── two-factor-challenge.blade.php
│   │       │   └── verify-email.blade.php
│   │       ├── livewire
│   │       │   ├── roles
│   │       │   │   └── list-roles.blade.php
│   │       │   ├── shared
│   │       │   │   └── notification-bell.blade.php
│   │       │   ├── system-maintenance.blade.php
│   │       │   ├── toast.blade.php
│   │       │   └── users
│   │       │       ├── create-user.blade.php
│   │       │       ├── edit-user.blade.php
│   │       │       └── list-users.blade.php
│   │       ├── maintenance.blade.php
│   │       └── settings
│   │           ├── ⚡appearance.blade.php
│   │           ├── ⚡delete-user-form.blade.php
│   │           ├── ⚡delete-user-modal.blade.php
│   │           ├── layout.blade.php
│   │           ├── partials
│   │           │   └── heading.blade.php
│   │           ├── ⚡profile.blade.php
│   │           ├── ⚡security.blade.php
│   │           ├── two-factor
│   │           │   └── ⚡recovery-codes.blade.php
│   │           └── ⚡two-factor-setup-modal.blade.php
│   └── Routes
│       └── web.php
├── DocumentationModule
│   ├── Livewire
│   │   ├── Admin
│   │   │   └── ManageArticles.php
│   │   └── Public
│   │       ├── ArticleDetail.php
│   │       └── ArticleIndex.php
│   ├── Models
│   │   └── Article.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           ├── admin
│   │           │   └── manage-articles.blade.php
│   │           └── public
│   │               ├── article-detail.blade.php
│   │               └── article-index.blade.php
│   └── Routes
│       └── web.php
├── FilesystemModule
│   ├── Actions
│   │   ├── DeleteFileSystemItemAction.php
│   │   ├── GetUserQuotaAction.php
│   │   ├── ShareItemAction.php
│   │   └── UploadFileAction.php
│   ├── Database
│   │   └── Migrations
│   │       └── 2026_05_15_080356_create_storage_quotas_table.php
│   ├── Livewire
│   │   ├── DownloadCenter.php
│   │   ├── FileBrowser.php
│   │   └── QuotaManager.php
│   ├── Models
│   │   ├── File.php
│   │   ├── FileShare.php
│   │   ├── Folder.php
│   │   └── StorageQuota.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       ├── livewire
│   │       │   ├── download-center.blade.php
│   │       │   ├── file-browser.blade.php
│   │       │   └── quota-manager.blade.php
│   │       └── partials
│   │           └── tree-node.blade.php
│   └── Routes
│       └── web.php
├── HelpdeskModule
│   ├── Livewire
│   │   ├── ManageTickets.php
│   │   ├── MyTickets.php
│   │   └── TicketDetail.php
│   ├── Models
│   │   ├── HelpdeskCategory.php
│   │   ├── HelpdeskTicketComment.php
│   │   └── HelpdeskTicket.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           ├── manage-tickets.blade.php
│   │           ├── my-tickets.blade.php
│   │           └── ticket-detail.blade.php
│   └── Routes
│       └── web.php
├── KnowledgeModule
│   ├── Actions
│   │   ├── CreateArticleAction.php
│   │   └── UpdateArticleAction.php
│   ├── DTOs
│   │   └── ArticleDTO.php
│   ├── Livewire
│   │   ├── ArticleDetail.php
│   │   ├── Forms
│   │   │   └── ArticleForm.php
│   │   ├── ManageArticles.php
│   │   ├── OperatorView.php
│   │   └── UpsertArticle.php
│   ├── Models
│   │   ├── Article.php
│   │   ├── ArticleVersion.php
│   │   ├── Category.php
│   │   ├── Queue.php
│   │   └── Tag.php
│   ├── Policies
│   │   └── ArticlePolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           ├── article-detail.blade.php
│   │           ├── manage-articles.blade.php
│   │           ├── operator-view.blade.php
│   │           └── upsert-article.blade.php
│   └── Routes
│       └── web.php
├── OperationsModule
│   ├── Actions
│   │   ├── CalculateAdvancedProductivityAction.php
│   │   ├── CalculateRealAdherenceAction.php
│   │   ├── GetEmployeePerformanceAction.php
│   │   ├── GetStandardizedPerformanceAction.php
│   │   └── ReconcileEmployeeAttendanceAction.php
│   ├── Console
│   │   └── Commands
│   │       └── ReconcileAttendanceCommand.php
│   ├── DTOs
│   │   ├── EmployeePerformanceDTO.php
│   │   └── StandardizedPerformanceDTO.php
│   ├── Livewire
│   │   ├── AdvancedProductivityDashboard.php
│   │   ├── AgentRealtimeCard.php
│   │   ├── AgentTimeline.php
│   │   ├── Dashboard.php
│   │   ├── IntradayAvailability.php
│   │   ├── PerformanceScorecard.php
│   │   ├── QueuePerformanceReport.php
│   │   ├── RealtimeMonitoring.php
│   │   ├── ReportingFrameworkIndex.php
│   │   ├── TeamPerformanceSummary.php
│   │   └── Widgets
│   │       ├── CriticalAlertsWidget.php
│   │       ├── HeroKpiWidget.php
│   │       ├── QueueStatsWidget.php
│   │       ├── RecentIncidentsWidget.php
│   │       ├── StateDistributionWidget.php
│   │       └── VolumeComparisonWidget.php
│   ├── Models
│   │   ├── AgentDailyMetric.php
│   │   ├── AttendanceIncident.php
│   │   └── IncidentType.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           ├── advanced-productivity-dashboard.blade.php
│   │           ├── agent-realtime-card.blade.php
│   │           ├── agent-timeline.blade.php
│   │           ├── dashboard.blade.php
│   │           ├── intraday-availability.blade.php
│   │           ├── performance-scorecard.blade.php
│   │           ├── queue-performance-report.blade.php
│   │           ├── realtime-monitoring.blade.php
│   │           ├── reporting-framework-index.blade.php
│   │           ├── team-performance-summary.blade.php
│   │           └── widgets
│   │               ├── critical-alerts-widget.blade.php
│   │               ├── hero-kpi-widget.blade.php
│   │               ├── queue-stats-widget.blade.php
│   │               ├── recent-incidents-widget.blade.php
│   │               ├── state-distribution-widget.blade.php
│   │               └── volume-comparison-widget.blade.php
│   ├── Routes
│   │   └── web.php
│   └── Services
│       └── PerformanceService.php
├── PersonnelModule
│   ├── Actions
│   │   ├── AssignEmployeesToTeamAction.php
│   │   ├── AssignEmployeeToTeamAction.php
│   │   ├── CreateDepartmentAction.php
│   │   ├── CreateDirectorateAction.php
│   │   ├── CreateEmployeeAction.php
│   │   ├── CreatePositionAction.php
│   │   ├── CreateTeamAction.php
│   │   ├── ExportEmployeesAction.php
│   │   ├── ImportEmployeesAction.php
│   │   ├── ProcessEmployeeImportChunkAction.php
│   │   ├── RemoveEmployeeFromTeamAction.php
│   │   ├── SyncEmployeeDataWithCiscoAction.php
│   │   ├── SyncEmployeeTeamsWithCiscoAction.php
│   │   ├── SyncTeamsWithCiscoAction.php
│   │   ├── ToggleDepartmentStatusAction.php
│   │   ├── ToggleDirectorateStatusAction.php
│   │   ├── TogglePositionStatusAction.php
│   │   ├── ToggleTeamStatusAction.php
│   │   ├── UpdateDepartmentAction.php
│   │   ├── UpdateDirectorateAction.php
│   │   ├── UpdateEmployeeAction.php
│   │   ├── UpdatePositionAction.php
│   │   └── UpdateTeamAction.php
│   ├── DTOs
│   │   ├── AssignEmployeesToTeamDTO.php
│   │   ├── AssignEmployeeToTeamDTO.php
│   │   ├── CreateEmployeeDTO.php
│   │   ├── DepartmentDTO.php
│   │   ├── DirectorateDTO.php
│   │   ├── EmployeeDTO.php
│   │   ├── EmployeeExportDTO.php
│   │   ├── ImportEmployeesDTO.php
│   │   ├── PositionDTO.php
│   │   ├── RemoveEmployeeFromTeamDTO.php
│   │   ├── TeamDTO.php
│   │   └── UpdateEmployeeDTO.php
│   ├── Events
│   │   ├── DepartmentStatusToggled.php
│   │   ├── DepartmentUpdated.php
│   │   ├── DirectorateStatusToggled.php
│   │   ├── DirectorateUpdated.php
│   │   ├── EmployeeCreated.php
│   │   ├── EmployeeUpdated.php
│   │   ├── PositionStatusToggled.php
│   │   ├── PositionUpdated.php
│   │   ├── TeamStatusToggled.php
│   │   └── TeamUpdated.php
│   ├── Http
│   │   ├── Controllers
│   │   │   ├── DepartmentController.php
│   │   │   ├── DirectorateController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── EmployeeExportController.php
│   │   │   ├── LocationController.php
│   │   │   ├── PositionController.php
│   │   │   └── TeamController.php
│   │   └── Requests
│   │       ├── AssignEmployeeToTeamRequest.php
│   │       ├── RemoveEmployeeFromTeamRequest.php
│   │       ├── StoreEmployeeRequest.php
│   │       └── UpdateEmployeeRequest.php
│   ├── Jobs
│   │   └── ProcessEmployeeImportChunkJob.php
│   ├── Livewire
│   │   ├── CreateDepartment.php
│   │   ├── CreateDirectorate.php
│   │   ├── CreateEmployee.php
│   │   ├── CreatePosition.php
│   │   ├── CreateTeam.php
│   │   ├── EditDepartment.php
│   │   ├── EditDirectorate.php
│   │   ├── EditEmployee.php
│   │   ├── EditPosition.php
│   │   ├── EditTeam.php
│   │   ├── Forms
│   │   │   └── ImportEmployeesForm.php
│   │   ├── ImportEmployees.php
│   │   ├── ListDepartments.php
│   │   ├── ListDirectorates.php
│   │   ├── ListEmployees.php
│   │   ├── ListPositions.php
│   │   ├── ListTeams.php
│   │   ├── ManageTeamAssignments.php
│   │   ├── ManageTeamMembers.php
│   │   ├── ShowDepartment.php
│   │   ├── ShowDirectorate.php
│   │   ├── ShowPosition.php
│   │   ├── ShowTeam.php
│   │   ├── StaffingSummary.php
│   │   └── TeamMemberTransfer.php
│   ├── Models
│   │   ├── Department.php
│   │   ├── Directorate.php
│   │   ├── District.php
│   │   ├── EmployeeDependent.php
│   │   ├── EmployeeDisability.php
│   │   ├── EmployeeDisease.php
│   │   ├── EmployeeImportBatch.php
│   │   ├── Employee.php
│   │   ├── EmployeePosition.php
│   │   ├── EmploymentStatus.php
│   │   ├── Position.php
│   │   ├── Province.php
│   │   ├── TeamMember.php
│   │   ├── Team.php
│   │   └── Township.php
│   ├── Observers
│   │   ├── DepartmentObserver.php
│   │   ├── DirectorateObserver.php
│   │   ├── EmployeeObserver.php
│   │   ├── EmploymentStatusObserver.php
│   │   ├── PositionObserver.php
│   │   └── TeamObserver.php
│   ├── Policies
│   │   ├── DepartmentPolicy.php
│   │   ├── DirectoratePolicy.php
│   │   ├── EmployeePolicy.php
│   │   ├── PositionPolicy.php
│   │   └── TeamPolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Repositories
│   │   └── EloquentEmployeeLookupRepository.php
│   ├── Resources
│   │   └── Views
│   │       ├── create.blade.php
│   │       ├── edit.blade.php
│   │       ├── import.blade.php
│   │       ├── index.blade.php
│   │       ├── livewire
│   │       │   ├── create-department.blade.php
│   │       │   ├── create-directorate.blade.php
│   │       │   ├── create-employee.blade.php
│   │       │   ├── create-position.blade.php
│   │       │   ├── create-team.blade.php
│   │       │   ├── edit-department.blade.php
│   │       │   ├── edit-directorate.blade.php
│   │       │   ├── edit-employee.blade.php
│   │       │   ├── edit-position.blade.php
│   │       │   ├── edit-team.blade.php
│   │       │   ├── import-employees.blade.php
│   │       │   ├── list-departments.blade.php
│   │       │   ├── list-directorates.blade.php
│   │       │   ├── list-employees.blade.php
│   │       │   ├── list-positions.blade.php
│   │       │   ├── list-teams.blade.php
│   │       │   ├── manage-team-members.blade.php
│   │       │   ├── show-department.blade.php
│   │       │   ├── show-directorate.blade.php
│   │       │   ├── show-position.blade.php
│   │       │   ├── show-team.blade.php
│   │       │   ├── staffing-summary.blade.php
│   │       │   └── team-member-transfer.blade.php
│   │       ├── location_index.blade.php
│   │       ├── manage-team-assignments.blade.php
│   │       └── show.blade.php
│   └── Routes
│       └── web.php
├── SupportModule
│   ├── Models
│   │   └── AuditLog.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   └── Routes
│       └── web.php
├── WfmModule
│   ├── Actions
│   │   ├── AssignIntradayActivityAction.php
│   │   ├── AssignTeamWeeklyScheduleAction.php
│   │   ├── CreateApprovedIntradayPeriodAction.php
│   │   ├── CreateScheduleAction.php
│   │   ├── ImportTeamWeeklyScheduleAction.php
│   │   ├── ProcessShiftSwapAction.php
│   │   ├── PublishWeeklyScheduleAction.php
│   │   ├── Realtime
│   │   │   └── GetExpectedAgentStateAction.php
│   │   ├── SaveAbsenceReasonAction.php
│   │   ├── SaveActivityTypeAction.php
│   │   ├── SaveAgentStateAction.php
│   │   ├── SaveScheduleAction.php
│   │   ├── SaveScheduledActivityAction.php
│   │   └── UpdateEmployeeDayAssignmentAction.php
│   ├── DTOs
│   │   └── IntradayActivityDTO.php
│   ├── Listeners
│   │   └── NotifyShiftSwapApproved.php
│   ├── Livewire
│   │   ├── EmployeeWeeklyPlanning.php
│   │   ├── Forms
│   │   │   ├── AbsenceReasonForm.php
│   │   │   ├── ActivityTypeForm.php
│   │   │   ├── AgentStateForm.php
│   │   │   ├── ExceptionForm.php
│   │   │   ├── ScheduledActivityForm.php
│   │   │   └── ScheduleForm.php
│   │   ├── ImportWeeklySchedule.php
│   │   ├── LeaveRequestHistory.php
│   │   ├── ManageAbsenceReasons.php
│   │   ├── ManageActivityTypes.php
│   │   ├── ManageAgentStates.php
│   │   ├── ManageIntradayActivities.php
│   │   ├── ManagerApprovals.php
│   │   ├── ManageScheduledActivities.php
│   │   ├── ManageScheduleExceptions.php
│   │   ├── ManageSchedules.php
│   │   ├── MyDay.php
│   │   ├── MyMetrics.php
│   │   ├── MySchedule.php
│   │   ├── MyTeam.php
│   │   ├── OperationalSettings.php
│   │   ├── RequestLeave.php
│   │   ├── RequestShiftSwap.php
│   │   ├── RequestSummary.php
│   │   ├── SwapRequestHistory.php
│   │   ├── TeamWeeklyPlanning.php
│   │   ├── WeeklyPlanning.php
│   │   ├── WeeklyPlanningTeams.php
│   │   └── WfmSwapApprovals.php
│   ├── Mail
│   │   └── ShiftSwapApprovedMail.php
│   ├── Models
│   │   ├── AbsenceReasonCode.php
│   │   ├── ActivityType.php
│   │   ├── AgentState.php
│   │   ├── ApprovedIntradayPeriod.php
│   │   ├── IntradayActivityAssignment.php
│   │   ├── IntradayActivity.php
│   │   ├── ScheduledActivityDefinition.php
│   │   ├── ScheduleException.php
│   │   ├── Schedule.php
│   │   ├── WeeklyScheduleAssignment.php
│   │   ├── WeeklySchedule.php
│   │   └── WeeklyTeamAssignment.php
│   ├── Notifications
│   │   ├── AttendanceIncidentNotification.php
│   │   ├── IntradayActivityNotification.php
│   │   ├── PendingApprovalNotification.php
│   │   ├── ScheduleModifiedNotification.php
│   │   ├── SchedulePublishedNotification.php
│   │   ├── ScheduleRequestStatusNotification.php
│   │   ├── ShiftSwapApprovedNotification.php
│   │   ├── SwapRequestNotification.php
│   │   └── SwapStatusChangedNotification.php
│   ├── Observers
│   │   └── LeaveRequestObserver.php
│   ├── Policies
│   │   ├── AbsenceReasonCodePolicy.php
│   │   ├── ActivityTypePolicy.php
│   │   ├── AgentStatePolicy.php
│   │   ├── ScheduledActivityDefinitionPolicy.php
│   │   ├── SchedulePolicy.php
│   │   ├── WeeklyScheduleAssignmentPolicy.php
│   │   └── WeeklySchedulePolicy.php
│   ├── Providers
│   │   └── ModuleServiceProvider.php
│   ├── Resources
│   │   └── Views
│   │       └── livewire
│   │           ├── employee-weekly-planning.blade.php
│   │           ├── import-weekly-schedule.blade.php
│   │           ├── leave-request-history.blade.php
│   │           ├── manage-absence-reasons.blade.php
│   │           ├── manage-activity-types.blade.php
│   │           ├── manage-agent-states.blade.php
│   │           ├── manage-intraday-activities.blade.php
│   │           ├── manager-approvals.blade.php
│   │           ├── manage-scheduled-activities.blade.php
│   │           ├── manage-schedule-exceptions.blade.php
│   │           ├── manage-schedules.blade.php
│   │           ├── my-day.blade.php
│   │           ├── my-metrics.blade.php
│   │           ├── my-schedule.blade.php
│   │           ├── my-team.blade.php
│   │           ├── operational-settings.blade.php
│   │           ├── request-leave.blade.php
│   │           ├── request-shift-swap.blade.php
│   │           ├── request-summary.blade.php
│   │           ├── swap-request-history.blade.php
│   │           ├── team-weekly-planning.blade.php
│   │           ├── weekly-planning.blade.php
│   │           ├── weekly-planning-teams.blade.php
│   │           └── wfm-swap-approvals.blade.php
│   ├── Routes
│   │   ├── api.php
│   │   └── web.php
│   └── Services
│       └── ScheduleService.php
└── WorkflowsModule
    ├── Models
    │   ├── LeaveRequestApproval.php
    │   ├── LeaveRequest.php
    │   ├── ShiftSwapApproval.php
    │   └── ShiftSwapRequest.php
    ├── Providers
    │   └── ModuleServiceProvider.php
    └── Routes
        └── web.php

197 directories, 653 files
