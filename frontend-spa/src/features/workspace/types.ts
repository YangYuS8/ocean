import type { DashboardSummary } from '../../api/client';

export type LoadState = 'idle' | 'loading' | 'ready' | 'error';

export type Notice = {
  tone: 'success' | 'error';
  message: string;
};

export type ResourceType = 'inspection_task' | 'sample' | 'sample_result';
export type Severity = 'low' | 'medium' | 'high' | 'critical';
export type WorkspaceTab = 'overview' | 'tasks' | 'samples' | 'results' | 'exceptions' | 'analysis';

export type SampleForm = {
  sample_code: string;
  sample_type: string;
  name: string;
  location_text: string;
};

export type ResultForm = {
  result_type: string;
  raw_value: string;
  normalized_value: string;
  conclusion: string;
};

export type ExceptionForm = {
  resource_type: ResourceType;
  resource_id: string;
  category: string;
  severity: Severity;
  title: string;
  description: string;
};

export type AnalysisJobForm = {
  sample_id: string;
  job_type: string;
};

export const emptySummary: DashboardSummary = {
  pending_samples: 0,
  today_inspection_tasks: 0,
  open_exceptions: 0,
  queued_analysis_jobs: 0,
};

export const resourceTypeOptions = ['inspection_task', 'sample', 'sample_result'] as const;
export const severityOptions = ['low', 'medium', 'high', 'critical'] as const;
export const jobTypeOptions = ['quality_assessment', 'object_detection'] as const;

export const initialSampleForm: SampleForm = {
  sample_code: '',
  sample_type: 'water',
  name: '',
  location_text: '',
};

export const initialResultForm: ResultForm = {
  result_type: 'salinity',
  raw_value: '{"value": 31.2, "unit": "ppt"}',
  normalized_value: '{"value": 31.2, "unit": "ppt"}',
  conclusion: 'within_expected_range',
};

export const initialExceptionForm: ExceptionForm = {
  resource_type: 'sample',
  resource_id: '',
  category: 'threshold_alert',
  severity: 'medium',
  title: '',
  description: '',
};

export const initialAnalysisJobForm: AnalysisJobForm = {
  sample_id: '',
  job_type: 'quality_assessment',
};
