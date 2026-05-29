import type { ReactNode } from 'react';
import { Box, Card, Group, Stack, Text, Title } from '@mantine/core';

type WorkspacePanelProps = {
  title: string;
  subtitle: string;
  children: ReactNode;
};

export function WorkspacePanel({ title, subtitle, children }: WorkspacePanelProps) {
  return (
    <Card className="workspace-panel" radius="lg" p={{ base: 'md', md: 'lg' }}>
      <Group justify="space-between" align="flex-start" mb="lg">
        <Box>
          <Title order={2} c="slate.9">{title}</Title>
          <Text c="slate.6" size="sm" mt={4} maw={760}>
            {subtitle}
          </Text>
        </Box>
      </Group>
      <Stack gap="md">{children}</Stack>
    </Card>
  );
}
