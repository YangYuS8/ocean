import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const readPage = (relativePath) => readFileSync(resolve(process.cwd(), relativePath), 'utf8')

test('sample detail icon-only close buttons have aria labels', () => {
  const source = readPage('app/pages/samples/[id].vue')

  const closeButtonMatches = source.match(/variant="ghost" icon="i-lucide-x"/g) || []
  const ariaLabelMatches = source.match(/aria-label="关闭[^"]*"/g) || []

  assert.equal(closeButtonMatches.length, 2)
  assert.equal(ariaLabelMatches.length, 2)
})

test('sample pages use focus-visible ring styles instead of border-only outline-none fields', () => {
  const pages = [
    readPage('app/pages/samples/index.vue'),
    readPage('app/pages/samples/[id].vue')
  ]

  for (const source of pages) {
    assert.equal(source.includes('outline-none transition focus:border-primary'), false)
    assert.match(source, /focus-visible:ring-2/)
    assert.match(source, /focus-visible:ring-primary\/50/)
  }
})

test('sample detail no longer renders the legacy workflow linkage panel', () => {
  const source = readPage('app/pages/samples/[id].vue')

  assert.equal(source.includes('关联工作流'), false)
})
