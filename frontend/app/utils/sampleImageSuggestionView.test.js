import test from 'node:test'
import assert from 'node:assert/strict'

import { buildSampleImageSuggestionView } from './sampleImageSuggestionView.js'

test('prompts upload when sample has no main image', () => {
  const view = buildSampleImageSuggestionView({
    sample: { main_image: null },
    suggestion: null
  })

  assert.equal(view.state, 'missing-main-image')
  assert.match(view.title, /先上传主图/)
  assert.equal(view.primaryAction, 'upload')
})

test('shows current findings as ready suggestion', () => {
  const view = buildSampleImageSuggestionView({
    sample: { main_image: { file_name: 'main.jpg', version: 1 } },
    suggestion: {
      state: 'current',
      summary: '检测到 scallop x2, starfish x1',
      suggestion: {
        has_findings: true,
        counts: { scallop: 2, starfish: 1 },
        confidence_summary: { top_score: 0.91 }
      }
    }
  })

  assert.equal(view.state, 'ready')
  assert.equal(view.primaryAction, 'rerun')
  assert.match(view.summary, /scallop x2/)
  assert.match(view.meta, /0.91/)
})

test('keeps previous summary visible while refreshing', () => {
  const view = buildSampleImageSuggestionView({
    sample: { main_image: { file_name: 'main.jpg', version: 1 } },
    suggestion: {
      state: 'refreshing',
      summary: '检测到 scallop x1',
      suggestion: {
        has_findings: true,
        counts: { scallop: 1 }
      }
    }
  })

  assert.equal(view.state, 'refreshing')
  assert.equal(view.primaryAction, 'busy')
  assert.match(view.description, /保留上一次建议/)
  assert.match(view.summary, /scallop x1/)
})

test('shows stale suggestion after main image changes', () => {
  const view = buildSampleImageSuggestionView({
    sample: { main_image: { file_name: 'main-2.jpg', version: 2 } },
    suggestion: {
      state: 'stale',
      summary: '检测到 scallop x1',
      suggestion: {
        has_findings: true,
        counts: { scallop: 1 }
      }
    }
  })

  assert.equal(view.state, 'stale')
  assert.equal(view.primaryAction, 'rerun')
  assert.match(view.description, /主图已更新/)
})

test('treats empty successful suggestion as no-findings', () => {
  const view = buildSampleImageSuggestionView({
    sample: { main_image: { file_name: 'main.jpg', version: 1 } },
    suggestion: {
      state: 'current',
      summary: '未检测到明确目标',
      suggestion: {
        has_findings: false,
        counts: {},
        confidence_summary: { top_score: 0.18 }
      }
    }
  })

  assert.equal(view.state, 'empty')
  assert.equal(view.primaryAction, 'rerun')
  assert.match(view.summary, /未检测到明确目标/)
})
