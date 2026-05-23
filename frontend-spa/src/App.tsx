import { apiBase, getPreviewRequest } from './api/client';

const previewEndpoints = [
  getPreviewRequest('/dashboard/summary'),
  getPreviewRequest('/inspection-tasks'),
  getPreviewRequest('/samples'),
  getPreviewRequest('/analysis-jobs'),
];

function App() {
  return (
    <main className="app-shell">
      <section className="hero-card">
        <span className="eyebrow">Ocean v1.1.0</span>
        <h1>Frontend Transition Foundation</h1>
        <p className="lead">
          This React + TypeScript + Vite workspace is the long-term SPA baseline for the
          business frontend. The current Nuxt flow remains intact while this target line
          establishes the API boundary and static deployment shape.
        </p>
      </section>

      <section className="grid">
        <article className="panel">
          <h2>Transition status</h2>
          <ul>
            <li>
              <strong>Current runtime:</strong> <code>frontend/</code> Nuxt/Vue remains the
              active flow.
            </li>
            <li>
              <strong>Target mainline:</strong> <code>frontend-spa/</code> React/Vite SPA.
            </li>
            <li>
              <strong>Backend contract:</strong> Laravel keeps business rules and exposes
              <code> /api </code>.
            </li>
            <li>
              <strong>Deployment target:</strong> static assets served by Nginx with API reverse
              proxying.
            </li>
          </ul>
        </article>

        <article className="panel">
          <h2>API boundary baseline</h2>
          <p>
            <span className="label">Configured base</span>
            <code>{apiBase}</code>
          </p>
          <p>
            All API calls should be routed through <code>src/api/client.ts</code> so the SPA can
            switch environments without touching page-level components.
          </p>
          <div className="endpoint-list">
            {previewEndpoints.map((endpoint) => (
              <div className="endpoint-row" key={`${endpoint.method}-${endpoint.url}`}>
                <span className="badge">{endpoint.method}</span>
                <code>{endpoint.url}</code>
              </div>
            ))}
          </div>
        </article>

        <article className="panel">
          <h2>v1.2 expectation</h2>
          <p>
            v1.1 proves the runtime, API client boundary, and static hosting path. v1.2 is
            expected to move the core workspace flows for inspection tasks, samples, results,
            exceptions, and summary views onto this SPA line.
          </p>
        </article>
      </section>
    </main>
  );
}

export default App;
