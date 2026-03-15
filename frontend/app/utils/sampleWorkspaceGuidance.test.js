import test from 'node:test'
import assert from 'node:assert/strict'

import { buildSampleWorkspaceGuidance } from './sampleWorkspaceGuidance.js'

test('recommends result entry and flags open exception risk', () => {
  const guidance = buildSampleWorkspaceGuidance({
    sample: { status: 'registered' },
    results: [],
    exceptions: [{ status: 'open', title: '标签模糊' }],
    analysisJobs: []
  })

  assert.equal(guidance.summaryTone, 'warning')
  assert.equal(guidance.nextStep.action, 'result')
  assert.match(guidance.nextStep.title, /录入结果/)
  assert.equal(guidance.risks[0].kind, 'exception')
})

test('recommends retry when sample has failed analysis job', () => {
  const guidance = buildSampleWorkspaceGuidance({
    sample: { status: 'testing' },
    results: [{ id: 1 }],
    exceptions: [],
    analysisJobs: [{ status: 'failed', job_type: 'quality_assessment' }]
  })

  assert.equal(guidance.summaryTone, 'warning')
  assert.equal(guidance.nextStep.action, 'retry-analysis')
  assert.match(guidance.nextStep.title, /重新发起分析/)
  assert.equal(guidance.risks[0].kind, 'analysis-failed')
})

test('shows in-progress analysis without treating it as failure', () => {
  const guidance = buildSampleWorkspaceGuidance({
    sample: { status: 'testing' },
    results: [{ id: 1 }],
    exceptions: [],
    analysisJobs: [{ status: 'running', job_type: 'anomaly_scan' }]
  })

  assert.equal(guidance.summaryTone, 'primary')
  assert.equal(guidance.nextStep.action, 'wait')
  assert.match(guidance.nextStep.title, /等待当前分析/)
  assert.equal(guidance.risks[0].kind, 'analysis-active')
})
