import { Button, Select, SimpleGrid, TextInput } from '@mantine/core';
import type { FormEvent } from 'react';
import type { TFunction } from 'i18next';
import type { AnalysisJob } from '../../../api/client';
import { RecordCard } from '../../../components/workspace/RecordCard';
import { RecordStack } from '../../../components/workspace/RecordStack';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { AnalysisJobForm } from '../types';
import { jobTypeOptions } from '../types';
import { analysisJobGuidance } from '../utils';

type AnalysisJobsPanelProps = {
  t: TFunction;
  analysisJobForm: AnalysisJobForm;
  analysisJobs: AnalysisJob[];
  activeAnalysisJobsCount: number;
  onAnalysisJobFormChange: (value: AnalysisJobForm) => void;
  onCreateAnalysisJob: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onAnalysisJobAction: (jobId: number, action: 'cancel' | 'retry') => Promise<void>;
};

export function AnalysisJobsPanel({
  t,
  analysisJobForm,
  analysisJobs,
  activeAnalysisJobsCount,
  onAnalysisJobFormChange,
  onCreateAnalysisJob,
  onAnalysisJobAction,
}: AnalysisJobsPanelProps) {
  return (
    <WorkspacePanel title={t('panels.analysis.title')} subtitle={t('panels.analysis.subtitle')}>
      <form className="form-surface" onSubmit={(event) => void onCreateAnalysisJob(event)}>
        <SimpleGrid cols={{ base: 1, md: 3 }} spacing="sm">
          <TextInput
            label={t('fields.sampleId')}
            min="1"
            required
            type="number"
            value={analysisJobForm.sample_id}
            onChange={(event) => onAnalysisJobFormChange({ ...analysisJobForm, sample_id: event.currentTarget.value })}
          />
          <Select
            label={t('fields.jobType')}
            data={jobTypeOptions.map((value) => ({ value, label: t(`jobs.${value}`) }))}
            value={analysisJobForm.job_type}
            onChange={(value) => value && onAnalysisJobFormChange({ ...analysisJobForm, job_type: value })}
          />
          <Button type="submit" className="form-button-align">
            {t('actions.queueAnalysisJob')}
          </Button>
        </SimpleGrid>
      </form>
      <RecordStack
        emptyText={t('empty.analysis')}
        isEmpty={analysisJobs.length === 0}
        footer={`${t('metrics.queuedAnalysis')}: ${activeAnalysisJobsCount}`}
      >
        {analysisJobs.map((job) => (
          <RecordCard
            key={job.id}
            title={`${t(`jobs.${job.job_type}`, { defaultValue: job.job_type })} · #${job.id}`}
            description={analysisJobGuidance(job, t)}
            status={job.status}
            error={job.error_message}
          >
            <Button size="xs" disabled={job.status !== 'queued'} onClick={() => void onAnalysisJobAction(job.id, 'cancel')}>
              {t('actions.cancel')}
            </Button>
            <Button size="xs" variant="light" disabled={job.status !== 'failed'} onClick={() => void onAnalysisJobAction(job.id, 'retry')}>
              {t('actions.retry')}
            </Button>
          </RecordCard>
        ))}
      </RecordStack>
    </WorkspacePanel>
  );
}
