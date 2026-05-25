import { Badge } from '@mantine/core';

type StatusBadgeProps = {
  value: string;
};

export function StatusBadge({ value }: StatusBadgeProps) {
  const color = value.includes('fail') || value === 'critical'
    ? 'red'
    : value.includes('open') || value.includes('queued') || value.includes('assigned')
      ? 'orange'
    : value.includes('running') || value.includes('progress')
        ? 'cyan'
    : value.includes('success') || value.includes('completed') || value.includes('resolved') || value.includes('submitted')
          ? 'ocean'
          : 'gray';

  return (
    <Badge color={color} variant="light" radius="sm" tt="capitalize">
      {value.replace(/_/g, ' ')}
    </Badge>
  );
}
