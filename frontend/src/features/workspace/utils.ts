import type { TFunction } from 'i18next';
import { AnalysisJob, ApiError } from '../../api/client';

export function optionalText(value: string): string | undefined {
  const trimmed = value.trim();
  return trimmed ? trimmed : undefined;
}

export function parseJsonObject(
  value: string,
  label: string,
  t: TFunction,
): Record<string, unknown> | undefined {
  if (!value.trim()) return undefined;
  const parsed = JSON.parse(value) as unknown;

  if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
    throw new Error(t('notices.jsonObject', { label }));
  }

  return parsed as Record<string, unknown>;
}

export function formatDate(value: string | null | undefined, language: string): string {
  if (!value) return '—';

  return new Intl.DateTimeFormat(language === 'zh-Hans' ? 'zh-CN' : 'en', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

export function formatError(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    if (error.status === 403 || error.code === 'FORBIDDEN') {
      return fallback;
    }

    return error.code ? `${error.code}: ${error.message}` : error.message;
  }

  if (error instanceof Error) return error.message;
  return fallback;
}

export function analysisJobGuidance(job: AnalysisJob, t: TFunction): string {
  if (job.status === 'queued') return t('jobs.queued', { sampleId: job.sample_id });
  if (job.status === 'running') return t('jobs.running', { sampleId: job.sample_id });
  if (job.status === 'succeeded') return job.result_summary || t('jobs.succeeded');
  if (job.status === 'failed') return t('jobs.failed');
  if (job.status === 'cancelled') return t('jobs.cancelled');
  return t('jobs.fallback', { sampleId: job.sample_id });
}
