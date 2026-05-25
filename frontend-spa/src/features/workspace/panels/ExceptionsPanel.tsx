import { Button, Select, SimpleGrid, TextInput, Textarea } from '@mantine/core';
import type { FormEvent } from 'react';
import type { TFunction } from 'i18next';
import type { ExceptionRecord } from '../../../api/client';
import { RecordCard } from '../../../components/workspace/RecordCard';
import { RecordStack } from '../../../components/workspace/RecordStack';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { ExceptionForm } from '../types';
import { resourceTypeOptions, severityOptions } from '../types';

type ExceptionsPanelProps = {
  t: TFunction;
  exceptionForm: ExceptionForm;
  exceptions: ExceptionRecord[];
  activeExceptionsCount: number;
  onExceptionFormChange: (value: ExceptionForm) => void;
  onCreateException: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onResolveException: (exceptionId: number) => Promise<void>;
};

export function ExceptionsPanel({
  t,
  exceptionForm,
  exceptions,
  activeExceptionsCount,
  onExceptionFormChange,
  onCreateException,
  onResolveException,
}: ExceptionsPanelProps) {
  return (
    <WorkspacePanel title={t('panels.exceptions.title')} subtitle={t('panels.exceptions.subtitle')}>
      <form className="form-surface" onSubmit={(event) => void onCreateException(event)}>
        <SimpleGrid cols={{ base: 1, sm: 2 }} spacing="sm">
          <Select
            label={t('fields.resourceType')}
            data={resourceTypeOptions.map((value) => ({ value, label: t(`resourceTypes.${value}`) }))}
            value={exceptionForm.resource_type}
            onChange={(value) => value && onExceptionFormChange({ ...exceptionForm, resource_type: value as ExceptionForm['resource_type'] })}
          />
          <TextInput
            label={t('fields.resourceId')}
            min="1"
            required
            type="number"
            value={exceptionForm.resource_id}
            onChange={(event) => onExceptionFormChange({ ...exceptionForm, resource_id: event.currentTarget.value })}
          />
          <TextInput
            label={t('fields.category')}
            required
            value={exceptionForm.category}
            onChange={(event) => onExceptionFormChange({ ...exceptionForm, category: event.currentTarget.value })}
          />
          <Select
            label={t('fields.severity')}
            data={severityOptions.map((value) => ({ value, label: t(`severity.${value}`) }))}
            value={exceptionForm.severity}
            onChange={(value) => value && onExceptionFormChange({ ...exceptionForm, severity: value as ExceptionForm['severity'] })}
          />
        </SimpleGrid>
        <TextInput
          label={t('fields.title')}
          required
          mt="sm"
          value={exceptionForm.title}
          onChange={(event) => onExceptionFormChange({ ...exceptionForm, title: event.currentTarget.value })}
        />
        <Textarea
          label={t('fields.description')}
          mt="sm"
          value={exceptionForm.description}
          onChange={(event) => onExceptionFormChange({ ...exceptionForm, description: event.currentTarget.value })}
        />
        <Button type="submit" mt="sm" color="orange">
          {t('actions.openException')}
        </Button>
      </form>
      <RecordStack
        emptyText={t('empty.exceptions')}
        isEmpty={exceptions.length === 0}
        footer={`${t('metrics.openExceptions')}: ${activeExceptionsCount}`}
      >
        {exceptions.map((exception) => (
          <RecordCard
            key={exception.id}
            title={exception.title}
            description={`${exception.resource_type} #${exception.resource_id} · ${exception.category} · ${exception.severity}`}
            status={exception.status}
          >
            <Button size="xs" disabled={exception.status !== 'open'} onClick={() => void onResolveException(exception.id)}>
              {t('actions.resolve')}
            </Button>
          </RecordCard>
        ))}
      </RecordStack>
    </WorkspacePanel>
  );
}
