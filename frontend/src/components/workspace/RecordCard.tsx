import type { ReactNode } from 'react';
import { Box, Group, Paper, Text } from '@mantine/core';
import { StatusBadge } from './StatusBadge';

type RecordCardProps = {
  title: string;
  description: string;
  status: string;
  error?: string | null;
  children: ReactNode;
};

export function RecordCard({ title, description, status, error, children }: RecordCardProps) {
  return (
    <Paper className="record-card" radius="md" p="md">
      <Group justify="space-between" align="flex-start" gap="md">
        <Box>
          <Text fw={750} c="slate.9">{title}</Text>
          <Text size="sm" c="slate.6">
            {description}
          </Text>
          {error && (
            <Text size="sm" c="red.7" mt={4}>
              {error}
            </Text>
          )}
        </Box>
        <Group gap="xs" justify="flex-end">
          <StatusBadge value={status} />
          {children}
        </Group>
      </Group>
    </Paper>
  );
}
