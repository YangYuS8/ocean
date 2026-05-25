import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import type {
  AnalysisJob,
  DashboardSummary,
  ExceptionRecord,
  InspectionTask,
  Sample,
  SampleResult,
} from '../../api/client';
import { oceanApi } from '../../api/client';
import type {
  AnalysisJobForm,
  ExceptionForm,
  LoadState,
  Notice,
  ResultForm,
  SampleForm,
  ResourceType,
} from './types';
import {
  emptySummary,
  initialAnalysisJobForm,
  initialExceptionForm,
  initialResultForm,
  initialSampleForm,
} from './types';
import { formatError, optionalText, parseJsonObject } from './utils';

export function useWorkspace() {
  const { t } = useTranslation();

  const [summary, setSummary] = useState<DashboardSummary>(emptySummary);
  const [tasks, setTasks] = useState<InspectionTask[]>([]);
  const [samples, setSamples] = useState<Sample[]>([]);
  const [results, setResults] = useState<SampleResult[]>([]);
  const [exceptions, setExceptions] = useState<ExceptionRecord[]>([]);
  const [analysisJobs, setAnalysisJobs] = useState<AnalysisJob[]>([]);
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [selectedTask, setSelectedTask] = useState<InspectionTask | null>(null);
  const [selectedSampleId, setSelectedSampleId] = useState<number | null>(null);
  const [selectedSample, setSelectedSample] = useState<Sample | null>(null);
  const [operatorId, setOperatorId] = useState(2);
  const [analystId, setAnalystId] = useState(3);
  const [loadState, setLoadState] = useState<LoadState>('idle');
  const [notice, setNotice] = useState<Notice | null>(null);
  const [sampleForm, setSampleForm] = useState<SampleForm>(initialSampleForm);
  const [resultForm, setResultForm] = useState<ResultForm>(initialResultForm);
  const [exceptionForm, setExceptionForm] = useState<ExceptionForm>(initialExceptionForm);
  const [analysisJobForm, setAnalysisJobForm] = useState<AnalysisJobForm>(initialAnalysisJobForm);

  const refreshWorkspace = useCallback(async () => {
    setLoadState('loading');
    setNotice(null);

    try {
      const [nextSummary, taskList, sampleList, exceptionList, analysisJobList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listInspectionTasks(),
        oceanApi.listSamples(),
        oceanApi.listExceptions(),
        oceanApi.listAnalysisJobs(),
      ]);

      setSummary(nextSummary);
      setTasks(taskList.data);
      setSamples(sampleList.data);
      setExceptions(exceptionList.data);
      setAnalysisJobs(analysisJobList.data);
      setSelectedTaskId((current) => current ?? taskList.data[0]?.id ?? null);
      setSelectedSampleId((current) => current ?? sampleList.data[0]?.id ?? null);
      setLoadState('ready');
    } catch (error) {
      setLoadState('error');
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }, [t]);

  useEffect(() => {
    void refreshWorkspace();
  }, [refreshWorkspace]);

  useEffect(() => {
    if (!selectedTaskId) {
      setSelectedTask(null);
      return;
    }

    let ignore = false;
    oceanApi
      .getInspectionTask(selectedTaskId)
      .then((task) => {
        if (!ignore) setSelectedTask(task);
      })
      .catch((error) => setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) }));

    return () => {
      ignore = true;
    };
  }, [selectedTaskId, t]);

  useEffect(() => {
    if (!selectedSampleId) {
      setSelectedSample(null);
      setResults([]);
      return;
    }

    let ignore = false;
    Promise.all([oceanApi.getSample(selectedSampleId), oceanApi.listSampleResults(selectedSampleId)])
      .then(([sample, resultList]) => {
        if (!ignore) {
          setSelectedSample(sample);
          setResults(resultList.data);
        }
      })
      .catch((error) => setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) }));

    return () => {
      ignore = true;
    };
  }, [selectedSampleId, t]);

  const activeExceptions = useMemo(
    () => exceptions.filter((exception) => exception.status === 'open'),
    [exceptions],
  );

  const activeAnalysisJobs = useMemo(
    () => analysisJobs.filter((job) => job.status === 'queued' || job.status === 'running'),
    [analysisJobs],
  );

  async function handleTaskAction(action: 'start' | 'submit') {
    if (!selectedTaskId) return;

    try {
      if (action === 'start') {
        await oceanApi.startInspectionTask(selectedTaskId, operatorId);
      } else {
        await oceanApi.submitInspectionTask(selectedTaskId, operatorId);
      }

      setNotice({ tone: 'success', message: t('notices.taskDone', { action }) });
      await refreshWorkspace();
      setSelectedTaskId(selectedTaskId);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleCreateSample(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createSample({
        sample_code: sampleForm.sample_code.trim(),
        sample_type: sampleForm.sample_type.trim(),
        inspection_task_id: selectedTaskId ?? undefined,
        name: optionalText(sampleForm.name),
        location_text: optionalText(sampleForm.location_text),
        collector_id: operatorId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.sampleCreated', { id: created.id }) });
      setSampleForm((current) => ({ ...current, sample_code: '', name: '' }));
      await refreshWorkspace();
      setSelectedSampleId(created.id);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleCreateResult(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedSampleId) return;

    try {
      await oceanApi.createSampleResult(selectedSampleId, {
        result_type: resultForm.result_type.trim(),
        raw_value: parseJsonObject(resultForm.raw_value, t('fields.rawValue'), t),
        normalized_value: parseJsonObject(resultForm.normalized_value, t('fields.normalizedValue'), t),
        conclusion: optionalText(resultForm.conclusion),
        entered_by: analystId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.resultSaved') });
      const [sample, resultList, nextSummary] = await Promise.all([
        oceanApi.getSample(selectedSampleId),
        oceanApi.listSampleResults(selectedSampleId),
        oceanApi.getDashboardSummary(),
      ]);

      setSelectedSample(sample);
      setResults(resultList.data);
      setSummary(nextSummary);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleResolveException(exceptionId: number) {
    try {
      await oceanApi.resolveException(exceptionId, analystId);
      setNotice({ tone: 'success', message: t('notices.exceptionResolved', { id: exceptionId }) });

      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);

      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleCreateException(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createException({
        resource_type: exceptionForm.resource_type,
        resource_id: Number(exceptionForm.resource_id),
        category: exceptionForm.category.trim(),
        severity: optionalText(exceptionForm.severity),
        title: exceptionForm.title.trim(),
        description: optionalText(exceptionForm.description),
        reported_by: analystId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.exceptionOpened', { id: created.id }) });
      setExceptionForm((current) => ({ ...current, title: '', description: '' }));

      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);

      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleCreateAnalysisJob(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    try {
      const created = await oceanApi.createAnalysisJob({
        sample_id: Number(analysisJobForm.sample_id),
        job_type: analysisJobForm.job_type.trim(),
        queued_by: analystId || undefined,
      });

      setNotice({ tone: 'success', message: t('notices.analysisQueued', { id: created.id }) });
      await refreshWorkspace();
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  async function handleAnalysisJobAction(jobId: number, action: 'cancel' | 'retry') {
    try {
      if (action === 'cancel') {
        await oceanApi.cancelAnalysisJob(jobId);
      } else {
        await oceanApi.retryAnalysisJob(jobId);
      }

      setNotice({ tone: 'success', message: t('notices.analysisActionDone', { action }) });
      await refreshWorkspace();
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error, t('notices.unexpectedError')) });
    }
  }

  function setExceptionTarget(resourceType: ResourceType, resourceId: number | null) {
    setExceptionForm((current) => ({
      ...current,
      resource_type: resourceType,
      resource_id: resourceId?.toString() ?? '',
    }));
  }

  function setAnalysisJobTarget(sampleId: number | null, jobType = 'quality_assessment') {
    setAnalysisJobForm({
      sample_id: sampleId?.toString() ?? '',
      job_type: jobType,
    });
  }

  return {
    summary,
    tasks,
    samples,
    results,
    exceptions,
    analysisJobs,
    selectedTaskId,
    selectedTask,
    selectedSampleId,
    selectedSample,
    operatorId,
    analystId,
    loadState,
    notice,
    sampleForm,
    resultForm,
    exceptionForm,
    analysisJobForm,
    activeExceptions,
    activeAnalysisJobs,
    setSelectedTaskId,
    setSelectedSampleId,
    setOperatorId,
    setAnalystId,
    setSampleForm,
    setResultForm,
    setExceptionForm,
    setAnalysisJobForm,
    refreshWorkspace,
    handleTaskAction,
    handleCreateSample,
    handleCreateResult,
    handleResolveException,
    handleCreateException,
    handleCreateAnalysisJob,
    handleAnalysisJobAction,
    setExceptionTarget,
    setAnalysisJobTarget,
  };
}
