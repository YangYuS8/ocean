import test from 'node:test'
import assert from 'node:assert/strict'

import { buildOverviewMetrics } from './overviewMetrics.js'

test('shows neutral placeholders when summary is unavailable', () => {
  const metrics = buildOverviewMetrics(null)

  assert.deepEqual(metrics.map(metric => metric.value), ['--', '--', '--', '--'])
})

test('shows formatted values from summary payload', () => {
  const metrics = buildOverviewMetrics({
    pending_samples: 5,
    today_inspection_tasks: 1,
    open_exceptions: 0,
    queued_analysis_jobs: 2
  })

  assert.deepEqual(metrics.map(metric => metric.value), ['5', '1', '0', '2'])
})
