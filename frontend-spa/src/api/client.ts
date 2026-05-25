export const apiBase = import.meta.env.VITE_API_BASE?.trim() || '/api';

export type ListMeta = {
  page: number;
  page_size: number;
  total: number;
};

export type ListResponse<T> = {
  data: T[];
  meta: ListMeta;
};

export type DashboardSummary = {
  pending_samples: number;
  today_inspection_tasks: number;
  open_exceptions: number;
  queued_analysis_jobs: number;
};

export type PersonRef = {
  id: number;
  display_name: string | null;
};

export type InspectionTask = {
  id: number;
  task_code: string;
  title: string;
  description?: string | null;
  task_type: string;
  priority: string;
  status: 'assigned' | 'in_progress' | 'submitted' | 'completed' | string;
  location_text: string | null;
  planned_at: string | null;
  due_at: string | null;
  assigned_to: PersonRef | null;
  created_by?: PersonRef | null;
  started_at?: string | null;
  submitted_at?: string | null;
};

export type Sample = {
  id: number;
  sample_code: string;
  inspection_task_id: number | null;
  sample_type: string;
  name: string | null;
  status: string;
  collection_time: string | null;
  location_text: string | null;
  collector_id?: number | null;
  collector_name?: string | null;
  collector?: PersonRef | null;
  notes?: string | null;
};

export type SampleResult = {
  id: number;
  sample_id: number;
  result_type: string;
  status: string;
  raw_value: unknown;
  normalized_value: unknown;
  conclusion: string | null;
  entered_by: PersonRef | null;
  entered_at: string | null;
};

export type ExceptionRecord = {
  id: number;
  resource_type: 'inspection_task' | 'sample' | 'sample_result' | string;
  resource_id: number;
  category: string;
  severity: string;
  title: string;
  description: string | null;
  status: 'open' | 'resolved' | string;
  reported_by: PersonRef | null;
  resolved_by: PersonRef | null;
  resolved_at: string | null;
  created_at: string | null;
};

export type AnalysisJob = {
  id: number;
  sample_id: number;
  job_type: string;
  status: 'queued' | 'running' | 'succeeded' | 'failed' | 'cancelled' | string;
  params?: unknown;
  result_summary: string | null;
  suggestion: unknown;
  error_message: string | null;
  queued_by: PersonRef | null;
  queued_at: string | null;
  started_at: string | null;
  finished_at: string | null;
};

export type SampleCreatePayload = {
  sample_code: string;
  sample_type: string;
  inspection_task_id?: number;
  name?: string;
  collection_time?: string;
  location_text?: string;
  collector_id?: number;
  notes?: string;
};

export type SampleResultCreatePayload = {
  result_type: string;
  raw_value?: Record<string, unknown>;
  normalized_value?: Record<string, unknown>;
  conclusion?: string;
  entered_by?: number;
  notes?: string;
};

export type ExceptionCreatePayload = {
  resource_type: 'inspection_task' | 'sample' | 'sample_result';
  resource_id: number;
  category: string;
  severity?: string;
  title: string;
  description?: string;
  reported_by?: number;
};

export type AnalysisJobCreatePayload = {
  sample_id: number;
  job_type: string;
  params?: Record<string, unknown>;
  queued_by?: number;
};

export type MutationResult = {
  id: number;
  status?: string;
  created_at?: string;
  started_at?: string;
  submitted_at?: string;
  resolved_at?: string;
};

type ApiEnvelope<T> = {
  data: T;
};

type ApiErrorEnvelope = {
  error?: {
    code?: string;
    message?: string;
  };
};

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly code?: string,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export function buildApiUrl(path: string): string {
  const normalizedBase = apiBase.endsWith('/') ? apiBase.slice(0, -1) : apiBase;
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;

  return `${normalizedBase}${normalizedPath}`;
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers = new Headers(init.headers);

  if (init.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  const response = await fetch(buildApiUrl(path), {
    ...init,
    headers,
  });

  const payload = (await response.json().catch(() => null)) as ApiEnvelope<T> & ApiErrorEnvelope | null;

  if (!response.ok) {
    throw new ApiError(
      payload?.error?.message || `Request failed with HTTP ${response.status}`,
      response.status,
      payload?.error?.code,
    );
  }

  return payload?.data as T;
}

async function listRequest<T>(path: string): Promise<ListResponse<T>> {
  const response = await fetch(buildApiUrl(path));
  const payload = (await response.json().catch(() => null)) as ListResponse<T> & ApiErrorEnvelope | null;

  if (!response.ok) {
    throw new ApiError(
      payload?.error?.message || `Request failed with HTTP ${response.status}`,
      response.status,
      payload?.error?.code,
    );
  }

  return {
    data: payload?.data ?? [],
    meta: payload?.meta ?? { page: 1, page_size: 0, total: payload?.data?.length ?? 0 },
  };
}

function postJson<T>(path: string, body: unknown): Promise<T> {
  return request<T>(path, {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

export const oceanApi = {
  getDashboardSummary: () => request<DashboardSummary>('/dashboard/summary'),
  listInspectionTasks: () => listRequest<InspectionTask>('/inspection-tasks?page_size=20'),
  getInspectionTask: (id: number) => request<InspectionTask>(`/inspection-tasks/${id}`),
  startInspectionTask: (id: number, operatorId: number) =>
    postJson<MutationResult>(`/inspection-tasks/${id}/start`, { operator_id: operatorId }),
  submitInspectionTask: (id: number, operatorId: number) =>
    postJson<MutationResult>(`/inspection-tasks/${id}/submit`, { operator_id: operatorId }),

  listSamples: () => listRequest<Sample>('/samples?page_size=20'),
  getSample: (id: number) => request<Sample>(`/samples/${id}`),
  createSample: (payload: SampleCreatePayload) => postJson<MutationResult>('/samples', payload),

  listSampleResults: (sampleId: number) =>
    listRequest<SampleResult>(`/samples/${sampleId}/results?page_size=20`),
  createSampleResult: (sampleId: number, payload: SampleResultCreatePayload) =>
    postJson<MutationResult>(`/samples/${sampleId}/results`, payload),

  listExceptions: () => listRequest<ExceptionRecord>('/exceptions?page_size=20'),
  createException: (payload: ExceptionCreatePayload) => postJson<MutationResult>('/exceptions', payload),
  resolveException: (id: number, resolvedBy: number) =>
    postJson<MutationResult>(`/exceptions/${id}/resolve`, { resolved_by: resolvedBy }),

  listAnalysisJobs: () => listRequest<AnalysisJob>('/analysis-jobs?page_size=20'),
  createAnalysisJob: (payload: AnalysisJobCreatePayload) =>
    postJson<MutationResult>('/analysis-jobs', payload),
  cancelAnalysisJob: (id: number) => postJson<MutationResult>(`/analysis-jobs/${id}/cancel`, {}),
  retryAnalysisJob: (id: number) => postJson<MutationResult>(`/analysis-jobs/${id}/retry`, {}),
};
