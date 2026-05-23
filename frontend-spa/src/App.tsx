import { FormEvent, ReactNode, useCallback, useEffect, useMemo, useState } from 'react';
import {
  ApiError,
  DashboardSummary,
  ExceptionRecord,
  InspectionTask,
  Sample,
  SampleResult,
  apiBase,
  oceanApi,
} from './api/client';

type LoadState = 'idle' | 'loading' | 'ready' | 'error';

type Notice = {
  tone: 'success' | 'error';
  message: string;
};

const emptySummary: DashboardSummary = {
  pending_samples: 0,
  today_inspection_tasks: 0,
  open_exceptions: 0,
  queued_analysis_jobs: 0,
};

function App() {
  const [summary, setSummary] = useState<DashboardSummary>(emptySummary);
  const [tasks, setTasks] = useState<InspectionTask[]>([]);
  const [samples, setSamples] = useState<Sample[]>([]);
  const [results, setResults] = useState<SampleResult[]>([]);
  const [exceptions, setExceptions] = useState<ExceptionRecord[]>([]);
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [selectedTask, setSelectedTask] = useState<InspectionTask | null>(null);
  const [selectedSampleId, setSelectedSampleId] = useState<number | null>(null);
  const [selectedSample, setSelectedSample] = useState<Sample | null>(null);
  const [operatorId, setOperatorId] = useState(2);
  const [analystId, setAnalystId] = useState(3);
  const [loadState, setLoadState] = useState<LoadState>('idle');
  const [notice, setNotice] = useState<Notice | null>(null);
  const [sampleForm, setSampleForm] = useState({
    sample_code: '',
    sample_type: 'water',
    name: '',
    location_text: '',
  });
  const [resultForm, setResultForm] = useState({
    result_type: 'salinity',
    raw_value: '{"value": 31.2, "unit": "ppt"}',
    normalized_value: '{"value": 31.2, "unit": "ppt"}',
    conclusion: 'within_expected_range',
  });
  const [exceptionForm, setExceptionForm] = useState({
    resource_type: 'sample' as 'inspection_task' | 'sample' | 'sample_result',
    resource_id: '',
    category: 'threshold_alert',
    severity: 'medium',
    title: '',
    description: '',
  });

  const refreshWorkspace = useCallback(async () => {
    setLoadState('loading');
    setNotice(null);

    try {
      const [nextSummary, taskList, sampleList, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listInspectionTasks(),
        oceanApi.listSamples(),
        oceanApi.listExceptions(),
      ]);

      setSummary(nextSummary);
      setTasks(taskList.data);
      setSamples(sampleList.data);
      setExceptions(exceptionList.data);
      setSelectedTaskId((current) => current ?? taskList.data[0]?.id ?? null);
      setSelectedSampleId((current) => current ?? sampleList.data[0]?.id ?? null);
      setLoadState('ready');
    } catch (error) {
      setLoadState('error');
      setNotice({ tone: 'error', message: formatError(error) });
    }
  }, []);

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
      .catch((error) => setNotice({ tone: 'error', message: formatError(error) }));

    return () => {
      ignore = true;
    };
  }, [selectedTaskId]);

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
      .catch((error) => setNotice({ tone: 'error', message: formatError(error) }));

    return () => {
      ignore = true;
    };
  }, [selectedSampleId]);

  const activeExceptions = useMemo(
    () => exceptions.filter((exception) => exception.status === 'open'),
    [exceptions],
  );

  async function handleTaskAction(action: 'start' | 'submit') {
    if (!selectedTaskId) return;

    try {
      if (action === 'start') {
        await oceanApi.startInspectionTask(selectedTaskId, operatorId);
      } else {
        await oceanApi.submitInspectionTask(selectedTaskId, operatorId);
      }
      setNotice({ tone: 'success', message: `Inspection task ${action} action completed.` });
      await refreshWorkspace();
      setSelectedTaskId(selectedTaskId);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error) });
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
      setNotice({ tone: 'success', message: `Sample ${created.id} created.` });
      setSampleForm((current) => ({ ...current, sample_code: '', name: '' }));
      await refreshWorkspace();
      setSelectedSampleId(created.id);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error) });
    }
  }

  async function handleCreateResult(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!selectedSampleId) return;

    try {
      await oceanApi.createSampleResult(selectedSampleId, {
        result_type: resultForm.result_type.trim(),
        raw_value: parseJsonObject(resultForm.raw_value, 'raw value'),
        normalized_value: parseJsonObject(resultForm.normalized_value, 'normalized value'),
        conclusion: optionalText(resultForm.conclusion),
        entered_by: analystId || undefined,
      });
      setNotice({ tone: 'success', message: 'Sample result saved as draft.' });
      const [sample, resultList] = await Promise.all([
        oceanApi.getSample(selectedSampleId),
        oceanApi.listSampleResults(selectedSampleId),
      ]);
      setSelectedSample(sample);
      setResults(resultList.data);
      const nextSummary = await oceanApi.getDashboardSummary();
      setSummary(nextSummary);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error) });
    }
  }

  async function handleResolveException(exceptionId: number) {
    try {
      await oceanApi.resolveException(exceptionId, analystId);
      setNotice({ tone: 'success', message: `Exception ${exceptionId} resolved.` });
      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);
      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error) });
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
      setNotice({ tone: 'success', message: `Exception ${created.id} opened.` });
      setExceptionForm((current) => ({ ...current, title: '', description: '' }));
      const [nextSummary, exceptionList] = await Promise.all([
        oceanApi.getDashboardSummary(),
        oceanApi.listExceptions(),
      ]);
      setSummary(nextSummary);
      setExceptions(exceptionList.data);
    } catch (error) {
      setNotice({ tone: 'error', message: formatError(error) });
    }
  }

  function setExceptionTarget(resourceType: 'inspection_task' | 'sample' | 'sample_result', resourceId: number | null) {
    setExceptionForm((current) => ({
      ...current,
      resource_type: resourceType,
      resource_id: resourceId?.toString() ?? '',
    }));
  }

  return (
    <main className="workspace-shell">
      <header className="workspace-hero">
        <div>
          <span className="eyebrow">Ocean v1.2.0</span>
          <h1>Core Operations Workspace</h1>
          <p className="lead">
            Connected P0 flows for inspection tasks, samples, results, exceptions, and dashboard
            summary. API base: <code>{apiBase}</code>
          </p>
        </div>
        <div className="identity-panel" aria-label="Transitional identity controls">
          <label>
            Operator ID
            <input
              min="1"
              type="number"
              value={operatorId}
              onChange={(event) => setOperatorId(Number(event.target.value))}
            />
          </label>
          <label>
            Analyst ID
            <input
              min="1"
              type="number"
              value={analystId}
              onChange={(event) => setAnalystId(Number(event.target.value))}
            />
          </label>
          <button className="ghost-button" onClick={() => void refreshWorkspace()} type="button">
            Refresh
          </button>
        </div>
      </header>

      {notice && <div className={`notice ${notice.tone}`}>{notice.message}</div>}
      {loadState === 'loading' && <div className="notice neutral">Loading live workspace data…</div>}

      <section className="summary-grid" aria-label="Dashboard summary">
        <MetricCard label="Pending samples" value={summary.pending_samples} />
        <MetricCard label="Today's tasks" value={summary.today_inspection_tasks} />
        <MetricCard label="Open exceptions" value={summary.open_exceptions} />
        <MetricCard label="Queued analysis" value={summary.queued_analysis_jobs} />
      </section>

      <section className="workspace-grid">
        <Panel title="Inspection tasks" subtitle="List, detail, start, and submit actions.">
          <EntityList
            emptyText="No inspection tasks returned by the API."
            items={tasks.map((task) => ({
              id: task.id,
              title: task.title,
              meta: `${task.task_code} · ${task.status}`,
            }))}
            selectedId={selectedTaskId}
            onSelect={setSelectedTaskId}
          />
          {selectedTask && (
            <div className="detail-card">
              <div className="detail-heading">
                <div>
                  <h3>{selectedTask.title}</h3>
                  <p>{selectedTask.location_text || 'No location recorded'}</p>
                </div>
                <StatusBadge value={selectedTask.status} />
              </div>
              <dl className="detail-grid">
                <Detail label="Type" value={selectedTask.task_type} />
                <Detail label="Priority" value={selectedTask.priority} />
                <Detail label="Assignee" value={selectedTask.assigned_to?.display_name} />
                <Detail label="Planned" value={formatDate(selectedTask.planned_at)} />
              </dl>
              <div className="action-row">
                <button
                  disabled={selectedTask.status !== 'assigned'}
                  onClick={() => void handleTaskAction('start')}
                  type="button"
                >
                  Start task
                </button>
                <button
                  disabled={selectedTask.status !== 'in_progress'}
                  onClick={() => void handleTaskAction('submit')}
                  type="button"
                >
                  Submit task
                </button>
                <button
                  className="secondary-button"
                  onClick={() => setExceptionTarget('inspection_task', selectedTask.id)}
                  type="button"
                >
                  Report exception
                </button>
              </div>
            </div>
          )}
        </Panel>

        <Panel title="Samples" subtitle="Create samples and inspect current sample details.">
          <form className="compact-form" onSubmit={handleCreateSample}>
            <input
              placeholder="Sample code"
              required
              value={sampleForm.sample_code}
              onChange={(event) => setSampleForm({ ...sampleForm, sample_code: event.target.value })}
            />
            <input
              placeholder="Type"
              required
              value={sampleForm.sample_type}
              onChange={(event) => setSampleForm({ ...sampleForm, sample_type: event.target.value })}
            />
            <input
              placeholder="Name"
              value={sampleForm.name}
              onChange={(event) => setSampleForm({ ...sampleForm, name: event.target.value })}
            />
            <input
              placeholder="Location"
              value={sampleForm.location_text}
              onChange={(event) => setSampleForm({ ...sampleForm, location_text: event.target.value })}
            />
            <button type="submit">Create sample</button>
          </form>

          <EntityList
            emptyText="No samples returned by the API."
            items={samples.map((sample) => ({
              id: sample.id,
              title: sample.name || sample.sample_code,
              meta: `${sample.sample_code} · ${sample.sample_type} · ${sample.status}`,
            }))}
            selectedId={selectedSampleId}
            onSelect={setSelectedSampleId}
          />

          {selectedSample && (
            <div className="detail-card">
              <div className="detail-heading">
                <div>
                  <h3>{selectedSample.name || selectedSample.sample_code}</h3>
                  <p>{selectedSample.location_text || 'No location recorded'}</p>
                </div>
                <StatusBadge value={selectedSample.status} />
              </div>
              <dl className="detail-grid">
                <Detail label="Code" value={selectedSample.sample_code} />
                <Detail label="Type" value={selectedSample.sample_type} />
                <Detail label="Task" value={selectedSample.inspection_task_id?.toString()} />
                <Detail
                  label="Collector"
                  value={selectedSample.collector?.display_name || selectedSample.collector_name}
                />
              </dl>
              <div className="action-row">
                <button
                  className="secondary-button"
                  onClick={() => setExceptionTarget('sample', selectedSample.id)}
                  type="button"
                >
                  Report exception
                </button>
              </div>
            </div>
          )}
        </Panel>

        <Panel title="Sample results" subtitle="Review and add draft results for the selected sample.">
          {selectedSampleId ? (
            <form className="result-form" onSubmit={handleCreateResult}>
              <input
                placeholder="Result type"
                required
                value={resultForm.result_type}
                onChange={(event) => setResultForm({ ...resultForm, result_type: event.target.value })}
              />
              <textarea
                aria-label="Raw value JSON"
                value={resultForm.raw_value}
                onChange={(event) => setResultForm({ ...resultForm, raw_value: event.target.value })}
              />
              <textarea
                aria-label="Normalized value JSON"
                value={resultForm.normalized_value}
                onChange={(event) =>
                  setResultForm({ ...resultForm, normalized_value: event.target.value })
                }
              />
              <input
                placeholder="Conclusion"
                value={resultForm.conclusion}
                onChange={(event) => setResultForm({ ...resultForm, conclusion: event.target.value })}
              />
              <button type="submit">Add draft result</button>
            </form>
          ) : (
            <EmptyState text="Select a sample before entering results." />
          )}

          <div className="record-stack">
            {results.length === 0 ? (
              <EmptyState text="No results for the selected sample." />
            ) : (
              results.map((result) => (
                <article className="record-card" key={result.id}>
                  <div>
                    <strong>{result.result_type}</strong>
                    <p>{result.conclusion || 'No conclusion recorded'}</p>
                  </div>
                  <div className="exception-actions">
                    <StatusBadge value={result.status} />
                    <button
                      className="secondary-button"
                      onClick={() => setExceptionTarget('sample_result', result.id)}
                      type="button"
                    >
                      Report
                    </button>
                  </div>
                </article>
              ))
            )}
          </div>
        </Panel>

        <Panel title="Exceptions" subtitle="Create and resolve exceptions in the operational chain.">
          <form className="exception-form" onSubmit={handleCreateException}>
            <select
              value={exceptionForm.resource_type}
              onChange={(event) =>
                setExceptionForm({
                  ...exceptionForm,
                  resource_type: event.target.value as 'inspection_task' | 'sample' | 'sample_result',
                })
              }
            >
              <option value="inspection_task">Inspection task</option>
              <option value="sample">Sample</option>
              <option value="sample_result">Sample result</option>
            </select>
            <input
              min="1"
              placeholder="Resource ID"
              required
              type="number"
              value={exceptionForm.resource_id}
              onChange={(event) =>
                setExceptionForm({ ...exceptionForm, resource_id: event.target.value })
              }
            />
            <input
              placeholder="Category"
              required
              value={exceptionForm.category}
              onChange={(event) => setExceptionForm({ ...exceptionForm, category: event.target.value })}
            />
            <select
              value={exceptionForm.severity}
              onChange={(event) => setExceptionForm({ ...exceptionForm, severity: event.target.value })}
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
            <input
              className="wide-field"
              placeholder="Title"
              required
              value={exceptionForm.title}
              onChange={(event) => setExceptionForm({ ...exceptionForm, title: event.target.value })}
            />
            <textarea
              className="wide-field"
              placeholder="Description"
              value={exceptionForm.description}
              onChange={(event) =>
                setExceptionForm({ ...exceptionForm, description: event.target.value })
              }
            />
            <button type="submit">Open exception</button>
          </form>
          <div className="record-stack">
            {exceptions.length === 0 ? (
              <EmptyState text="No exceptions returned by the API." />
            ) : (
              exceptions.map((exception) => (
                <article className="record-card" key={exception.id}>
                  <div>
                    <strong>{exception.title}</strong>
                    <p>
                      {exception.resource_type} #{exception.resource_id} · {exception.category} ·{' '}
                      {exception.severity}
                    </p>
                  </div>
                  <div className="exception-actions">
                    <StatusBadge value={exception.status} />
                    <button
                      disabled={exception.status !== 'open'}
                      onClick={() => void handleResolveException(exception.id)}
                      type="button"
                    >
                      Resolve
                    </button>
                  </div>
                </article>
              ))
            )}
          </div>
          <p className="hint">Open exceptions in this view: {activeExceptions.length}</p>
        </Panel>
      </section>
    </main>
  );
}

