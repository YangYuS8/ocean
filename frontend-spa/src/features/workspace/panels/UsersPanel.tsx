import type { FormEvent } from 'react';
import { Button, Group, MultiSelect, Pagination, PasswordInput, Select, SimpleGrid, Stack, Text, TextInput } from '@mantine/core';
import type { TFunction } from 'i18next';
import type { ListMeta, UserRecord } from '../../../api/client';
import { DetailCard } from '../../../components/workspace/DetailCard';
import { EmptyState } from '../../../components/workspace/EmptyState';
import { EntityList } from '../../../components/workspace/EntityList';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { UserCreateForm, UserEditForm, UserFilters } from '../types';

const roleOptions = ['admin', 'inspector', 'analyst', 'worker'].map((role) => ({ value: role, label: role }));
const statusOptions = [{ value: 'active', label: 'active' }, { value: 'inactive', label: 'inactive' }];

type UsersPanelProps = {
  t: TFunction;
  users: UserRecord[];
  usersMeta: ListMeta;
  selectedUserId: number | null;
  selectedUser: UserRecord | null;
  filters: UserFilters;
  createForm: UserCreateForm;
  editForm: UserEditForm;
  forbidden: boolean;
  isAdmin: boolean;
  onFiltersChange: (value: UserFilters) => void;
  onCreateFormChange: (value: UserCreateForm) => void;
  onEditFormChange: (value: UserEditForm) => void;
  onSelectUser: (id: number) => void;
  onCreateUser: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onUpdateUser: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onActivation: (action: 'activate' | 'deactivate') => Promise<void>;
  onPageChange: (page: number) => void;
};

export function UsersPanel({
  t,
  users,
  usersMeta,
  selectedUserId,
  selectedUser,
  filters,
  createForm,
  editForm,
  forbidden,
  isAdmin,
  onFiltersChange,
  onCreateFormChange,
  onEditFormChange,
  onSelectUser,
  onCreateUser,
  onUpdateUser,
  onActivation,
  onPageChange,
}: UsersPanelProps) {
  const pageCount = Math.max(1, Math.ceil((usersMeta.total || 0) / (usersMeta.page_size || 20)));

  if (forbidden || !isAdmin) {
    return (
      <WorkspacePanel title={t('panels.users.title')} subtitle={t('panels.users.subtitle')}>
        <EmptyState text={t('empty.usersForbidden')} />
      </WorkspacePanel>
    );
  }

  return (
    <WorkspacePanel title={t('panels.users.title')} subtitle={t('panels.users.subtitle')}>
      <SimpleGrid cols={{ base: 1, md: 3 }} spacing="sm" className="form-surface">
        <TextInput label={t('users.search')} value={filters.search} onChange={(event) => onFiltersChange({ ...filters, search: event.currentTarget.value })} />
        <Select label={t('fields.status')} data={[{ value: '', label: t('users.allStatuses') }, ...statusOptions]} value={filters.status} onChange={(value) => onFiltersChange({ ...filters, status: value ?? '' })} />
        <Select label={t('users.role')} data={[{ value: '', label: t('users.allRoles') }, ...roleOptions]} value={filters.role} onChange={(value) => onFiltersChange({ ...filters, role: value ?? '' })} />
      </SimpleGrid>

      <form className="form-surface" onSubmit={(event) => void onCreateUser(event)}>
        <Text fw={800} c="slate.9" mb="sm">{t('users.createTitle')}</Text>
        <SimpleGrid cols={{ base: 1, md: 3 }} spacing="sm">
          <TextInput label={t('auth.username')} required value={createForm.username} onChange={(event) => onCreateFormChange({ ...createForm, username: event.currentTarget.value })} />
          <TextInput label={t('fields.displayName')} required value={createForm.display_name} onChange={(event) => onCreateFormChange({ ...createForm, display_name: event.currentTarget.value })} />
          <TextInput label={t('fields.email')} value={createForm.email} onChange={(event) => onCreateFormChange({ ...createForm, email: event.currentTarget.value })} />
          <Select label={t('fields.status')} data={statusOptions} value={createForm.status} allowDeselect={false} onChange={(value) => onCreateFormChange({ ...createForm, status: value || 'active' })} />
          <PasswordInput label={t('auth.password')} required value={createForm.password} onChange={(event) => onCreateFormChange({ ...createForm, password: event.currentTarget.value })} />
          <MultiSelect label={t('users.roles')} data={roleOptions} value={createForm.roles} onChange={(roles) => onCreateFormChange({ ...createForm, roles })} />
        </SimpleGrid>
        <Button type="submit" mt="sm">{t('actions.createUser')}</Button>
      </form>

      <EntityList
        emptyText={t('empty.users')}
        items={users.map((user) => ({ id: user.id, title: user.display_name || user.username, meta: `@${user.username} · ${user.status} · ${(user.roles || []).join(', ')}` }))}
        selectedId={selectedUserId}
        onSelect={onSelectUser}
      />
      {pageCount > 1 ? <Pagination total={pageCount} value={usersMeta.page} onChange={onPageChange} /> : null}

      {selectedUser ? (
        <DetailCard
          title={selectedUser.display_name || selectedUser.username}
          subtitle={`@${selectedUser.username}`}
          status={selectedUser.status}
          details={[[t('fields.email'), selectedUser.email], [t('users.roles'), selectedUser.roles?.join(', ')], [t('settings.savedLanguage'), selectedUser.preferences?.language], [t('settings.displayDensity'), selectedUser.preferences?.display_density]]}
        >
          <form onSubmit={(event) => void onUpdateUser(event)}>
            <Stack gap="sm">
              <SimpleGrid cols={{ base: 1, md: 2 }} spacing="sm">
                <TextInput label={t('fields.displayName')} value={editForm.display_name} onChange={(event) => onEditFormChange({ ...editForm, display_name: event.currentTarget.value })} />
                <TextInput label={t('fields.email')} value={editForm.email} onChange={(event) => onEditFormChange({ ...editForm, email: event.currentTarget.value })} />
                <Select label={t('fields.status')} data={statusOptions} value={editForm.status} allowDeselect={false} onChange={(value) => onEditFormChange({ ...editForm, status: value || 'active' })} />
                <PasswordInput label={t('users.newPassword')} value={editForm.password} onChange={(event) => onEditFormChange({ ...editForm, password: event.currentTarget.value })} />
                <MultiSelect label={t('users.roles')} data={roleOptions} value={editForm.roles} onChange={(roles) => onEditFormChange({ ...editForm, roles })} />
              </SimpleGrid>
              <Group gap="xs">
                <Button type="submit">{t('actions.saveUser')}</Button>
                <Button variant="light" color="teal" onClick={() => void onActivation('activate')}>{t('actions.activate')}</Button>
                <Button variant="light" color="red" onClick={() => void onActivation('deactivate')}>{t('actions.deactivate')}</Button>
              </Group>
            </Stack>
          </form>
        </DetailCard>
      ) : null}
    </WorkspacePanel>
  );
}
