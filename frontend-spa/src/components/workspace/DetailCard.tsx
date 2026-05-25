import type { ReactNode } from 'react';
import { Box, Divider, Group, Paper, SimpleGrid, Text } from '@mantine/core';
import { StatusBadge } from './StatusBadge';

type DetailCardProps = {
  title: string;
  subtitle: string;
  status: string;
  details: Array<[string, string | null | undefined]>;
  children: ReactNode;
};

export function DetailCard({ title, subtitle, status, details, children }: DetailCardProps) {
  return (
    <Paper className="detail-card" radius="md" p="md">
      <Group justify="space-between" align="flex-start">
        <Box>
          <Text fw={750} c="slate.9">{title}</Text>
          <Text c="slate.6" size="sm">
            {subtitle}
          </Text>
        </Box>
        <StatusBadge value={status} />
      </Group>
      <SimpleGrid cols={{ base: 2, sm: 4 }} spacing="sm" mt="md">
        {details.map(([label, value]) => (
          <Box key={label}>
            <Text size="xs" c="slate.5" tt="uppercase" fw={800} lts="0.045em">
              {label}
            </Text>
            <Text size="sm" fw={650} c="slate.8">
              {value || '—'}
            </Text>
          </Box>
        ))}
      </SimpleGrid>
      <Divider my="md" />
      {children}
    </Paper>
  );
}
