import type { ReactNode } from 'react';
import { Stack, Text } from '@mantine/core';
import { EmptyState } from './EmptyState';

type RecordStackProps = {
  emptyText: string;
  isEmpty: boolean;
  footer?: string;
  children: ReactNode;
};

export function RecordStack({ emptyText, isEmpty, footer, children }: RecordStackProps) {
  return (
    <Stack gap="sm">
      {isEmpty ? <EmptyState text={emptyText} /> : children}
      {footer && (
        <Text size="sm" c="slate.6" fw={700}>
          {footer}
        </Text>
      )}
    </Stack>
  );
}
