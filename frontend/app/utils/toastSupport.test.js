import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

test('app root wraps pages in UApp for toast support', () => {
  const source = readFileSync(resolve(process.cwd(), 'app/app.vue'), 'utf8')

  assert.match(source, /<UApp>/)
  assert.match(source, /<NuxtLayout>/)
})
