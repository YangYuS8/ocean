import { Button, Stack, TextInput, Textarea } from '@mantine/core';
import type { FormEvent } from 'react';
import type { TFunction } from 'i18next';
import type { SampleResult } from '../../../api/client';
import { EmptyState } from '../../../components/workspace/EmptyState';
import { RecordCard } from '../../../components/workspace/RecordCard';
import { RecordStack } from '../../../components/workspace/RecordStack';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { ResultForm } from '../types';

type ResultsPanelProps = {
  t: TFunction;
  selectedSampleId: number | null;
  resultForm: ResultForm;
  results: SampleResult[];
  onResultFormChange: (value: ResultForm) => void;
  onCreateResult: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onReportException: (resourceType: 'sample_result', resourceId: number) => void;
};

export function ResultsPanel({
  t,
  selectedSampleId,
  resultForm,
  results,
  onResultFormChange,
  onCreateResult,
  onReportException,
}: ResultsPanelProps) {
  return (
    <WorkspacePanel title={t('panels.results.title')} subtitle={t('panels.results.subtitle')}>
      {selectedSampleId ? (
        <form className="form-surface" onSubmit={(event) => void onCreateResult(event)}>
          <Stack gap="sm">
            <TextInput
              label={t('fields.resultType')}
              required
              value={resultForm.result_type}
              onChange={(event) => onResultFormChange({ ...resultForm, result_type: event.currentTarget.value })}
            />
            <Textarea
              label={t('fields.rawValue')}
              autosize
              minRows={2}
              value={resultForm.raw_value}
              onChange={(event) => onResultFormChange({ ...resultForm, raw_value: event.currentTarget.value })}
            />
            <Textarea
              label={t('fields.normalizedValue')}
              autosize
              minRows={2}
              value={resultForm.normalized_value}
              onChange={(event) => onResultFormChange({ ...resultForm, normalized_value: event.currentTarget.value })}
            />
            <TextInput
              label={t('fields.conclusion')}
              value={resultForm.conclusion}
              onChange={(event) => onResultFormChange({ ...resultForm, conclusion: event.currentTarget.value })}
            />
            <Button type="submit">{t('actions.addDraftResult')}</Button>
          </Stack>
        </form>
      ) : (
        <EmptyState text={t('empty.selectSample')} />
      )}

      <RecordStack emptyText={t('empty.results')} isEmpty={results.length === 0}>
        {results.map((result) => (
          <RecordCard
            key={result.id}
            title={result.result_type}
            description={result.conclusion || t('empty.noConclusion')}
            status={result.status}
          >
            <Button size="xs" variant="light" color="orange" onClick={() => onReportException('sample_result', result.id)}>
              {t('actions.report')}
            </Button>
          </RecordCard>
        ))}
      </RecordStack>
    </WorkspacePanel>
  );
}
