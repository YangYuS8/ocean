import { Button, Group, SimpleGrid, TextInput } from '@mantine/core';
import type { FormEvent } from 'react';
import type { TFunction } from 'i18next';
import type { Sample } from '../../../api/client';
import { DetailCard } from '../../../components/workspace/DetailCard';
import { EntityList } from '../../../components/workspace/EntityList';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { SampleForm } from '../types';

type SamplesPanelProps = {
  t: TFunction;
  samples: Sample[];
  selectedSampleId: number | null;
  selectedSample: Sample | null;
  sampleForm: SampleForm;
  onSampleFormChange: (value: SampleForm) => void;
  onSelectSample: (id: number) => void;
  onCreateSample: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onReportException: (resourceType: 'sample', resourceId: number) => void;
  onQueueAnalysis: (sampleId: number) => void;
};

export function SamplesPanel({
  t,
  samples,
  selectedSampleId,
  selectedSample,
  sampleForm,
  onSampleFormChange,
  onSelectSample,
  onCreateSample,
  onReportException,
  onQueueAnalysis,
}: SamplesPanelProps) {
  return (
    <WorkspacePanel title={t('panels.samples.title')} subtitle={t('panels.samples.subtitle')}>
      <form className="form-surface" onSubmit={(event) => void onCreateSample(event)}>
        <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="sm">
          <TextInput
            label={t('fields.sampleCode')}
            required
            value={sampleForm.sample_code}
            onChange={(event) => onSampleFormChange({ ...sampleForm, sample_code: event.currentTarget.value })}
          />
          <TextInput
            label={t('fields.type')}
            required
            value={sampleForm.sample_type}
            onChange={(event) => onSampleFormChange({ ...sampleForm, sample_type: event.currentTarget.value })}
          />
          <TextInput
            label={t('fields.name')}
            value={sampleForm.name}
            onChange={(event) => onSampleFormChange({ ...sampleForm, name: event.currentTarget.value })}
          />
          <TextInput
            label={t('fields.location')}
            value={sampleForm.location_text}
            onChange={(event) => onSampleFormChange({ ...sampleForm, location_text: event.currentTarget.value })}
          />
        </SimpleGrid>
        <Button type="submit" mt="sm">
          {t('actions.createSample')}
        </Button>
      </form>
      <EntityList
        emptyText={t('empty.samples')}
        items={samples.map((sample) => ({
          id: sample.id,
          title: sample.name || sample.sample_code,
          meta: `${sample.sample_code} · ${sample.sample_type} · ${sample.status}`,
        }))}
        selectedId={selectedSampleId}
        onSelect={onSelectSample}
      />
      {selectedSample && (
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
          <Group gap="xs">
            <Button variant="light" color="orange" onClick={() => onReportException('sample', selectedSample.id)}>
              {t('actions.reportException')}
            </Button>
            <Button variant="light" color="ocean" onClick={() => onQueueAnalysis(selectedSample.id)}>
              {t('actions.queueAnalysis')}
            </Button>
          </Group>
        </DetailCard>
      )}
    </WorkspacePanel>
  );
}
