import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import {
  Badge,
  Box,
  Button,
  Card,
  Container,
  Divider,
  Grid,
  Group,
  Modal,
  Paper,
  PasswordInput,
  Select,
  SimpleGrid,
  Stack,
  Tabs,
  Text,
  TextInput,
  Title,
} from '@mantine/core';
import type { SupportedLanguage } from '../../i18n';
import type { WorkspaceTab } from '../../features/workspace/types';
import { LanguageSwitcher } from './LanguageSwitcher';
import { MetricCard } from './MetricCard';

type ActorOption = {
  value: string;
  label: string;
};

type WorkspaceShellProps = {
  title: string;
  subtitle: string;
  versionLabel: string;
  apiBaseLabel: string;
  apiBaseValue: string;
  settingsTitle: string;
  currentActorLabel: string;
  governedIdentityLabel: string;
  governanceHint: string;
  actorRoleLabel: string;
  headerStrategyLabel: string;
  authTokenStrategyLabel: string;
  refreshLabel: string;
  languageLabel: string;
  chineseLabel: string;
  englishLabel: string;
  language: SupportedLanguage;
  isAuthenticated: boolean;
  currentActorName: string;
  currentActorUsername: string;
  currentActorRole: string;
  loginTitle: string;
  usernameLabel: string;
  passwordLabel: string;
  loginLabel: string;
  logoutLabel: string;
  signedInAsLabel: string;
  demoAccountsLabel: string;
  loginUsername: string;
  loginPassword: string;
  selectedActorId: number;
  selectedActorRole: string;
  actorOptions: ActorOption[];
  onLoginUsernameChange: (value: string) => void;
  onLoginPasswordChange: (value: string) => void;
  onLogin: (event: FormEvent<HTMLFormElement>) => void;
  onLogout: () => void;
  onActorChange: (value: number) => void;
  onRefresh: () => void;
  onLanguageChange: (value: SupportedLanguage) => void;
  activeTab: WorkspaceTab;
  onTabChange: (value: WorkspaceTab) => void;
  overviewLabel: string;
  tasksLabel: string;
  samplesLabel: string;
  resultsLabel: string;
  exceptionsLabel: string;
  analysisLabel: string;
  settingsLabel: string;
  usersLabel: string;
  summaryMetrics: Array<{ label: string; value: number }>;
  children: ReactNode;
};

