import { Card, Text } from '@mantine/core';

type MetricCardProps = {
  label: string;
  value: number;
};

export function MetricCard({ label, value }: MetricCardProps) {
  return (
    <Card className="metric-card" radius="md" p="md">
      <Text size="xs" fw={800} c="slate.6" tt="uppercase" lts="0.045em">
        {label}
      </Text>
      <Text className="metric-value">{value}</Text>
    </Card>
  );
}
