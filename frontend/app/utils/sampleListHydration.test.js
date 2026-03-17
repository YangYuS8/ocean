import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

test('sample list useFetch calls declare stable async data keys', () => {
  const source = readFileSync(resolve(process.cwd(), 'app/pages/samples/index.vue'), 'utf8')

  assert.match(source, /useFetch<PaginatedResponse<Sample>>\('\/api\/samples',[\s\S]*key:\s*\(\)\s*=>\s*`samples-list-/)
  assert.match(source, /useFetch<PaginatedResponse<SampleException>>\('\/api\/exceptions',[\s\S]*key:\s*\(\)\s*=>\s*`samples-exceptions-/)
  assert.match(source, /useFetch<PaginatedResponse<AnalysisJob>>\('\/api\/analysis-jobs',[\s\S]*key:\s*\(\)\s*=>\s*`samples-analysis-jobs-/)
})