export function WorkspaceShell({
  title,
  subtitle,
  versionLabel,
  apiBaseLabel,
  apiBaseValue,
  settingsTitle,
  currentActorLabel,
  governedIdentityLabel,
  governanceHint,
  actorRoleLabel,
  headerStrategyLabel,
  authTokenStrategyLabel,
  refreshLabel,
  languageLabel,
  chineseLabel,
  englishLabel,
  language,
  isAuthenticated,
  currentActorName,
  currentActorUsername,
  currentActorRole,
  loginTitle,
  usernameLabel,
  passwordLabel,
  loginLabel,
  logoutLabel,
  signedInAsLabel,
  demoAccountsLabel,
  loginUsername,
  loginPassword,
  selectedActorId,
  selectedActorRole,
  actorOptions,
  onLoginUsernameChange,
  onLoginPasswordChange,
  onLogin,
  onLogout,
  onActorChange,
  onRefresh,
  onLanguageChange,
  activeTab,
  onTabChange,
  overviewLabel,
  tasksLabel,
  samplesLabel,
  resultsLabel,
  exceptionsLabel,
  analysisLabel,
  settingsLabel,
  usersLabel,
  summaryMetrics,
  children,
}: WorkspaceShellProps) {
  const [loginOpened, setLoginOpened] = useState(false);

  useEffect(() => {
    if (isAuthenticated) {
      setLoginOpened(false);
    }
  }, [isAuthenticated]);

  return (
    <Box className="app-shell">
      <Modal
        opened={loginOpened}
        onClose={() => setLoginOpened(false)}
        title={loginTitle}
        centered
        radius="lg"
        size="md"
        overlayProps={{ backgroundOpacity: 0.55, blur: 5 }}
        classNames={{ content: 'login-modal', header: 'login-modal-header', title: 'login-modal-title' }}
      >
        <Box component="form" onSubmit={onLogin}>
          <Stack gap="md">
            <Text size="sm" c="dimmed">
              {demoAccountsLabel}
            </Text>
            <TextInput
              label={usernameLabel}
              value={loginUsername}
              onChange={(event) => onLoginUsernameChange(event.currentTarget.value)}
              autoComplete="username"
              data-autofocus
            />
            <PasswordInput
              label={passwordLabel}
              value={loginPassword}
              onChange={(event) => onLoginPasswordChange(event.currentTarget.value)}
              autoComplete="current-password"
            />
            <Button type="submit" color="ocean" size="md" fullWidth>
              {loginLabel}
            </Button>
          </Stack>
        </Box>
      </Modal>

      <Container size="xl" py={{ base: 'md', md: 'xl' }}>
        <Stack gap="md">
          <Paper className="shell-header" p={0} radius="lg">
            <Stack gap={0}>
              <Group
                className="header-rule"
                justify="space-between"
                align="flex-start"
                gap="lg"
                wrap="wrap"
                p={{ base: 'lg', md: 'xl' }}
              >
                <Box maw={760}>
                  <Badge variant="light" color="ocean" radius="sm" mb="sm">
                    {versionLabel}
                  </Badge>
                  <Title order={1} className="hero-title">
                    {title}
                  </Title>
                  <Text size="md" c="slate.7" mt="sm" maw={700}>
                    {subtitle}
                  </Text>
                  <Text size="sm" c="dimmed" mt="sm">
                    {apiBaseLabel}: <code>{apiBaseValue}</code>
                  </Text>
                </Box>

                <Group align="flex-start" gap="sm" wrap="wrap" justify="flex-end">
                  {!isAuthenticated ? (
                    <Button color="ocean" variant="filled" onClick={() => setLoginOpened(true)}>
                      {loginLabel}
                    </Button>
                  ) : null}
                  <LanguageSwitcher
                    label={languageLabel}
                    value={language}
                    chineseLabel={chineseLabel}
                    englishLabel={englishLabel}
                    onChange={onLanguageChange}
                  />
                </Group>
              </Group>

              <Grid gap="md" align="stretch" p={{ base: 'lg', md: 'xl' }}>
                <Grid.Col span={{ base: 12, lg: 8 }}>
                  <SimpleGrid cols={{ base: 1, xs: 2, md: 4 }} spacing="md">
                    {summaryMetrics.map((metric) => (
                      <MetricCard key={metric.label} label={metric.label} value={metric.value} />
                    ))}
                  </SimpleGrid>
                </Grid.Col>
                <Grid.Col span={{ base: 12, lg: 4 }}>
                  <Card className="control-card settings-card" radius="lg" p="md">
                    <Stack gap="sm">
                      <Group justify="space-between" gap="xs">
                        <Text fw={700} c="slate.9">
                          {settingsTitle}
                        </Text>
                        <Badge variant="outline" color="ocean" radius="sm">
                          {governedIdentityLabel}
                        </Badge>
                      </Group>
                      <Divider />
                      {isAuthenticated ? (
                        <Paper withBorder radius="md" p="sm" bg="white">
                          <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            {signedInAsLabel}
                          </Text>
                          <Text fw={700} c="slate.9" mt={4}>
                            {currentActorName}
                          </Text>
                          <Text size="sm" c="slate.7" mt={2}>
                            @{currentActorUsername}
                          </Text>
                        </Paper>
                      ) : (
                        <Paper withBorder radius="md" p="sm" bg="white" className="login-prompt-card">
                          <Group justify="space-between" gap="sm" align="center">
                            <Box>
                              <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                                {loginTitle}
                              </Text>
                              <Text size="sm" c="slate.7" mt={3}>
                                {authTokenStrategyLabel}
                              </Text>
                            </Box>
                            <Button color="ocean" variant="light" onClick={() => setLoginOpened(true)}>
                              {loginLabel}
                            </Button>
                          </Group>
                        </Paper>
                      )}
                      {!isAuthenticated && actorOptions.length > 0 ? (
                        <Select
                          label={currentActorLabel}
                          description={governanceHint}
                          data={actorOptions}
                          value={String(selectedActorId)}
                          onChange={(value) => value && onActorChange(Number(value))}
                          allowDeselect={false}
                        />
                      ) : null}
                      <SimpleGrid cols={{ base: 1, xs: 2 }} spacing="sm">
                        <Paper withBorder radius="md" p="sm" bg="white">
                          <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            {actorRoleLabel}
                          </Text>
                          <Text fw={600} c="slate.9" mt={4}>
                            {isAuthenticated ? currentActorRole : selectedActorRole}
                          </Text>
                        </Paper>
                        <Paper withBorder radius="md" p="sm" bg="white">
                          <Text size="xs" c="dimmed" tt="uppercase" fw={700}>
                            {headerStrategyLabel}
                          </Text>
                          <Text size="sm" c="slate.7" mt={4}>
                            {authTokenStrategyLabel}
                          </Text>
                        </Paper>
                      </SimpleGrid>
                      <Group grow>
                        <Button variant="light" color="ocean" onClick={onRefresh}>
                          {refreshLabel}
                        </Button>
                        {isAuthenticated ? (
                          <Button variant="outline" color="red" onClick={onLogout}>
                            {logoutLabel}
                          </Button>
                        ) : null}
                      </Group>
                    </Stack>
                  </Card>
                </Grid.Col>
              </Grid>
            </Stack>
          </Paper>

          <Tabs value={activeTab} onChange={(value) => value && onTabChange(value as WorkspaceTab)}>
            <Tabs.List className="workspace-tabs-list">
              <Tabs.Tab value="overview" fw={700}>
                {overviewLabel}
              </Tabs.Tab>
              <Tabs.Tab value="tasks" fw={700}>
                {tasksLabel}
              </Tabs.Tab>
              <Tabs.Tab value="samples" fw={700}>
                {samplesLabel}
              </Tabs.Tab>
              <Tabs.Tab value="results" fw={700}>
                {resultsLabel}
              </Tabs.Tab>
              <Tabs.Tab value="exceptions" fw={700}>
                {exceptionsLabel}
              </Tabs.Tab>
              <Tabs.Tab value="analysis" fw={700}>
                {analysisLabel}
              </Tabs.Tab>
              <Tabs.Tab value="settings" fw={700}>
                {settingsLabel}
              </Tabs.Tab>
              <Tabs.Tab value="users" fw={700}>
                {usersLabel}
              </Tabs.Tab>
            </Tabs.List>

            <Box className="workspace-tab-panel">{children}</Box>
          </Tabs>
        </Stack>
      </Container>
    </Box>
  );
}
