import { Paper, Text } from '@mantine/core';

type EmptyStateProps = {
  text: string;
};

export function EmptyState({ text }: EmptyStateProps) {
  return (
    <Paper className="empty-state" radius="md" p="md">
      <Text c="slate.6" size="sm">
        {text}
      </Text>
    </Paper>
  );
}
