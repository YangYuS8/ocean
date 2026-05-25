import { Alert, Grid, SimpleGrid, Stack, Text } from '@mantine/core';
import type { TFunction } from 'i18next';
import type { AnalysisJob, DashboardSummary, ExceptionRecord, InspectionTask, Sample } from '../../../api/client';
import { DetailCard } from '../../../components/workspace/DetailCard';
import { EmptyState } from '../../../components/workspace/EmptyState';
import { MetricCard } from '../../../components/workspace/MetricCard';
import { RecordCard } from '../../../components/workspace/RecordCard';
import { RecordStack } from '../../../components/workspace/RecordStack';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import { analysisJobGuidance, formatDate } from '../utils';

type OverviewPanelProps = {
  t: TFunction;
  language: string;
  loadState: 'idle' | 'loading' | 'ready' | 'error';
  notice: { tone: 'success' | 'error'; message: string } | null;
  summary: DashboardSummary;
  selectedTask: InspectionTask | null;
  selectedSample: Sample | null;
  activeExceptions: ExceptionRecord[];
  activeAnalysisJobs: AnalysisJob[];
};

export function OverviewPanel({
  t,
  language,
  loadState,
  notice,
  summary,
  selectedTask,
  selectedSample,
  activeExceptions,
  activeAnalysisJobs,
}: OverviewPanelProps) {
  return (
    <Stack gap="md">
      {notice && (
        <Alert color={notice.tone === 'success' ? 'ocean' : 'red'} variant="light" radius="md">
          {notice.message}
        </Alert>
      )}
      {loadState === 'loading' && (
        <Alert color="cyan" variant="light" radius="md">
          {t('app.loading')}
        </Alert>
      )}

      <SimpleGrid cols={{ base: 1, xs: 2, md: 4 }} spacing="md">
        <MetricCard label={t('metrics.pendingSamples')} value={summary.pending_samples} />
        <MetricCard label={t('metrics.todayTasks')} value={summary.today_inspection_tasks} />
        <MetricCard label={t('metrics.openExceptions')} value={summary.open_exceptions} />
        <MetricCard label={t('metrics.queuedAnalysis')} value={summary.queued_analysis_jobs} />
      </SimpleGrid>

      <Grid gap="md" align="stretch">
        <Grid.Col span={{ base: 12, md: 6 }}>
          <WorkspacePanel title={t('overview.currentTaskTitle')} subtitle={t('overview.currentTaskSubtitle')}>
            {selectedTask ? (
              <DetailCard
                title={selectedTask.title}
                subtitle={selectedTask.location_text || t('empty.noLocation')}
                status={selectedTask.status}
                details={[
                  [t('fields.code'), selectedTask.task_code],
                  [t('fields.type'), selectedTask.task_type],
                  [t('fields.priority'), selectedTask.priority],
                  [t('fields.planned'), formatDate(selectedTask.planned_at, language)],
                ]}
              >
                <Text size="sm" c="dimmed">
                  {selectedTask.description || t('overview.noTaskDescription')}
                </Text>
              </DetailCard>
            ) : (
              <EmptyState text={t('empty.tasks')} />
            )}
          </WorkspacePanel>
        </Grid.Col>

        <Grid.Col span={{ base: 12, md: 6 }}>
          <WorkspacePanel title={t('overview.currentSampleTitle')} subtitle={t('overview.currentSampleSubtitle')}>
            {selectedSample ? (
              <DetailCard
                title={selectedSample.name || selectedSample.sample_code}
                subtitle={selectedSample.location_text || t('empty.noLocation')}
                status={selectedSample.status}
                details={[
                  [t('fields.code'), selectedSample.sample_code],
                  [t('fields.type'), selectedSample.sample_type],
                  [t('fields.task'), selectedSample.inspection_task_id?.toString()],
                  [t('fields.collector'), selectedSample.collector?.display_name || selectedSample.collector_name],
                ]}
              >
                <Text size="sm" c="dimmed">
                  {selectedSample.notes || t('overview.noSampleNotes')}
                </Text>
              </DetailCard>
            ) : (
              <EmptyState text={t('empty.samples')} />
            )}
          </WorkspacePanel>
        </Grid.Col>

        <Grid.Col span={{ base: 12, md: 6 }}>
          <WorkspacePanel title={t('overview.openExceptionsTitle')} subtitle={t('overview.openExceptionsSubtitle')}>
            <RecordStack emptyText={t('empty.exceptions')} isEmpty={activeExceptions.length === 0}>
              {activeExceptions.slice(0, 3).map((exception) => (
                <RecordCard
                  key={exception.id}
                  title={exception.title}
                  description={`${exception.resource_type} #${exception.resource_id} · ${exception.category} · ${exception.severity}`}
                  status={exception.status}
                >
                  <></>
                </RecordCard>
              ))}
            </RecordStack>
          </WorkspacePanel>
        </Grid.Col>

        <Grid.Col span={{ base: 12, md: 6 }}>
          <WorkspacePanel title={t('overview.activeAnalysisTitle')} subtitle={t('overview.activeAnalysisSubtitle')}>
            <RecordStack emptyText={t('empty.analysis')} isEmpty={activeAnalysisJobs.length === 0}>
              {activeAnalysisJobs.slice(0, 3).map((job) => (
                <RecordCard
                  key={job.id}
                  title={`${t(`jobs.${job.job_type}`, { defaultValue: job.job_type })} · #${job.id}`}
                  description={analysisJobGuidance(job, t)}
                  status={job.status}
                  error={job.error_message}
                >
                  <></>
                </RecordCard>
              ))}
            </RecordStack>
          </WorkspacePanel>
        </Grid.Col>
      </Grid>
    </Stack>
  );
}