function MetricCard({ label, value }: { label: string; value: number }) {
  return (
    <article className="metric-card">
      <span>{label}</span>
      <strong>{value}</strong>
    </article>
  );
}

function Panel({ title, subtitle, children }: { title: string; subtitle: string; children: ReactNode }) {
  return (
    <section className="panel">
      <div className="panel-header">
        <h2>{title}</h2>
        <p>{subtitle}</p>
      </div>
      {children}
    </section>
  );
}

function EntityList({
  emptyText,
  items,
  selectedId,
  onSelect,
}: {
  emptyText: string;
  items: Array<{ id: number; title: string; meta: string }>;
  selectedId: number | null;
  onSelect: (id: number) => void;
}) {
  if (items.length === 0) return <EmptyState text={emptyText} />;

  return (
    <div className="entity-list">
      {items.map((item) => (
        <button
          className={item.id === selectedId ? 'entity-row active' : 'entity-row'}
          key={item.id}
          onClick={() => onSelect(item.id)}
          type="button"
        >
          <span>{item.title}</span>
          <small>{item.meta}</small>
        </button>
      ))}
    </div>
  );
}

function StatusBadge({ value }: { value: string }) {
  return <span className={`status-badge ${value.replace(/_/g, '-')}`}>{value}</span>;
}

function Detail({ label, value }: { label: string; value?: string | null }) {
  return (
    <div>
      <dt>{label}</dt>
      <dd>{value || '—'}</dd>
    </div>
  );
}

function EmptyState({ text }: { text: string }) {
  return <div className="empty-state">{text}</div>;
}

function optionalText(value: string): string | undefined {
  const trimmed = value.trim();
  return trimmed ? trimmed : undefined;
}

function parseJsonObject(value: string, label: string): Record<string, unknown> | undefined {
  if (!value.trim()) return undefined;
  const parsed = JSON.parse(value) as unknown;

  if (!parsed || Array.isArray(parsed) || typeof parsed !== 'object') {
    throw new Error(`${label} must be a JSON object.`);
  }

  return parsed as Record<string, unknown>;
}

function formatDate(value?: string | null): string {
  if (!value) return '—';
  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}

function formatError(error: unknown): string {
  if (error instanceof ApiError) {
    return error.code ? `${error.code}: ${error.message}` : error.message;
  }
  if (error instanceof Error) return error.message;
  return 'Unexpected error.';
}

export default App;
