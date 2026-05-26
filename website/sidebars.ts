import type { SidebarsConfig } from '@docusaurus/plugin-content-docs';

const sidebars: SidebarsConfig = {
  docsSidebar: [
    'intro',
    'contributing',
    {
      type: 'category',
      label: 'Architecture',
      items: [
        'architecture/tech-stack',
        'architecture/system-architecture',
        'architecture/frontend-transition',
      ],
    },
    {
      type: 'category',
      label: 'Product and Scope',
      items: [
        'product/roles-and-requirements',
        'product/v1-roadmap',
      ],
    },
    {
      type: 'category',
      label: 'API and Data',
      items: [
        'api/p0-api',
        'data/data-model-and-states',
      ],
    },
    {
      type: 'category',
      label: 'Deployment and Operations',
      items: ['operations/deployment'],
    },
    {
      type: 'category',
      label: 'Decision History',
      items: ['history/openspec-decisions'],
    },
  ],
};

export default sidebars;
