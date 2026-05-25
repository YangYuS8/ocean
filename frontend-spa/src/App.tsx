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
import { TasksPanel } from './features/workspace/panels/TasksPanel';
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
      operatorIdLabel={t('app.operatorId')}
      analystIdLabel={t('app.analystId')}
      refreshLabel={t('app.refresh')}
      languageLabel={t('app.languageToggle')}
      chineseLabel={t('app.chinese')}
      englishLabel={t('app.english')}
      language={language}
      operatorId={workspace.operatorId}
      analystId={workspace.analystId}
      onOperatorIdChange={workspace.setOperatorId}
      onAnalystIdChange={workspace.setAnalystId}
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
    </WorkspaceShell>
  );
}

export default App;
