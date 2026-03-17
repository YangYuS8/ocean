import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

test('sample detail useFetch calls declare stable async data keys', () => {
  const source = readFileSync(resolve(process.cwd(), 'app/pages/samples/[id].vue'), 'utf8')

  assert.match(source, /useFetch<DetailResponse<SampleDetail>>\([\s\S]*key:\s*\(\)\s*=>\s*`sample-detail-/)
  assert.match(source, /useFetch<DetailResponse<SampleResult\[]>>\([\s\S]*key:\s*\(\)\s*=>\s*`sample-results-/)
  assert.match(source, /useFetch<PaginatedResponse<SampleException>>\([\s\S]*key:\s*\(\)\s*=>\s*`sample-exceptions-/)
  assert.match(source, /useFetch<PaginatedResponse<AnalysisJob>>\([\s\S]*key:\s*\(\)\s*=>\s*`sample-analysis-jobs-/)
  assert.match(source, /useFetch<ImageSuggestionResponse>\([\s\S]*key:\s*\(\)\s*=>\s*`sample-image-suggestion-/)
})
