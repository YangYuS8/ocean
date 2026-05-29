import { Group, SegmentedControl, Text } from '@mantine/core';
import type { SupportedLanguage } from '../../i18n';

type LanguageSwitcherProps = {
  label: string;
  value: SupportedLanguage;
  chineseLabel: string;
  englishLabel: string;
  onChange: (value: SupportedLanguage) => void;
};

export function LanguageSwitcher({
  label,
  value,
  chineseLabel,
  englishLabel,
  onChange,
}: LanguageSwitcherProps) {
  return (
    <Group className="language-switcher" gap="sm" justify="flex-end" wrap="wrap">
      <Text fw={700} size="sm" c="slate.7">
        {label}
      </Text>
      <SegmentedControl
        aria-label={label}
        size="xs"
        value={value}
        data={[
          { label: chineseLabel, value: 'zh-Hans' },
          { label: englishLabel, value: 'en' },
        ]}
        onChange={(nextValue) => onChange(nextValue as SupportedLanguage)}
      />
    </Group>
  );
}
