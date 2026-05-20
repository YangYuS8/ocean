import { themes as prismThemes } from 'prism-react-renderer';
import type { Config } from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

const repoName = process.env.GITHUB_REPOSITORY?.split('/')[1] ?? 'ocean';
const baseUrl = process.env.DOCUSAURUS_BASE_URL ?? `/${repoName}/`;

const config: Config = {
  title: 'Ocean Documentation',
  tagline: 'Architecture, API, delivery, and decision records for the Ocean platform.',
  favicon: 'img/favicon.svg',

  future: {
    v4: true,
  },

  url: process.env.DOCUSAURUS_URL ?? 'https://example.com',
  baseUrl,

  organizationName: 'yangyus8',
  projectName: repoName,

  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'warn',
    },
  },

  i18n: {
    defaultLocale: 'en',
    locales: ['en', 'zh-Hans'],
    localeConfigs: {
      en: {
        label: 'English',
        htmlLang: 'en',
      },
      'zh-Hans': {
        label: '简体中文',
        htmlLang: 'zh-CN',
      },
    },
  },

  presets: [
    [
      'classic',
      {
        docs: {
          sidebarPath: './sidebars.ts',
          routeBasePath: 'docs',
          editUrl: undefined,
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
      } satisfies Preset.Options,
    ],
  ],

  themeConfig: {
    navbar: {
      title: 'Ocean Docs',
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'docsSidebar',
          position: 'left',
          label: 'Docs',
        },
        {
          to: '/docs/intro',
          label: 'Overview',
          position: 'left',
        },
        {
          type: 'localeDropdown',
          position: 'right',
        },
        {
          href: 'https://github.com/yangyus8/ocean',
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Docs',
          items: [
            { label: 'Documentation Overview', to: '/docs/intro' },
            { label: 'Technology Stack Reassessment', to: '/docs/architecture/tech-stack' },
          ],
        },
        {
          title: 'Delivery',
          items: [
            { label: 'Deployment and Operations', to: '/docs/operations/deployment' },
            { label: 'v1 Roadmap', to: '/docs/product/v1-roadmap' },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Ocean`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
    },
  } satisfies Preset.ThemeConfig,
};

export default config;
