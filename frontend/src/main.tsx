import React from 'react';
import ReactDOM from 'react-dom/client';
import {
  Alert,
  Badge,
  Button,
  Card,
  createTheme,
  MantineProvider,
  Paper,
  SegmentedControl,
  Select,
  Tabs,
  Textarea,
  TextInput,
} from '@mantine/core';
import App from './App';
import '@mantine/core/styles.css';
import './i18n';
import './styles.css';

const theme = createTheme({
  colors: {
    ocean: ['#eefdfb', '#d7f8f4', '#aaeae2', '#79dcd0', '#51d0c2', '#35c7b8', '#1fb9aa', '#159487', '#13766d', '#125f58'],
  },
  primaryColor: 'ocean',
  primaryShade: { light: 7, dark: 6 },
  defaultRadius: 'md',
  radius: {
    xs: '4px',
    sm: '8px',
    md: '12px',
    lg: '16px',
    xl: '20px',
  },
  spacing: {
    xs: '0.5rem',
    sm: '0.75rem',
    md: '1rem',
    lg: '1.5rem',
    xl: '2rem',
  },
  fontSizes: {
    xs: '0.75rem',
    sm: '0.875rem',
    md: '0.9375rem',
    lg: '1rem',
    xl: '1.125rem',
  },
  fontFamily:
    '"IBM Plex Sans", "Aptos", "Segoe UI", ui-sans-serif, system-ui, -apple-system, sans-serif',
  fontFamilyMonospace: '"IBM Plex Mono", "SFMono-Regular", Consolas, ui-monospace, monospace',
  headings: {
    fontFamily:
      '"IBM Plex Sans", "Aptos", "Segoe UI", ui-sans-serif, system-ui, -apple-system, sans-serif',
    fontWeight: '700',
    sizes: {
      h1: { fontSize: '2.125rem', lineHeight: '1.12' },
      h2: { fontSize: '1.375rem', lineHeight: '1.25' },
      h3: { fontSize: '1.125rem', lineHeight: '1.3' },
    },
  },
  shadows: {
    xs: '0 1px 2px rgba(15, 23, 42, 0.04)',
    sm: '0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04)',
    md: '0 8px 24px rgba(15, 23, 42, 0.08)',
    lg: '0 16px 40px rgba(15, 23, 42, 0.10)',
    xl: '0 24px 60px rgba(15, 23, 42, 0.12)',
  },
  defaultGradient: { from: 'ocean.7', to: 'ocean.6', deg: 0 },
  cursorType: 'pointer',
  respectReducedMotion: true,
  components: {
    Alert: Alert.extend({ defaultProps: { radius: 'md' } }),
    Badge: Badge.extend({ defaultProps: { radius: 'sm', fw: 700 } }),
    Button: Button.extend({ defaultProps: { radius: 'md', fw: 700 } }),
    Card: Card.extend({ defaultProps: { radius: 'lg', withBorder: true } }),
    Paper: Paper.extend({ defaultProps: { radius: 'md', withBorder: true } }),
    Select: Select.extend({ defaultProps: { radius: 'md' } }),
    SegmentedControl: SegmentedControl.extend({ defaultProps: { radius: 'md' } }),
    Tabs: Tabs.extend({ defaultProps: { radius: 'md' } }),
    Textarea: Textarea.extend({ defaultProps: { radius: 'md' } }),
    TextInput: TextInput.extend({ defaultProps: { radius: 'md' } }),
  },
});

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <MantineProvider theme={theme} defaultColorScheme="light">
      <App />
    </MantineProvider>
  </React.StrictMode>,
);
