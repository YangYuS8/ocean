import { useState } from 'react';
import { Tabs } from '@mantine/core';
import { useTranslation } from 'react-i18next';
import { apiBase } from './api/client';
import { WorkspaceShell } from './components/workspace/WorkspaceShell';
import { supportedLanguages, type SupportedLanguage } from './i18n';
import { AnalysisJobsPanel } from './features/workspace/panels/AnalysisJobsPanel';
import { ExceptionsPanel } from './features/workspace/panels/ExceptionsPanel';
import { OverviewPanel } from './features/workspace/panels/OverviewPanel';
import { ResultsPanel } from './features/workspace/panels/ResultsPanel';
import { SamplesPanel } from './features/workspace/panels/SamplesPanel';
import { SettingsPanel } from './features/workspace/panels/SettingsPanel';
import { TasksPanel } from './features/workspace/panels/TasksPanel';
import { UsersPanel } from './features/workspace/panels/UsersPanel';
import type { WorkspaceTab } from './features/workspace/types';
import { useWorkspace } from './features/workspace/useWorkspace';

function App() {
  const { t, i18n } = useTranslation();
  const workspace = useWorkspace();
  const [activeTab, setActiveTab] = useState<WorkspaceTab>('overview');

  const language = supportedLanguages.includes(i18n.language as SupportedLanguage)
    ? (i18n.language as SupportedLanguage)
    : 'zh-Hans';

  return (
    <WorkspaceShell
      title={t('app.title')}
      subtitle={t('app.subtitle')}
      versionLabel={t('app.version')}
      apiBaseLabel={t('app.apiBase')}
      apiBaseValue={apiBase}
      settingsTitle={t('app.liveData')}
      currentActorLabel={t('app.currentActor')}
      governedIdentityLabel={t('app.governedIdentity')}
      governanceHint={t('app.rbacHeaderStrategy')}
      actorRoleLabel={t('app.actorRole')}
      headerStrategyLabel={t('app.headerStrategy')}
      authTokenStrategyLabel={t('app.authTokenStrategy')}
      refreshLabel={t('app.refresh')}
      languageLabel={t('app.languageToggle')}
      chineseLabel={t('app.chinese')}
      englishLabel={t('app.english')}
      language={language}
      isAuthenticated={workspace.isAuthenticated}
      currentActorName={workspace.currentActor?.display_name ?? workspace.currentActor?.username ?? '-'}
      currentActorUsername={workspace.currentActor?.username ?? '-'}
      currentActorRole={workspace.currentActor?.roles.join(', ') ?? '-'}
      loginTitle={t('auth.loginTitle')}
      usernameLabel={t('auth.username')}
      passwordLabel={t('auth.password')}
      loginLabel={t('auth.login')}
      logoutLabel={t('auth.logout')}
      signedInAsLabel={t('auth.signedInAs')}
      demoAccountsLabel={t('auth.demoAccounts')}
      loginUsername={workspace.loginForm.username}
      loginPassword={workspace.loginForm.password}
      selectedActorId={workspace.selectedActorId}
      selectedActorRole={workspace.effectiveActor.roles.join(', ')}
      actorOptions={workspace.actorOptions.map((actor) => ({
        value: String(actor.id),
        label: `${actor.display_name ?? actor.username} · ${actor.roles.join(', ')}`,
      }))}
      onLoginUsernameChange={(value) => workspace.setLoginForm((current) => ({ ...current, username: value }))}
      onLoginPasswordChange={(value) => workspace.setLoginForm((current) => ({ ...current, password: value }))}
      onLogin={workspace.handleLogin}
      onLogout={() => void workspace.handleLogout()}
      onActorChange={workspace.setSelectedActorId}
      onRefresh={() => void workspace.refreshWorkspace()}
      onLanguageChange={(value) => void i18n.changeLanguage(value)}
      activeTab={activeTab}
      onTabChange={setActiveTab}
      overviewLabel={t('tabs.overview')}
      tasksLabel={t('tabs.tasks')}
      samplesLabel={t('tabs.samples')}
      resultsLabel={t('tabs.results')}
      exceptionsLabel={t('tabs.exceptions')}
      analysisLabel={t('tabs.analysis')}
      settingsLabel={t('tabs.settings')}
      usersLabel={t('tabs.users')}
      summaryMetrics={[
        { label: t('metrics.pendingSamples'), value: workspace.summary.pending_samples },
        { label: t('metrics.todayTasks'), value: workspace.summary.today_inspection_tasks },
        { label: t('metrics.openExceptions'), value: workspace.summary.open_exceptions },
        { label: t('metrics.queuedAnalysis'), value: workspace.summary.queued_analysis_jobs },
      ]}
    >
      <Tabs.Panel value="overview" pt="md">
        <OverviewPanel
          t={t}
          language={language}
          loadState={workspace.loadState}
          notice={workspace.notice}
          summary={workspace.summary}
          selectedTask={workspace.selectedTask}
          selectedSample={workspace.selectedSample}
          activeExceptions={workspace.activeExceptions}
          activeAnalysisJobs={workspace.activeAnalysisJobs}
        />
      </Tabs.Panel>

      <Tabs.Panel value="tasks" pt="md">
        <TasksPanel
          t={t}
          language={language}
          tasks={workspace.tasks}
          selectedTaskId={workspace.selectedTaskId}
          selectedTask={workspace.selectedTask}
          onSelectTask={workspace.setSelectedTaskId}
          onTaskAction={workspace.handleTaskAction}
          onReportException={(resourceType, resourceId) => workspace.setExceptionTarget(resourceType, resourceId)}
        />
      </Tabs.Panel>

      <Tabs.Panel value="samples" pt="md">
        <SamplesPanel
          t={t}
          samples={workspace.samples}
          selectedSampleId={workspace.selectedSampleId}
          selectedSample={workspace.selectedSample}
          sampleForm={workspace.sampleForm}
          onSampleFormChange={workspace.setSampleForm}
          onSelectSample={workspace.setSelectedSampleId}
          onCreateSample={workspace.handleCreateSample}
          onReportException={(resourceType, resourceId) => workspace.setExceptionTarget(resourceType, resourceId)}
          onQueueAnalysis={(sampleId) => {
            workspace.setAnalysisJobTarget(sampleId);
            setActiveTab('analysis');
          }}
        />
      </Tabs.Panel>

      <Tabs.Panel value="results" pt="md">
        <ResultsPanel
          t={t}
          selectedSampleId={workspace.selectedSampleId}
          resultForm={workspace.resultForm}
          results={workspace.results}
          onResultFormChange={workspace.setResultForm}
          onCreateResult={workspace.handleCreateResult}
          onReportException={(resourceType, resourceId) => {
            workspace.setExceptionTarget(resourceType, resourceId);
            setActiveTab('exceptions');
          }}
        />
      </Tabs.Panel>

      <Tabs.Panel value="exceptions" pt="md">
        <ExceptionsPanel
          t={t}
          exceptionForm={workspace.exceptionForm}
          exceptions={workspace.exceptions}
          activeExceptionsCount={workspace.activeExceptions.length}
          onExceptionFormChange={workspace.setExceptionForm}
          onCreateException={workspace.handleCreateException}
          onResolveException={workspace.handleResolveException}
        />
      </Tabs.Panel>

      <Tabs.Panel value="analysis" pt="md">
        <AnalysisJobsPanel
          t={t}
          analysisJobForm={workspace.analysisJobForm}
          analysisJobs={workspace.analysisJobs}
          activeAnalysisJobsCount={workspace.activeAnalysisJobs.length}
          onAnalysisJobFormChange={workspace.setAnalysisJobForm}
          onCreateAnalysisJob={workspace.handleCreateAnalysisJob}
          onAnalysisJobAction={workspace.handleAnalysisJobAction}
        />
      </Tabs.Panel>

      <Tabs.Panel value="settings" pt="md">
        <SettingsPanel
          t={t}
          profile={workspace.profile}
          settings={workspace.settings}
          profileForm={workspace.profileForm}
          settingsForm={workspace.settingsForm}
          isAuthenticated={workspace.isAuthenticated}
          isAdmin={workspace.isAdmin}
          onProfileFormChange={workspace.setProfileForm}
          onSettingsFormChange={workspace.setSettingsForm}
          onSaveProfile={workspace.handleSaveProfile}
          onSaveSettings={workspace.handleSaveSettings}
          onLanguageChange={(value) => void i18n.changeLanguage(value)}
        />
      </Tabs.Panel>

      <Tabs.Panel value="users" pt="md">
        <UsersPanel
          t={t}
          users={workspace.users}
          usersMeta={workspace.usersMeta}
          selectedUserId={workspace.selectedUserId}
          selectedUser={workspace.selectedUser}
          filters={workspace.userFilters}
          createForm={workspace.userCreateForm}
          editForm={workspace.userEditForm}
          forbidden={workspace.usersForbidden}
          isAdmin={workspace.isAdmin}
          onFiltersChange={workspace.setUserFilters}
          onCreateFormChange={workspace.setUserCreateForm}
          onEditFormChange={workspace.setUserEditForm}
          onSelectUser={workspace.setSelectedUserId}
          onCreateUser={workspace.handleCreateUser}
          onUpdateUser={workspace.handleUpdateUser}
          onActivation={workspace.handleUserActivation}
          onPageChange={(page) => void workspace.refreshUsers(page)}
        />
      </Tabs.Panel>
    </WorkspaceShell>
  );
}

export default App;
