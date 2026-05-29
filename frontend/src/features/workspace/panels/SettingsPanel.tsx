import type { FormEvent } from 'react';
import { Badge, Button, Card, Group, Select, SimpleGrid, Stack, Text, TextInput } from '@mantine/core';
import type { TFunction } from 'i18next';
import { apiBase, type ProfileRecord, type SettingsRecord } from '../../../api/client';
import { DetailCard } from '../../../components/workspace/DetailCard';
import { WorkspacePanel } from '../../../components/workspace/WorkspacePanel';
import type { ProfileForm, SettingsForm, WorkspaceTab } from '../types';
import { workspaceTabs } from '../types';

type SettingsPanelProps = {
  t: TFunction;
  profile: ProfileRecord | null;
  settings: SettingsRecord | null;
  profileForm: ProfileForm;
  settingsForm: SettingsForm;
  isAuthenticated: boolean;
  isAdmin: boolean;
  onProfileFormChange: (value: ProfileForm) => void;
  onSettingsFormChange: (value: SettingsForm) => void;
  onSaveProfile: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onSaveSettings: (event: FormEvent<HTMLFormElement>) => Promise<void>;
  onLanguageChange: (value: 'zh-Hans' | 'en') => void;
};

export function SettingsPanel({
  t,
  profile,
  settings,
  profileForm,
  settingsForm,
  isAuthenticated,
  isAdmin,
  onProfileFormChange,
  onSettingsFormChange,
  onSaveProfile,
  onSaveSettings,
  onLanguageChange,
}: SettingsPanelProps) {
  const tabOptions = workspaceTabs.map((tab) => ({ value: tab, label: t(`tabs.${tab}`) }));

  return (
    <WorkspacePanel title={t('panels.settings.title')} subtitle={t('panels.settings.subtitle')}>
      <SimpleGrid cols={{ base: 1, md: 2 }} spacing="md">
        <form className="form-surface" onSubmit={(event) => void onSaveProfile(event)}>
          <Stack gap="sm">
            <Group justify="space-between">
              <Text fw={800} c="slate.9">{t('settings.profileTitle')}</Text>
              <Badge color={isAuthenticated ? 'teal' : 'gray'} variant="light">
                {isAuthenticated ? t('settings.signedIn') : t('settings.notSignedIn')}
              </Badge>
            </Group>
            <TextInput label={t('auth.username')} value={profile?.username ?? ''} disabled />
            <TextInput
              label={t('fields.displayName')}
              value={profileForm.display_name}
              onChange={(event) => onProfileFormChange({ ...profileForm, display_name: event.currentTarget.value })}
            />
            <TextInput
              label={t('fields.email')}
              value={profileForm.email}
              onChange={(event) => onProfileFormChange({ ...profileForm, email: event.currentTarget.value })}
            />
            <Button type="submit" disabled={!isAuthenticated}>{t('actions.saveProfile')}</Button>
          </Stack>
        </form>

        <form className="form-surface" onSubmit={(event) => void onSaveSettings(event)}>
          <Stack gap="sm">
            <Text fw={800} c="slate.9">{t('settings.preferencesTitle')}</Text>
            <Select
              label={t('app.language')}
              data={[{ value: 'zh-Hans', label: t('app.chinese') }, { value: 'en', label: t('app.english') }]}
              value={settingsForm.language}
              allowDeselect={false}
              onChange={(value) => {
                const language = (value === 'en' ? 'en' : 'zh-Hans') as 'zh-Hans' | 'en';
                onSettingsFormChange({ ...settingsForm, language });
                onLanguageChange(language);
              }}
            />
            <Select
              label={t('settings.displayDensity')}
              data={[{ value: 'comfortable', label: t('settings.comfortable') }, { value: 'compact', label: t('settings.compact') }]}
              value={settingsForm.display_density}
              allowDeselect={false}
              onChange={(value) => onSettingsFormChange({ ...settingsForm, display_density: value === 'compact' ? 'compact' : 'comfortable' })}
            />
            <Select
              label={t('settings.defaultTab')}
              data={tabOptions}
              value={settingsForm.default_workspace_tab}
              allowDeselect={false}
              onChange={(value) => onSettingsFormChange({ ...settingsForm, default_workspace_tab: (value as WorkspaceTab) || 'overview' })}
            />
            <Button type="submit" disabled={!isAuthenticated}>{t('actions.saveSettings')}</Button>
          </Stack>
        </form>
      </SimpleGrid>

      <DetailCard
        title={t('settings.summaryTitle')}
        subtitle={t('settings.summarySubtitle')}
        status={isAdmin ? 'admin' : 'governed'}
        details={[
          [t('app.apiBase'), apiBase],
          [t('settings.authMode'), isAuthenticated ? 'Bearer token' : 'dev actor fallback'],
          [t('settings.roles'), profile?.roles?.join(', ')],
          [t('settings.savedLanguage'), settings?.language || settingsForm.language],
        ]}
      >
        <SimpleGrid cols={{ base: 1, md: 3 }} spacing="sm">
          <Card withBorder radius="md" p="sm"><Text fw={700}>{t('settings.apiSummary')}</Text><Text size="sm" c="dimmed">{t('settings.apiSummaryCopy')}</Text></Card>
          <Card withBorder radius="md" p="sm"><Text fw={700}>{t('settings.authSummary')}</Text><Text size="sm" c="dimmed">{t('app.authTokenStrategy')}</Text></Card>
          <Card withBorder radius="md" p="sm"><Text fw={700}>{t('settings.governanceSummary')}</Text><Text size="sm" c="dimmed">{t('app.rbacHeaderStrategy')}</Text></Card>
        </SimpleGrid>
      </DetailCard>
    </WorkspacePanel>
  );
}
