export type WorkspaceGuidanceInput = {
  sample: { status: string } | null
  results: Array<{ id?: number | string }>
  exceptions: Array<{ status: string; title?: string | null }>
  analysisJobs: Array<{ status: string; job_type?: string | null }>
}

export type WorkspaceGuidance = {
  summaryTone: 'primary' | 'success' | 'warning' | 'error' | 'info' | 'neutral' | 'secondary'
  summaryTitle: string
  summaryDescription: string
  stats: Array<{ label: string; value: string }>
  risks: Array<{ kind: string; title: string; description: string }>
  nextStep: { action: string; title: string; description: string }
}

export function buildSampleWorkspaceGuidance(input: WorkspaceGuidanceInput): WorkspaceGuidance
