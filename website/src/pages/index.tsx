import clsx from 'clsx';
import Link from '@docusaurus/Link';
import Layout from '@theme/Layout';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import useBaseUrl from '@docusaurus/useBaseUrl';

function HomeContent() {
  const { i18n } = useDocusaurusContext();
  const isZhHans = i18n.currentLocale === 'zh-Hans';

  const title = isZhHans ? 'Ocean 项目文档中心' : 'Ocean Documentation Hub';
  const subtitle = isZhHans
    ? '面向架构、接口、交付和历史决策的双语 Docusaurus 文档站。'
    : 'A bilingual Docusaurus site for architecture, API, delivery, and historical decisions.';

  const cards = isZhHans
    ? [
        ['架构主线', '明确 Laravel API + React/TypeScript SPA + Python Worker + MariaDB + Redis + Nginx + Docker Compose。'],
        ['产品范围', '整理角色、MVP 需求、状态流转与 v1 路线图。'],
        ['运维交付', '沉淀迁移运行、Redis/Python 边界与部署维护要点。'],
      ]
    : [
        ['Architecture', 'Laravel-first backend, SPA frontend baseline, Python worker, and deployment topology.'],
        ['Product Scope', 'Roles, MVP requirements, state transitions, and versioned v1 roadmap.'],
        ['Operations', 'Migration, runtime, Redis/Python boundary, and compose deployment guidance.'],
      ];

  return (
    <Layout title={title} description={subtitle}>
      <header className={clsx('hero hero--ocean')}>
        <div className="container">
          <h1 className="hero__title">{title}</h1>
          <p className="hero__subtitle">{subtitle}</p>
          <div>
            <Link className="button button--secondary button--lg" to={useBaseUrl('/docs/intro')}>
              {isZhHans ? '进入文档' : 'Read the docs'}
            </Link>
          </div>
        </div>
      </header>
      <main className="container margin-vert--xl">
        <div className="ocean-card-grid">
          {cards.map(([heading, text]) => (
            <section key={heading} className="ocean-card">
              <h3>{heading}</h3>
              <p>{text}</p>
            </section>
          ))}
        </div>
      </main>
    </Layout>
  );
}

export default HomeContent;
