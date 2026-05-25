import { Button, Group } from '@mantine/core';
import type { TFunction } from 'i18next';
import type { InspectionTask } from '../../../api/client';
import { DetailCard } from '../../../components/workspace/DetailCard';
import { EntityList } from '../../../components/workspace/EntityList';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import { formatDate } from '../utils';

type TasksPanelProps = {
  t: TFunction;
  language: string;
  tasks: InspectionTask[];
  selectedTaskId: number | null;
  selectedTask: InspectionTask | null;
  onSelectTask: (id: number) => void;
  onTaskAction: (action: 'start' | 'submit') => Promise<void>;
  onReportException: (resourceType: 'inspection_task', resourceId: number) => void;
};

export function TasksPanel({
  t,
  language,
  tasks,
  selectedTaskId,
  selectedTask,
  onSelectTask,
  onTaskAction,
  onReportException,
}: TasksPanelProps) {
  return (
    <WorkspacePanel title={t('panels.tasks.title')} subtitle={t('panels.tasks.subtitle')}>
      <EntityList
        emptyText={t('empty.tasks')}
        items={tasks.map((task) => ({
          id: task.id,
          title: task.title,
          meta: `${task.task_code} · ${task.status}`,
        }))}
        selectedId={selectedTaskId}
        onSelect={onSelectTask}
      />
      {selectedTask && (
        <DetailCard
          title={selectedTask.title}
          subtitle={selectedTask.location_text || t('empty.noLocation')}
          status={selectedTask.status}
          details={[
            [t('fields.type'), selectedTask.task_type],
            [t('fields.priority'), selectedTask.priority],
            [t('fields.assignee'), selectedTask.assigned_to?.display_name],
            [t('fields.planned'), formatDate(selectedTask.planned_at, language)],
          ]}
        >
          <Group gap="xs">
            <Button disabled={selectedTask.status !== 'assigned'} onClick={() => void onTaskAction('start')}>
              {t('actions.startTask')}
            </Button>
            <Button disabled={selectedTask.status !== 'in_progress'} onClick={() => void onTaskAction('submit')}>
              {t('actions.submitTask')}
            </Button>
            <Button variant="light" color="orange" onClick={() => onReportException('inspection_task', selectedTask.id)}>
              {t('actions.reportException')}
            </Button>
          </Group>
        </DetailCard>
      )}
    </WorkspacePanel>
  );
}
