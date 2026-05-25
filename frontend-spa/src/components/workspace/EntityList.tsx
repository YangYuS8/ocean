import { ScrollArea, Stack } from '@mantine/core';
import { EmptyState } from './EmptyState';

type EntityListItem = {
  id: number;
  title: string;
  meta: string;
};

type EntityListProps = {
  emptyText: string;
  items: EntityListItem[];
  selectedId: number | null;
  onSelect: (id: number) => void;
};

export function EntityList({ emptyText, items, selectedId, onSelect }: EntityListProps) {
  if (items.length === 0) return <EmptyState text={emptyText} />;

  return (
    <ScrollArea.Autosize mah={260} type="auto">
      <Stack gap="xs">
        {items.map((item) => (
          <button
            className={item.id === selectedId ? 'entity-row active' : 'entity-row'}
            key={item.id}
            onClick={() => onSelect(item.id)}
            type="button"
          >
            <span>{item.title}</span>
            <small>{item.meta}</small>
          </button>
        ))}
      </Stack>
    </ScrollArea.Autosize>
  );
}
