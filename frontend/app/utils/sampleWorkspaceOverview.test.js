import test from 'node:test'
import assert from 'node:assert/strict'

import { buildSampleListItemPreview, buildSampleHistoryPreview } from './sampleWorkspaceOverview.js'

test('sample list preview prioritizes failed automation as strongest signal', () => {
  const preview = buildSampleListItemPreview({
    sample: {
      status: 'registered',
      sample_type: 'benthic'
    },
    results: [],
    exceptions: [],
    analysisJobs: [
      { status: 'failed', job_type: 'object_detection' }
    ]
  })

  assert.equal(preview.tone, 'warning')
  assert.match(preview.summary, /自动检测失败/)
  assert.match(preview.nextAction, /重新检测/)
})

test('sample list preview prioritizes open exceptions over generic status', () => {
  const preview = buildSampleListItemPreview({
    sample: {
      status: 'testing',
      sample_type: 'water'
    },
    results: [{ id: 1 }],
    exceptions: [{ status: 'open' }],
    analysisJobs: []
  })

  assert.equal(preview.tone, 'warning')
  assert.match(preview.summary, /存在未解决异常/)
  assert.match(preview.nextAction, /记录异常|处理异常/)
})

test('sample list preview shows ready-for-result state when main work is pending result entry', () => {
  const preview = buildSampleListItemPreview({
    sample: {
      status: 'received',
      sample_type: 'water'
    },
    results: [],
    exceptions: [],
    analysisJobs: []
  })

  assert.equal(preview.tone, 'primary')
  assert.match(preview.summary, /等待录入结果/)
  assert.match(preview.nextAction, /新增结果/)
})

test('history preview shows only latest three simplified records', () => {
  const preview = buildSampleHistoryPreview([
    { id: 5, status: 'succeeded', result_summary: 'A', error_message: null, finished_at: '2026-03-16 10:05:00', queued_at: '2026-03-16 10:00:00' },
    { id: 4, status: 'failed', result_summary: null, error_message: 'boom', finished_at: '2026-03-16 09:30:00', queued_at: '2026-03-16 09:20:00' },
    { id: 3, status: 'running', result_summary: null, error_message: null, finished_at: null, queued_at: '2026-03-16 08:00:00' },
    { id: 2, status: 'succeeded', result_summary: 'older', error_message: null, finished_at: '2026-03-16 07:10:00', queued_at: '2026-03-16 07:00:00' }
  ])

  assert.equal(preview.totalCount, 4)
  assert.equal(preview.items.length, 3)
  assert.equal(preview.allItems.length, 4)
  assert.equal(preview.items[0].summary, 'A')
  assert.equal(preview.items[1].summary, 'boom')
  assert.equal(preview.items[2].statusLabel, '进行中')
})
