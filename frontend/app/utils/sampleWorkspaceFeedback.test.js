import test from 'node:test'
import assert from 'node:assert/strict'

import { buildImageSuggestionToast, formatDateTimeInShanghai, shouldPollImageSuggestion } from './sampleWorkspaceFeedback.js'

test('formats date time in Asia/Shanghai timezone', () => {
  const formatted = formatDateTimeInShanghai('2026-03-16T12:30:00Z')

  assert.match(formatted, /2026/)
  assert.match(formatted, /20:30|20:30:00|20:30/)
})

test('formats SQL datetime strings as Asia/Shanghai local time without hydration drift', () => {
  const formatted = formatDateTimeInShanghai('2026-03-16 12:17:00')

  assert.match(formatted, /20:17/)
})

test('builds success toast when detection becomes current', () => {
  const toast = buildImageSuggestionToast({
    previousState: 'running',
    nextState: 'current',
    summary: 'echinus x1, scallop x1'
  })

  assert.equal(toast?.title, '自动检测已完成')
  assert.match(toast?.description || '', /echinus x1, scallop x1/)
  assert.equal(toast?.color, 'success')
})

test('builds empty-result toast when detection completes without findings', () => {
  const toast = buildImageSuggestionToast({
    previousState: 'refreshing',
    nextState: 'empty',
    summary: '未检测到明确目标'
  })

  assert.equal(toast?.title, '自动检测已完成')
  assert.match(toast?.description || '', /未检测到明确目标/)
})

test('builds failure toast when detection fails after running', () => {
  const toast = buildImageSuggestionToast({
    previousState: 'running',
    nextState: 'failed',
    summary: '自动检测未完成，请稍后重新发起。'
  })

  assert.equal(toast?.title, '自动检测失败')
  assert.equal(toast?.color, 'error')
})

test('does not build toast for unrelated state changes', () => {
  const toast = buildImageSuggestionToast({
    previousState: 'idle',
    nextState: 'current',
    summary: 'echinus x1'
  })

  assert.equal(toast, null)
})

test('polls while image suggestion is running or refreshing', () => {
  assert.equal(shouldPollImageSuggestion('running'), true)
  assert.equal(shouldPollImageSuggestion('refreshing'), true)
  assert.equal(shouldPollImageSuggestion('current'), false)
})
