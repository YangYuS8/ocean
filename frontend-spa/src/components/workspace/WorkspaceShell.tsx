import type { ReactNode } from 'react';
import { Badge, Box, Button, Card, Container, Divider, Grid, Group, Paper, SimpleGrid, Stack, Tabs, Text, TextInput, Title } from '@mantine/core';
import type { SupportedLanguage } from '../../i18n';
import type { WorkspaceTab } from '../../features/workspace/types';
import { LanguageSwitcher } from './LanguageSwitcher';
import { MetricCard } from './MetricCard';

type WorkspaceShellProps = {
  title: string;
  subtitle: string;
  versionLabel: string;
  apiBaseLabel: string;
  apiBaseValue: string;
  settingsTitle: string;
  operatorIdLabel: string;
  analystIdLabel: string;
  refreshLabel: string;
  languageLabel: string;
  chineseLabel: string;
  englishLabel: string;
  language: SupportedLanguage;
  operatorId: number;
  analystId: number;
  onOperatorIdChange: (value: number) => void;
  onAnalystIdChange: (value: number) => void;
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
  operatorIdLabel,
  analystIdLabel,
  refreshLabel,
  languageLabel,
  chineseLabel,
  englishLabel,
  language,
  operatorId,
  analystId,
  onOperatorIdChange,
  onAnalystIdChange,
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
  summaryMetrics,
  children,
}: WorkspaceShellProps) {
  return (
    <Box className="app-shell">
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

                <LanguageSwitcher
                  label={languageLabel}
                  value={language}
                  chineseLabel={chineseLabel}
                  englishLabel={englishLabel}
                  onChange={onLanguageChange}
                />
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
                        <Badge variant="outline" color="gray" radius="sm">
                          Live
                        </Badge>
                      </Group>
                      <Divider />
                      <SimpleGrid cols={{ base: 1, xs: 2 }} spacing="sm">
                        <TextInput
                          label={operatorIdLabel}
                          min="1"
                          type="number"
                          value={operatorId}
                          onChange={(event) => onOperatorIdChange(Number(event.currentTarget.value))}
                        />
                        <TextInput
                          label={analystIdLabel}
                          min="1"
                          type="number"
                          value={analystId}
                          onChange={(event) => onAnalystIdChange(Number(event.currentTarget.value))}
                        />
                      </SimpleGrid>
                      <Button variant="light" color="ocean" onClick={onRefresh}>
                        {refreshLabel}
                      </Button>
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
            </Tabs.List>

            <Box className="workspace-tab-panel">{children}</Box>
          </Tabs>
        </Stack>
      </Container>
    </Box>
  );
}
