<script setup lang="ts">
import { buildSampleWorkspaceGuidance } from '../../utils/sampleWorkspaceGuidance.js'
import { buildSampleImageSuggestionView } from '../../utils/sampleImageSuggestionView.js'

type SampleMainImage = {
  file_name: string
  mime_type: string | null
  size: number | null
  version: number
  uploaded_at: string | null
  content_url: string
}

type UiTone = 'error' | 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'neutral'

type SampleDetail = {
  id: number
  sample_code: string
  inspection_task_id: number | null
  sample_type: string
  name: string | null
  status: string
  collection_time: string | null
  location_text: string | null
  collector: null | {
    id: number
    display_name: string
  }
  received_by: null | {
    id: number
    display_name: string
  }
  received_at: string | null
  notes: string | null
  main_image: SampleMainImage | null
  created_at: string | null
  updated_at: string | null
}

type SampleResult = {
  id: number
  sample_id: number
  result_type: string
  status: string
  raw_value: unknown
  normalized_value: unknown
  conclusion: string | null
  entered_by: null | {
    id: number
    display_name: string
  }
  entered_at: string | null
}

type DetailResponse<T> = {
  data: T
}

type SampleResultCreateResponse = {
  data: {
    id: number
    sample_id: number
    status: string
    created_at: string
  }
}

type SampleException = {
  id: number
  resource_type: string
  resource_id: number
  category: string
  severity: string
  title: string
  description: string | null
  status: string
  reported_by: null | {
    id: number
    display_name: string
  }
  resolved_by: null | {
    id: number
    display_name: string
  }
  resolved_at: string | null
  created_at: string | null
}

type PaginatedResponse<T> = {
  data: T[]
  meta: {
    page: number
    page_size: number
    total: number
  }
}

type SampleExceptionCreateResponse = {
  data: {
    id: number
    status: string
    created_at: string
  }
}

type SampleExceptionResolveResponse = {
  data: {
    id: number
    status: string
    resolved_at: string
  }
}

type AnalysisJob = {
  id: number
  sample_id: number
  job_type: string
  status: string
  result_summary: string | null
  suggestion?: {
    has_findings?: boolean
    counts?: Record<string, number>
    confidence_summary?: {
      top_score?: number
    }
  } | null
  error_message: string | null
  queued_by: null | {
    id: number
    display_name: string
  }
  queued_at: string | null
  started_at: string | null
  finished_at: string | null
}

type AnalysisJobCreateResponse = {
  data: {
    id: number
    sample_id: number
    job_type: string
    status: string
    queued_at: string
  }
}

type AnalysisJobActionResponse = {
  data: {
    id: number
    sample_id?: number
    job_type?: string
    status: string
    queued_at?: string
    started_at?: string
    finished_at?: string
    result_summary?: string | null
    error_message?: string | null
  }
}

type SampleMainImageUploadResponse = {
  data: {
    sample_id: number
    main_image: SampleMainImage
  }
}

type ImageSuggestionResponse = {
  data: {
    state: string
    summary: string | null
    suggestion: null | {
      has_findings?: boolean
      counts?: Record<string, number>
      confidence_summary?: {
        top_score?: number
      }
    }
    job: null | {
      id: number
      status: string
      finished_at: string | null
      started_at: string | null
      queued_at: string | null
      current_main_image_version: number
      job_main_image_version: number
    }
  }
}

const route = useRoute()
const { baseURL, request, getErrorMessage } = useApiClient()

const sampleId = computed(() => String(route.params.id))

const { data, pending, error, refresh: refreshSample } = await useFetch<DetailResponse<SampleDetail>>(() => `/api/samples/${sampleId.value}`, {
  baseURL,
  watch: [sampleId]
})

const { data: resultsData, pending: resultsPending, error: resultsError, refresh: refreshResults } = await useFetch<DetailResponse<SampleResult[]>>(() => `/api/samples/${sampleId.value}/results`, {
  baseURL,
  watch: [sampleId]
})

const exceptionQuery = computed(() => ({
  resource_type: 'sample',
  resource_id: Number(sampleId.value)
}))

const { data: exceptionsData, pending: exceptionsPending, error: exceptionsError, refresh: refreshExceptions } = await useFetch<PaginatedResponse<SampleException>>('/api/exceptions', {
  baseURL,
  query: exceptionQuery
})

const analysisJobQuery = computed(() => ({
  sample_id: Number(sampleId.value)
}))

const { data: analysisJobsData, pending: analysisJobsPending, error: analysisJobsError, refresh: refreshAnalysisJobs } = await useFetch<PaginatedResponse<AnalysisJob>>('/api/analysis-jobs', {
  baseURL,
  query: analysisJobQuery
})

const { data: imageSuggestionData, pending: imageSuggestionPending, error: imageSuggestionError, refresh: refreshImageSuggestion } = await useFetch<ImageSuggestionResponse>(() => `/api/samples/${sampleId.value}/image-suggestion`, {
  baseURL,
  watch: [sampleId]
})

const sample = computed(() => data.value?.data ?? null)
const results = computed(() => resultsData.value?.data ?? [])
const exceptions = computed(() => exceptionsData.value?.data ?? [])
const analysisJobs = computed(() => analysisJobsData.value?.data ?? [])
const imageSuggestion = computed(() => imageSuggestionData.value?.data ?? null)
const openExceptions = computed(() => exceptions.value.filter(item => item.status === 'open'))
const failedAnalysisJobs = computed(() => analysisJobs.value.filter(item => item.status === 'failed'))
const activeAnalysisJobs = computed(() => analysisJobs.value.filter(item => item.status === 'queued' || item.status === 'running'))
const imageSuggestionView = computed(() => buildSampleImageSuggestionView({
  sample: sample.value,
  suggestion: imageSuggestion.value
}))
const imageSuggestionTone = computed(() => imageSuggestionView.value.tone as UiTone)
const workspaceGuidance = computed(() => sample.value ? buildSampleWorkspaceGuidance({
  sample: sample.value,
  results: results.value,
  exceptions: exceptions.value,
  analysisJobs: analysisJobs.value
}) : null)

const resultTypeOptions = [
  {
    value: 'salinity_test',
    label: '盐度检测'
  },
  {
    value: 'ph_test',
    label: 'pH 检测'
  }
] as const

const resultTemplates = {
  salinity_test: {
    raw: {
      salinity: 31.2,
      unit: 'ppt'
    },
    normalized: {
      salinity: 31.2,
      unit: 'ppt',
      range_flag: 'normal'
    },
    conclusion: '盐度正常'
  },
  ph_test: {
    raw: {
      ph: 8.1,
      unit: 'pH'
    },
    normalized: {
      ph: 8.1,
      unit: 'pH',
      range_flag: 'normal'
    },
    conclusion: 'pH 在正常范围内'
  }
} as const

const showCreateResult = ref(false)
const createPending = ref(false)
const createSuccess = ref('')
const createError = ref('')
const rawValueError = ref('')
const normalizedValueError = ref('')

const resultForm = reactive({
  result_type: 'salinity_test',
  entered_by: '',
  raw_value: '',
  normalized_value: '',
  conclusion: '',
  notes: ''
})

const exceptionCategoryOptions = [
  { value: 'sample_quality', label: '样本质量问题' },
  { value: 'record_mismatch', label: '记录信息不一致' },
  { value: 'handling_issue', label: '处理过程问题' }
] as const

const exceptionSeverityOptions = [
  { value: 'low', label: '低' },
  { value: 'medium', label: '中' },
  { value: 'high', label: '高' },
  { value: 'critical', label: '严重' }
] as const

const showCreateException = ref(false)
const exceptionCreatePending = ref(false)
const exceptionResolveId = ref<number | null>(null)
const exceptionError = ref('')
const exceptionSuccess = ref('')

const exceptionForm = reactive({
  category: 'sample_quality',
  severity: 'medium',
  title: '',
  description: '',
  reported_by: ''
})

const analysisJobTypeOptions = [
  { value: 'quality_assessment', label: '质量评估' },
  { value: 'anomaly_scan', label: '异常扫描' },
  { value: 'object_detection', label: '自动检测' }
] as const

const showCreateAnalysisJob = ref(false)
const analysisJobCreatePending = ref(false)
const analysisJobError = ref('')
const analysisJobSuccess = ref('')
const analysisJobActionPendingId = ref<number | null>(null)
const mainImageUploadPending = ref(false)
const mainImageUploadError = ref('')
const mainImageUploadSuccess = ref('')
const mainImageInput = ref<HTMLInputElement | null>(null)

const analysisJobForm = reactive({
  job_type: 'quality_assessment',
  queued_by: ''
})

const getTemplateString = (resultType: keyof typeof resultTemplates, key: 'raw' | 'normalized') => {
  return JSON.stringify(resultTemplates[resultType][key], null, 2)
}

const applyTemplate = (resultType: keyof typeof resultTemplates) => {
  resultForm.raw_value = getTemplateString(resultType, 'raw')
  resultForm.normalized_value = getTemplateString(resultType, 'normalized')
  resultForm.conclusion = resultTemplates[resultType].conclusion
}

const resetResultForm = () => {
  resultForm.result_type = 'salinity_test'
  resultForm.entered_by = sample.value?.collector?.id ? String(sample.value.collector.id) : ''
  resultForm.notes = ''
  applyTemplate('salinity_test')
  createError.value = ''
  createSuccess.value = ''
  rawValueError.value = ''
  normalizedValueError.value = ''
}

const openCreateResult = () => {
  resetResultForm()
  showCreateResult.value = true
}

const resetExceptionForm = () => {
  exceptionForm.category = 'sample_quality'
  exceptionForm.severity = 'medium'
  exceptionForm.title = ''
  exceptionForm.description = ''
  exceptionForm.reported_by = sample.value?.collector?.id ? String(sample.value.collector.id) : ''
  exceptionError.value = ''
}

const openCreateException = () => {
  resetExceptionForm()
  showCreateException.value = true
}

const resetAnalysisJobForm = () => {
  analysisJobForm.job_type = 'quality_assessment'
  analysisJobForm.queued_by = sample.value?.collector?.id ? String(sample.value.collector.id) : ''
  analysisJobError.value = ''
}

const openCreateAnalysisJob = () => {
  resetAnalysisJobForm()
  showCreateAnalysisJob.value = true
}

watch(() => resultForm.result_type, (value) => {
  applyTemplate(value as keyof typeof resultTemplates)
  rawValueError.value = ''
  normalizedValueError.value = ''
})

const parseJsonObject = (value: string, fieldLabel: string) => {
  if (!value.trim()) {
    return null
  }

  try {
    const parsed = JSON.parse(value)

    if (parsed === null || Array.isArray(parsed) || typeof parsed !== 'object') {
      throw new Error(`${fieldLabel}必须是 JSON 对象。`)
    }

    return parsed
  }
  catch (error) {
    throw new Error(error instanceof Error ? error.message : `${fieldLabel}不是合法 JSON。`)
  }
}

const createResult = async () => {
  createPending.value = true
  createError.value = ''
  createSuccess.value = ''
  rawValueError.value = ''
  normalizedValueError.value = ''

  let rawValue: Record<string, unknown> | null = null
  let normalizedValue: Record<string, unknown> | null = null

  try {
    rawValue = parseJsonObject(resultForm.raw_value, '原始结果')
  }
  catch (error) {
    rawValueError.value = error instanceof Error ? error.message : '原始结果不是合法 JSON。'
  }

  try {
    normalizedValue = parseJsonObject(resultForm.normalized_value, '归一化结果')
  }
  catch (error) {
    normalizedValueError.value = error instanceof Error ? error.message : '归一化结果不是合法 JSON。'
  }

  if (rawValueError.value || normalizedValueError.value) {
    createPending.value = false
    return
  }

  try {
    await request<SampleResultCreateResponse>(`/api/samples/${sampleId.value}/results`, {
      method: 'POST',
      body: {
        result_type: resultForm.result_type,
        ...(rawValue ? { raw_value: rawValue } : {}),
        ...(normalizedValue ? { normalized_value: normalizedValue } : {}),
        ...(resultForm.conclusion.trim() ? { conclusion: resultForm.conclusion.trim() } : {}),
        ...(resultForm.entered_by ? { entered_by: Number(resultForm.entered_by) } : {}),
        ...(resultForm.notes.trim() ? { notes: resultForm.notes.trim() } : {})
      }
    })

    await Promise.all([refreshResults(), refreshSample()])
    createSuccess.value = '结果已录入，样本处理视图已刷新。'
    showCreateResult.value = false
  }
  catch (error) {
    createError.value = getErrorMessage(error, '结果录入失败，请稍后重试。')
  }
  finally {
    createPending.value = false
  }
}

const createException = async () => {
  exceptionCreatePending.value = true
  exceptionError.value = ''
  exceptionSuccess.value = ''

  try {
    await request<SampleExceptionCreateResponse>('/api/exceptions', {
      method: 'POST',
      body: {
        resource_type: 'sample',
        resource_id: Number(sampleId.value),
        category: exceptionForm.category,
        severity: exceptionForm.severity,
        title: exceptionForm.title.trim(),
        ...(exceptionForm.description.trim() ? { description: exceptionForm.description.trim() } : {}),
        ...(exceptionForm.reported_by ? { reported_by: Number(exceptionForm.reported_by) } : {})
      }
    })

    await refreshExceptions()
    exceptionSuccess.value = '异常已记录，当前样本的异常列表已刷新。'
    showCreateException.value = false
  }
  catch (error) {
    exceptionError.value = getErrorMessage(error, '异常记录失败，请稍后重试。')
  }
  finally {
    exceptionCreatePending.value = false
  }
}

const resolveException = async (id: number) => {
  exceptionResolveId.value = id
  exceptionError.value = ''
  exceptionSuccess.value = ''

  try {
    await request<SampleExceptionResolveResponse>(`/api/exceptions/${id}/resolve`, {
      method: 'POST',
      body: {
        resolved_by: sample.value?.collector?.id ?? 2
      }
    })

    await refreshExceptions()
    exceptionSuccess.value = '异常已解决，当前异常列表已刷新。'
  }
  catch (error) {
    exceptionError.value = getErrorMessage(error, '异常解决失败，请稍后重试。')
  }
  finally {
    exceptionResolveId.value = null
  }
}

const createAnalysisJob = async () => {
  analysisJobCreatePending.value = true
  analysisJobError.value = ''
  analysisJobSuccess.value = ''

  try {
    await request<AnalysisJobCreateResponse>('/api/analysis-jobs', {
      method: 'POST',
      body: {
        sample_id: Number(sampleId.value),
        job_type: analysisJobForm.job_type,
        ...(analysisJobForm.queued_by ? { queued_by: Number(analysisJobForm.queued_by) } : {})
      }
    })

    await refreshAnalysisJobs()
    analysisJobSuccess.value = '分析任务已发起，当前样本的任务列表已刷新。'
    showCreateAnalysisJob.value = false
  }
  catch (error) {
    analysisJobError.value = getErrorMessage(error, '分析任务发起失败，请稍后重试。')
  }
  finally {
    analysisJobCreatePending.value = false
  }
}

const refreshImageWorkspace = async () => {
  await Promise.all([refreshSample(), refreshAnalysisJobs(), refreshImageSuggestion()])
}

const triggerMainImageUpload = () => {
  mainImageInput.value?.click()
}

const uploadMainImage = async (event: Event) => {
  const input = event.target as HTMLInputElement | null
  const file = input?.files?.[0]

  if (!file) {
    return
  }

  mainImageUploadPending.value = true
  mainImageUploadError.value = ''
  mainImageUploadSuccess.value = ''

  try {
    const formData = new FormData()
    formData.append('image', file)

    await request<SampleMainImageUploadResponse>(`/api/samples/${sampleId.value}/main-image`, {
      method: 'POST',
      body: formData
    })

    await refreshImageWorkspace()
    mainImageUploadSuccess.value = '主图已更新，若需要新的建议，请重新运行自动检测。'
  }
  catch (error) {
    mainImageUploadError.value = getErrorMessage(error, '主图上传失败，请稍后重试。')
  }
  finally {
    if (input) {
      input.value = ''
    }

    mainImageUploadPending.value = false
  }
}

const createObjectDetectionJob = async () => {
  analysisJobCreatePending.value = true
  analysisJobError.value = ''
  analysisJobSuccess.value = ''

  try {
    await request<AnalysisJobCreateResponse>('/api/analysis-jobs', {
      method: 'POST',
      body: {
        sample_id: Number(sampleId.value),
        job_type: 'object_detection',
        ...(sample.value?.collector?.id ? { queued_by: sample.value.collector.id } : {})
      }
    })

    await Promise.all([refreshAnalysisJobs(), refreshImageSuggestion()])
    analysisJobSuccess.value = '自动检测已发起，稍后可在建议卡片和分析任务列表中查看状态。'
  }
  catch (error) {
    analysisJobError.value = getErrorMessage(error, '自动检测发起失败，请稍后重试。')
  }
  finally {
    analysisJobCreatePending.value = false
  }
}

const getAnalysisJobSuccessSummary = (job: AnalysisJob) => {
  if (job.job_type === 'object_detection') {
    return '自动检测已完成，可查看当前主图的建议摘要。'
  }

  if (job.job_type === 'quality_assessment') {
    return '质量评估已完成，可结合结果摘要继续录入样本结果或补充异常记录。'
  }

  return '异常扫描已完成，可根据扫描结果继续录入结果或判断是否需要记录异常。'
}

const getAnalysisJobFailureMessage = (job: AnalysisJob) => {
  if (job.job_type === 'object_detection') {
    return '自动检测未完成，请稍后重新发起。'
  }

  if (job.job_type === 'quality_assessment') {
    return '质量评估未完成，请稍后重新发起，或根据现场情况补充异常记录。'
  }

  return '异常扫描未完成，请稍后重新发起，或先记录当前样本的异常情况。'
}

const runAnalysisJobAction = async (jobId: number, path: string, successMessage: string, body?: Record<string, unknown>) => {
  analysisJobActionPendingId.value = jobId
  analysisJobError.value = ''
  analysisJobSuccess.value = ''

  try {
    await request<AnalysisJobActionResponse>(path, {
      method: 'POST',
      ...(body ? { body } : {})
    })

    await Promise.all([refreshAnalysisJobs(), refreshImageSuggestion()])
    analysisJobSuccess.value = successMessage
  }
  catch (error) {
    analysisJobError.value = getErrorMessage(error, '分析任务操作失败，请稍后重试。')
  }
  finally {
    analysisJobActionPendingId.value = null
  }
}

const startAnalysisJob = async (job: AnalysisJob) => {
  await runAnalysisJobAction(job.id, `/api/analysis-jobs/${job.id}/start`, '分析任务已开始执行，任务列表已刷新。')
}

const succeedAnalysisJob = async (job: AnalysisJob) => {
  await runAnalysisJobAction(
    job.id,
    `/api/analysis-jobs/${job.id}/succeed`,
    '分析任务已标记为成功，结果摘要与任务列表已刷新。',
    { result_summary: getAnalysisJobSuccessSummary(job) }
  )
}

const failAnalysisJob = async (job: AnalysisJob) => {
  await runAnalysisJobAction(
    job.id,
    `/api/analysis-jobs/${job.id}/fail`,
    '分析任务已标记为失败，失败原因与任务列表已刷新。',
    { error_message: getAnalysisJobFailureMessage(job) }
  )
}

const cancelAnalysisJob = async (job: AnalysisJob) => {
  await runAnalysisJobAction(job.id, `/api/analysis-jobs/${job.id}/cancel`, '分析任务已取消，任务列表已刷新。')
}

const retryAnalysisJob = async (job: AnalysisJob) => {
  await runAnalysisJobAction(job.id, `/api/analysis-jobs/${job.id}/retry`, '分析任务已重新发起，原失败记录已保留。')
}

const formatDateTime = (value: string | null) => {
  if (!value) {
    return '未设置'
  }

  return new Intl.DateTimeFormat('zh-CN', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value))
}

const stringifyValue = (value: unknown) => {
  if (value == null) {
    return '无'
  }

  if (typeof value === 'string') {
    return value
  }

  return JSON.stringify(value, null, 2)
}

const exceptionCategoryLabel = (value: string) => {
  return exceptionCategoryOptions.find(option => option.value === value)?.label || value
}

const exceptionSeverityTone = (value: string) => {
  switch (value) {
    case 'low':
      return 'info'
    case 'medium':
      return 'warning'
    case 'high':
      return 'error'
    case 'critical':
      return 'error'
    default:
      return 'neutral'
  }
}

const analysisJobTypeLabel = (value: string) => {
  return analysisJobTypeOptions.find(option => option.value === value)?.label || value
}

const analysisJobStatusTone = (value: string) => {
  switch (value) {
    case 'queued':
      return 'warning'
    case 'running':
      return 'primary'
    case 'succeeded':
      return 'success'
    case 'failed':
      return 'error'
    default:
      return 'neutral'
  }
}

const analysisJobStatusLabel = (value: string) => {
  switch (value) {
    case 'queued':
      return '待执行'
    case 'running':
      return '执行中'
    case 'succeeded':
      return '已完成'
    case 'failed':
      return '失败'
    case 'cancelled':
      return '已取消'
    default:
      return value
  }
}

const imageSuggestionActionLabel = (action: string) => {
  switch (action) {
    case 'upload':
      return '上传主图'
    case 'run':
      return '运行自动检测'
    case 'rerun':
      return '重新检测'
    case 'busy':
      return '检测中...'
    default:
      return '查看建议'
  }
}

const imageSuggestionActionIcon = (action: string) => {
  switch (action) {
    case 'upload':
      return 'i-lucide-upload'
    case 'run':
      return 'i-lucide-sparkles'
    case 'rerun':
      return 'i-lucide-rotate-ccw'
    case 'busy':
      return 'i-lucide-loader-circle'
    default:
      return 'i-lucide-sparkles'
  }
}

const runImageSuggestionPrimaryAction = async () => {
  const action = imageSuggestionView.value.primaryAction

  if (action === 'upload') {
    triggerMainImageUpload()
    return
  }

  if (action === 'run' || action === 'rerun') {
    await createObjectDetectionJob()
  }
}

const workspaceGuidanceActionLabel = (action: string) => {
  switch (action) {
    case 'result':
      return '去录入结果'
    case 'exception':
      return '去处理异常'
    case 'retry-analysis':
      return '重新发起分析'
    case 'wait':
      return '刷新分析状态'
    default:
      return '继续处理样本'
  }
}

const workspaceGuidanceActionIcon = (action: string) => {
  switch (action) {
    case 'result':
      return 'i-lucide-flask-conical'
    case 'exception':
      return 'i-lucide-alert-triangle'
    case 'retry-analysis':
      return 'i-lucide-rotate-ccw'
    case 'wait':
      return 'i-lucide-refresh-cw'
    default:
      return 'i-lucide-compass'
  }
}

const runWorkspaceNextStep = async () => {
  const action = workspaceGuidance.value?.nextStep.action

  switch (action) {
    case 'result':
      openCreateResult()
      return
    case 'exception':
      openCreateException()
      return
    case 'retry-analysis': {
      const failedJob = failedAnalysisJobs.value[0]
      if (failedJob) {
        await retryAnalysisJob(failedJob)
        return
      }
      openCreateAnalysisJob()
      return
    }
    case 'wait':
      await refreshAnalysisJobs()
      return
    default:
      openCreateAnalysisJob()
  }
}
</script>

<template>
  <UDashboardPanel id="sample-detail">
    <template #header>
      <UDashboardNavbar :title="sample?.name || '样本详情'">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>

        <template #right>
          <UButton to="/samples" color="neutral" variant="outline" icon="i-lucide-arrow-left">
            返回列表
          </UButton>
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <UAlert
        v-if="error"
        color="error"
        variant="soft"
        title="样本详情加载失败"
        :description="'请检查样本是否存在，或稍后重试。'"
      />

      <UCard v-else-if="pending || !sample">
        <div class="space-y-3">
          <div class="h-6 w-48 rounded bg-muted/60" />
          <div class="h-24 rounded-2xl bg-muted/40" />
          <div class="h-24 rounded-2xl bg-muted/40" />
        </div>
      </UCard>

      <div v-else class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="space-y-4">
          <UCard>
            <template #header>
              <div class="flex flex-wrap items-center gap-2">
                <UBadge color="primary" variant="soft">
                  {{ sample.status }}
                </UBadge>
                <span class="text-xs uppercase tracking-[0.2em] text-muted">
                  {{ sample.sample_code }}
                </span>
              </div>
            </template>

            <div class="space-y-6">
              <div>
                <h2 class="text-2xl font-semibold text-highlighted">
                  {{ sample.name || '未命名样本' }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-toned">
                  {{ sample.notes || '当前样本暂无补充说明。' }}
                </p>
              </div>

              <dl class="grid gap-4 text-sm text-toned sm:grid-cols-2">
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">样本类型</dt>
                  <dd class="mt-1 text-default">{{ sample.sample_type }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">采集位置</dt>
                  <dd class="mt-1 text-default">{{ sample.location_text || '未设置' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">采集时间</dt>
                  <dd class="mt-1 text-default">{{ formatDateTime(sample.collection_time) }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">采集人</dt>
                  <dd class="mt-1 text-default">{{ sample.collector?.display_name || '未记录' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">接收人</dt>
                  <dd class="mt-1 text-default">{{ sample.received_by?.display_name || '未记录' }}</dd>
                </div>
                <div>
                  <dt class="text-xs uppercase tracking-[0.18em] text-muted">接收时间</dt>
                  <dd class="mt-1 text-default">{{ formatDateTime(sample.received_at) }}</dd>
                </div>
              </dl>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    检测结果
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    结果录入现在直接附着在样本详情内完成，保持 MVP 处理链最短闭环。
                  </p>
                </div>

                <UButton icon="i-lucide-plus" @click="openCreateResult">
                  新增结果
                </UButton>
              </div>
            </template>

            <div v-if="showCreateResult" class="mb-4 rounded-2xl border border-default bg-muted/20 p-4">
              <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-base font-semibold text-highlighted">
                    录入实验结果
                  </h3>
                  <p class="mt-1 text-sm text-toned">
                    当前先支持 `salinity_test` 与 `ph_test`，并通过 JSON 模板降低录入门槛。
                  </p>
                </div>

                <UButton type="button" color="neutral" variant="ghost" icon="i-lucide-x" @click="showCreateResult = false" />
              </div>

              <form class="space-y-4" @submit.prevent="createResult">
                <div class="grid gap-4 md:grid-cols-2">
                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">结果类型</span>
                    <select v-model="resultForm.result_type" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                      <option v-for="option in resultTypeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>

                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">录入人 ID</span>
                    <input v-model.trim="resultForm.entered_by" type="number" min="1" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                  </label>
                </div>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">结论</span>
                  <input v-model.trim="resultForm.conclusion" type="text" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <div class="grid gap-4 xl:grid-cols-2">
                  <label class="block space-y-2 text-sm text-toned">
                    <span class="flex items-center justify-between gap-3 font-medium text-highlighted">
                      <span>原始结果 JSON</span>
                      <UButton type="button" color="neutral" variant="outline" size="xs" @click="resultForm.raw_value = getTemplateString(resultForm.result_type as keyof typeof resultTemplates, 'raw')">
                        恢复模板
                      </UButton>
                    </span>
                    <textarea v-model="resultForm.raw_value" rows="9" class="w-full rounded-2xl border border-default bg-default px-3 py-2 font-mono text-xs text-default outline-none transition focus:border-primary"></textarea>
                    <p class="text-xs text-muted">
                      {{ resultForm.result_type === 'salinity_test' ? '推荐字段：salinity, unit' : '推荐字段：ph, unit' }}
                    </p>
                    <p v-if="rawValueError" class="text-xs text-error">
                      {{ rawValueError }}
                    </p>
                  </label>

                  <label class="block space-y-2 text-sm text-toned">
                    <span class="flex items-center justify-between gap-3 font-medium text-highlighted">
                      <span>归一化结果 JSON（可选）</span>
                      <UButton type="button" color="neutral" variant="outline" size="xs" @click="resultForm.normalized_value = getTemplateString(resultForm.result_type as keyof typeof resultTemplates, 'normalized')">
                        恢复模板
                      </UButton>
                    </span>
                    <textarea v-model="resultForm.normalized_value" rows="9" class="w-full rounded-2xl border border-default bg-default px-3 py-2 font-mono text-xs text-default outline-none transition focus:border-primary"></textarea>
                    <p class="text-xs text-muted">
                      可留空；若填写，建议补充 `range_flag` 字段。
                    </p>
                    <p v-if="normalizedValueError" class="text-xs text-error">
                      {{ normalizedValueError }}
                    </p>
                  </label>
                </div>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">备注（可选）</span>
                  <textarea v-model.trim="resultForm.notes" rows="3" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary"></textarea>
                </label>

                <UAlert
                  v-if="createError"
                  color="error"
                  variant="soft"
                  title="结果录入失败"
                  :description="createError"
                />

                <div class="flex flex-wrap gap-3">
                  <UButton type="submit" :loading="createPending">
                    提交结果
                  </UButton>
                  <UButton type="button" color="neutral" variant="outline" @click="resetResultForm()">
                    重置表单
                  </UButton>
                </div>
              </form>
            </div>

            <UAlert
              v-if="createSuccess"
              class="mb-4"
              color="success"
              variant="soft"
              title="结果录入成功"
              :description="createSuccess"
            />

            <UAlert
              v-if="resultsError"
              color="error"
              variant="soft"
              title="结果列表加载失败"
              :description="'请稍后重试。'"
            />

            <div v-else-if="resultsPending" class="space-y-3">
              <div class="h-20 rounded-2xl bg-muted/40" />
              <div class="h-20 rounded-2xl bg-muted/40" />
            </div>

            <div v-else-if="results.length === 0" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
              <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <UIcon name="i-lucide-file-search" class="size-6" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-highlighted">
                  当前还没有检测结果
                </h3>
                <p class="mt-1 text-sm text-toned">
                  这一块已经准备好承接后续的结果录入、异常登记和分析任务发起能力。
                </p>
              </div>
            </div>

            <div v-else class="space-y-4">
              <UCard v-for="result in results" :key="result.id" class="bg-muted/20">
                <div class="space-y-3 text-sm">
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge color="primary" variant="soft">
                      {{ result.result_type }}
                    </UBadge>
                    <UBadge color="neutral" variant="outline">
                      {{ result.status }}
                    </UBadge>
                  </div>

                  <div class="grid gap-3 md:grid-cols-2">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        录入人
                      </p>
                      <p class="mt-1 text-toned">
                        {{ result.entered_by?.display_name || '未记录' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        录入时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(result.entered_at) }}
                      </p>
                    </div>
                  </div>

                  <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      结论
                    </p>
                    <p class="mt-1 text-toned">
                      {{ result.conclusion || '暂无结论' }}
                    </p>
                  </div>

                  <div class="grid gap-3 md:grid-cols-2">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        原始值
                      </p>
                      <pre class="mt-1 overflow-x-auto rounded-xl bg-default p-3 text-xs text-toned">{{ stringifyValue(result.raw_value) }}</pre>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        归一化值
                      </p>
                      <pre class="mt-1 overflow-x-auto rounded-xl bg-default p-3 text-xs text-toned">{{ stringifyValue(result.normalized_value) }}</pre>
                    </div>
                  </div>
                </div>
              </UCard>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    异常处理
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    围绕当前样本记录质量问题、记录不一致或处理过程问题，并在需要时关闭异常。
                  </p>
                </div>

                <UButton icon="i-lucide-triangle-alert" @click="openCreateException">
                  记录异常
                </UButton>
              </div>
            </template>

            <div v-if="showCreateException" class="mb-4 rounded-2xl border border-default bg-muted/20 p-4">
              <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-base font-semibold text-highlighted">
                    记录样本异常
                  </h3>
                  <p class="mt-1 text-sm text-toned">
                    第一版异常固定挂载到当前样本，且只影响异常自身状态，不自动改动样本状态。
                  </p>
                </div>

                <UButton type="button" color="neutral" variant="ghost" icon="i-lucide-x" @click="showCreateException = false" />
              </div>

              <form class="space-y-4" @submit.prevent="createException">
                <div class="grid gap-4 md:grid-cols-2">
                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">异常分类</span>
                    <select v-model="exceptionForm.category" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                      <option v-for="option in exceptionCategoryOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>

                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">严重级别</span>
                    <select v-model="exceptionForm.severity" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                      <option v-for="option in exceptionSeverityOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>
                </div>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">异常标题</span>
                  <input v-model.trim="exceptionForm.title" required type="text" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">异常描述（可选）</span>
                  <textarea v-model.trim="exceptionForm.description" rows="4" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary"></textarea>
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">上报人 ID</span>
                  <input v-model.trim="exceptionForm.reported_by" type="number" min="1" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <UAlert
                  v-if="exceptionError"
                  color="error"
                  variant="soft"
                  title="异常处理失败"
                  :description="exceptionError"
                />

                <div class="flex flex-wrap gap-3">
                  <UButton type="submit" :loading="exceptionCreatePending">
                    提交异常
                  </UButton>
                  <UButton type="button" color="neutral" variant="outline" @click="resetExceptionForm()">
                    重置表单
                  </UButton>
                </div>
              </form>
            </div>

            <UAlert
              v-if="exceptionSuccess"
              class="mb-4"
              color="success"
              variant="soft"
              title="异常处理成功"
              :description="exceptionSuccess"
            />

            <UAlert
              v-if="exceptionsError"
              color="error"
              variant="soft"
              title="异常列表加载失败"
              :description="'请稍后重试。'"
            />

            <div v-else-if="exceptionsPending" class="space-y-3">
              <div class="h-20 rounded-2xl bg-muted/40" />
              <div class="h-20 rounded-2xl bg-muted/40" />
            </div>

            <div v-else-if="exceptions.length === 0" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
              <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <UIcon name="i-lucide-shield-check" class="size-6" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-highlighted">
                  当前没有异常记录
                </h3>
                <p class="mt-1 text-sm text-toned">
                  如果在结果录入或样本处理过程中发现质量、记录或处理问题，可以在这里登记异常。
                </p>
              </div>
            </div>

            <div v-else class="space-y-4">
              <UCard v-for="exception in exceptions" :key="exception.id" class="bg-muted/20">
                <div class="space-y-3 text-sm">
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge :color="exceptionSeverityTone(exception.severity)" variant="soft">
                      {{ exception.severity }}
                    </UBadge>
                    <UBadge color="neutral" variant="outline">
                      {{ exception.status }}
                    </UBadge>
                    <span class="text-xs uppercase tracking-[0.18em] text-muted">
                      {{ exceptionCategoryLabel(exception.category) }}
                    </span>
                  </div>

                  <div>
                    <h3 class="text-base font-semibold text-highlighted">
                      {{ exception.title }}
                    </h3>
                    <p class="mt-1 text-toned">
                      {{ exception.description || '当前异常暂无补充说明。' }}
                    </p>
                  </div>

                  <div class="grid gap-3 md:grid-cols-2">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        上报人
                      </p>
                      <p class="mt-1 text-toned">
                        {{ exception.reported_by?.display_name || '未记录' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        创建时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(exception.created_at) }}
                      </p>
                    </div>
                  </div>

                  <div v-if="exception.status === 'resolved'" class="grid gap-3 md:grid-cols-2">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        处理人
                      </p>
                      <p class="mt-1 text-toned">
                        {{ exception.resolved_by?.display_name || '未记录' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        解决时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(exception.resolved_at) }}
                      </p>
                    </div>
                  </div>

                  <div v-if="exception.status === 'open'" class="flex justify-end">
                    <UButton
                      color="neutral"
                      variant="outline"
                      :loading="exceptionResolveId === exception.id"
                      @click="resolveException(exception.id)"
                    >
                      解决异常
                    </UButton>
                  </div>
                </div>
              </UCard>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    分析任务
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    围绕当前样本发起质量评估或异常扫描，并在这里查看任务状态。
                  </p>
                </div>

                <UButton icon="i-lucide-sparkles" @click="openCreateAnalysisJob">
                  发起分析
                </UButton>
              </div>
            </template>

            <div v-if="showCreateAnalysisJob" class="mb-4 rounded-2xl border border-default bg-muted/20 p-4">
              <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-base font-semibold text-highlighted">
                    发起分析任务
                  </h3>
                  <p class="mt-1 text-sm text-toned">
                    第一版只支持最小任务类型集合，参数系统后续再扩展。
                  </p>
                </div>

                <UButton type="button" color="neutral" variant="ghost" icon="i-lucide-x" @click="showCreateAnalysisJob = false" />
              </div>

              <form class="space-y-4" @submit.prevent="createAnalysisJob">
                <div class="grid gap-4 md:grid-cols-2">
                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">分析类型</span>
                    <select v-model="analysisJobForm.job_type" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                      <option v-for="option in analysisJobTypeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>

                  <label class="block space-y-2 text-sm text-toned">
                    <span class="font-medium text-highlighted">发起人 ID</span>
                    <input v-model.trim="analysisJobForm.queued_by" type="number" min="1" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                  </label>
                </div>

                <div class="rounded-2xl bg-primary/5 px-4 py-3 text-sm text-toned">
                  <p>
                    当前样本上下文：<span class="font-semibold text-highlighted">#{{ sample.id }}</span>
                  </p>
                  <p class="mt-1">
                    任务创建后会进入 <span class="font-semibold text-highlighted">queued</span> 状态，并计入首页分析任务摘要。
                  </p>
                </div>

                <UAlert
                  v-if="analysisJobError"
                  color="error"
                  variant="soft"
                  title="分析任务发起失败"
                  :description="analysisJobError"
                />

                <div class="flex flex-wrap gap-3">
                  <UButton type="submit" :loading="analysisJobCreatePending">
                    提交任务
                  </UButton>
                  <UButton type="button" color="neutral" variant="outline" @click="resetAnalysisJobForm()">
                    重置表单
                  </UButton>
                </div>
              </form>
            </div>

            <UAlert
              v-if="analysisJobSuccess"
              class="mb-4"
              color="success"
              variant="soft"
              title="分析任务已创建"
              :description="analysisJobSuccess"
            />

            <UAlert
              v-if="analysisJobsError"
              color="error"
              variant="soft"
              title="分析任务列表加载失败"
              :description="'请稍后重试。'"
            />

            <div v-else-if="analysisJobsPending" class="space-y-3">
              <div class="h-20 rounded-2xl bg-muted/40" />
              <div class="h-20 rounded-2xl bg-muted/40" />
            </div>

            <div v-else-if="analysisJobs.length === 0" class="flex flex-col items-center justify-center gap-3 py-10 text-center">
              <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <UIcon name="i-lucide-sparkles" class="size-6" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-highlighted">
                  当前没有分析任务
                </h3>
                <p class="mt-1 text-sm text-toned">
                  当需要对当前样本进行质量评估或异常扫描时，可以在这里直接发起分析任务。
                </p>
              </div>
            </div>

            <div v-else class="space-y-4">
              <UCard v-for="job in analysisJobs" :key="job.id" class="bg-muted/20">
                <div class="space-y-3 text-sm">
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge :color="analysisJobStatusTone(job.status)" variant="soft">
                      {{ analysisJobStatusLabel(job.status) }}
                    </UBadge>
                    <span class="text-xs uppercase tracking-[0.18em] text-muted">
                      {{ analysisJobTypeLabel(job.job_type) }}
                    </span>
                    <span class="text-xs text-muted">
                      #{{ job.id }}
                    </span>
                  </div>

                  <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        发起人
                      </p>
                      <p class="mt-1 text-toned">
                        {{ job.queued_by?.display_name || '未记录' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        入队时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(job.queued_at) }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        开始时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(job.started_at) }}
                      </p>
                    </div>
                    <div>
                      <p class="text-xs uppercase tracking-[0.18em] text-muted">
                        完成时间
                      </p>
                      <p class="mt-1 text-toned">
                        {{ formatDateTime(job.finished_at) }}
                      </p>
                    </div>
                  </div>

                  <div v-if="job.status === 'succeeded' && job.result_summary">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      结果摘要
                    </p>
                    <p class="mt-1 text-toned">
                      {{ job.result_summary }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <UButton color="primary" variant="soft" icon="i-lucide-flask-conical" @click="openCreateResult()">
                        去录入结果
                      </UButton>
                      <UButton color="warning" variant="soft" icon="i-lucide-alert-triangle" @click="openCreateException()">
                        记录异常
                      </UButton>
                      <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-xs text-toned">
                        也可以暂不处理，稍后回到该样本继续判断。
                      </span>
                    </div>
                  </div>

                  <div v-if="job.status === 'failed' && job.error_message">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      失败原因
                    </p>
                    <p class="mt-1 text-error">
                      {{ job.error_message }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <UButton
                        color="primary"
                        variant="soft"
                        icon="i-lucide-rotate-ccw"
                        :loading="analysisJobActionPendingId === job.id"
                        @click="retryAnalysisJob(job)"
                      >
                        重新发起分析
                      </UButton>
                      <UButton color="warning" variant="soft" icon="i-lucide-alert-triangle" @click="openCreateException()">
                        记录异常
                      </UButton>
                    </div>
                  </div>

                  <div v-if="job.status === 'queued'" class="flex flex-wrap gap-2">
                    <UButton
                      color="primary"
                      variant="soft"
                      icon="i-lucide-play"
                      :loading="analysisJobActionPendingId === job.id"
                      @click="startAnalysisJob(job)"
                    >
                      开始执行
                    </UButton>
                    <UButton
                      color="neutral"
                      variant="outline"
                      icon="i-lucide-ban"
                      :loading="analysisJobActionPendingId === job.id"
                      @click="cancelAnalysisJob(job)"
                    >
                      取消任务
                    </UButton>
                  </div>

                  <div v-if="job.status === 'running'" class="flex flex-wrap gap-2">
                    <UButton
                      color="success"
                      variant="soft"
                      icon="i-lucide-check"
                      :loading="analysisJobActionPendingId === job.id"
                      @click="succeedAnalysisJob(job)"
                    >
                      标记成功
                    </UButton>
                    <UButton
                      color="error"
                      variant="soft"
                      icon="i-lucide-x"
                      :loading="analysisJobActionPendingId === job.id"
                      @click="failAnalysisJob(job)"
                    >
                      标记失败
                    </UButton>
                  </div>

                  <div v-if="job.status === 'cancelled'" class="rounded-2xl bg-muted/50 px-4 py-3 text-xs text-toned">
                    该分析任务已取消，不会继续执行。若仍需分析当前样本，可重新发起新的分析任务。
                  </div>
                </div>
              </UCard>
            </div>
          </UCard>
        </div>

        <div class="space-y-4">
          <UCard>
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    当前主图
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    MVP 阶段每个样本只有一张主图，自动检测默认基于当前主图运行。
                  </p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <input
                    ref="mainImageInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="uploadMainImage"
                  >
                  <UButton icon="i-lucide-upload" :loading="mainImageUploadPending" @click="triggerMainImageUpload">
                    {{ sample.main_image ? '替换主图' : '上传主图' }}
                  </UButton>
                </div>
              </div>
            </template>

            <div class="space-y-4">
              <UAlert
                v-if="mainImageUploadError"
                color="error"
                variant="soft"
                title="主图上传失败"
                :description="mainImageUploadError"
              />

              <UAlert
                v-if="mainImageUploadSuccess"
                color="success"
                variant="soft"
                title="主图已更新"
                :description="mainImageUploadSuccess"
              />

              <div v-if="sample.main_image" class="space-y-4">
                <img
                  :src="sample.main_image.content_url"
                  :alt="`${sample.sample_code} 主图`"
                  class="max-h-72 w-full rounded-2xl border border-default object-cover"
                >

                <div class="grid gap-3 sm:grid-cols-2">
                  <div class="rounded-2xl border border-default bg-muted/20 px-4 py-3 text-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      文件名
                    </p>
                    <p class="mt-2 font-medium text-highlighted">
                      {{ sample.main_image.file_name }}
                    </p>
                  </div>
                  <div class="rounded-2xl border border-default bg-muted/20 px-4 py-3 text-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      主图版本
                    </p>
                    <p class="mt-2 font-medium text-highlighted">
                      v{{ sample.main_image.version }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-else class="rounded-2xl border border-dashed border-default bg-muted/10 px-4 py-8 text-center text-sm text-toned">
                当前样本还没有主图，上传后才能运行自动检测。
              </div>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    自动检测建议
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    基于当前主图生成，仅供人工参考，不会自动写入正式结果。
                  </p>
                </div>

                <UButton
                  :icon="imageSuggestionActionIcon(imageSuggestionView.primaryAction)"
                  :loading="analysisJobCreatePending && (imageSuggestionView.primaryAction === 'run' || imageSuggestionView.primaryAction === 'rerun')"
                  :disabled="imageSuggestionView.primaryAction === 'busy' || mainImageUploadPending"
                  @click="runImageSuggestionPrimaryAction"
                >
                  {{ imageSuggestionActionLabel(imageSuggestionView.primaryAction) }}
                </UButton>
              </div>
            </template>

            <div class="space-y-4">
              <UAlert
                v-if="imageSuggestionError"
                color="error"
                variant="soft"
                title="自动检测建议加载失败"
                :description="'请稍后刷新页面重试。'"
              />

              <div v-else-if="imageSuggestionPending" class="space-y-3">
                <div class="h-20 rounded-2xl bg-muted/40" />
                <div class="h-20 rounded-2xl bg-muted/40" />
              </div>

              <div v-else class="space-y-4">
                <div class="rounded-2xl border border-default bg-muted/20 px-4 py-4">
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge :color="imageSuggestionTone" variant="soft">
                      {{ imageSuggestionView.title }}
                    </UBadge>
                    <span v-if="imageSuggestion?.job?.id" class="text-xs text-muted">
                      Job #{{ imageSuggestion.job.id }}
                    </span>
                  </div>

                  <p class="mt-3 text-sm text-toned">
                    {{ imageSuggestionView.description }}
                  </p>

                  <p class="mt-3 text-base font-medium text-highlighted">
                    {{ imageSuggestionView.summary }}
                  </p>

                  <p v-if="imageSuggestionView.meta" class="mt-2 text-xs text-muted">
                    {{ imageSuggestionView.meta }}
                  </p>
                </div>

                <div v-if="imageSuggestion?.suggestion?.counts && Object.keys(imageSuggestion.suggestion.counts).length > 0" class="grid gap-3 sm:grid-cols-2">
                  <div
                    v-for="(count, label) in imageSuggestion.suggestion.counts"
                    :key="label"
                    class="rounded-2xl border border-default bg-default px-4 py-3"
                  >
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">
                      {{ label }}
                    </p>
                    <p class="mt-2 text-2xl font-semibold text-highlighted">
                      {{ count }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </UCard>

          <UCard v-if="workspaceGuidance">
            <template #header>
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge :color="workspaceGuidance.summaryTone" variant="soft">
                      工作台摘要
                    </UBadge>
                    <span class="text-xs uppercase tracking-[0.18em] text-muted">
                      Sample Workspace
                    </span>
                  </div>
                  <h2 class="mt-3 text-lg font-semibold text-highlighted">
                    {{ workspaceGuidance.summaryTitle }}
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    {{ workspaceGuidance.summaryDescription }}
                  </p>
                </div>

                <UButton
                  :icon="workspaceGuidanceActionIcon(workspaceGuidance.nextStep.action)"
                  @click="runWorkspaceNextStep"
                >
                  {{ workspaceGuidanceActionLabel(workspaceGuidance.nextStep.action) }}
                </UButton>
              </div>
            </template>

            <div class="space-y-4">
              <div class="grid gap-3 sm:grid-cols-2">
                <div
                  v-for="stat in workspaceGuidance.stats"
                  :key="stat.label"
                  class="rounded-2xl border border-default bg-muted/20 px-4 py-3"
                >
                  <p class="text-xs uppercase tracking-[0.18em] text-muted">
                    {{ stat.label }}
                  </p>
                  <p class="mt-2 text-2xl font-semibold text-highlighted">
                    {{ stat.value }}
                  </p>
                </div>
              </div>

              <div class="rounded-2xl bg-primary/5 px-4 py-4 text-sm">
                <p class="text-xs uppercase tracking-[0.18em] text-muted">
                  推荐下一步
                </p>
                <p class="mt-2 font-medium text-highlighted">
                  {{ workspaceGuidance.nextStep.title }}
                </p>
                <p class="mt-1 text-toned">
                  {{ workspaceGuidance.nextStep.description }}
                </p>
              </div>

              <div v-if="workspaceGuidance.risks.length > 0" class="space-y-3">
                <div
                  v-for="risk in workspaceGuidance.risks"
                  :key="risk.kind"
                  class="rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm"
                >
                  <p class="font-medium text-highlighted">
                    {{ risk.title }}
                  </p>
                  <p class="mt-1 text-toned">
                    {{ risk.description }}
                  </p>
                </div>
              </div>

              <div class="flex flex-wrap gap-2">
                <UButton color="primary" variant="soft" icon="i-lucide-flask-conical" @click="openCreateResult()">
                  新增结果
                </UButton>
                <UButton color="warning" variant="soft" icon="i-lucide-alert-triangle" @click="openCreateException()">
                  记录异常
                </UButton>
                <UButton color="neutral" variant="outline" icon="i-lucide-sparkles" @click="openCreateAnalysisJob()">
                  发起分析
                </UButton>
              </div>
            </div>
          </UCard>

          <UCard>
            <template #header>
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  关联工作流
                </h2>
                <p class="mt-1 text-sm text-toned">
                  在巡检任务、样本结果与后续分析动作之间保持最小可导航关系。
                </p>
              </div>
            </template>

            <div class="flex flex-col gap-3">
              <div class="rounded-2xl bg-primary/5 px-4 py-3 text-sm text-toned">
                <p>
                  当前样本状态：<span class="font-semibold text-highlighted">{{ sample.status }}</span>
                </p>
                <p class="mt-1">
                  结果录入成功后，`registered` / `received` 样本会自动推进到 `testing`。
                </p>
                <p v-if="openExceptions.length > 0" class="mt-1">
                  当前仍有 <span class="font-semibold text-highlighted">{{ openExceptions.length }}</span> 条未解决异常，需要结合现场情况继续判断。
                </p>
                <p v-if="failedAnalysisJobs.length > 0" class="mt-1">
                  当前有 <span class="font-semibold text-highlighted">{{ failedAnalysisJobs.length }}</span> 个失败分析任务，建议优先查看失败原因或重新发起分析。
                </p>
                <p v-if="activeAnalysisJobs.length > 0" class="mt-1">
                  当前还有 <span class="font-semibold text-highlighted">{{ activeAnalysisJobs.length }}</span> 个待执行或执行中的分析任务。
                </p>
              </div>

              <UButton
                v-if="sample.inspection_task_id"
                :to="`/inspections/${sample.inspection_task_id}`"
                color="neutral"
                variant="outline"
                icon="i-lucide-clipboard-check"
              >
                查看来源任务 #{{ sample.inspection_task_id }}
              </UButton>

              <UButton to="/samples" color="neutral" variant="outline" icon="i-lucide-list">
                返回样本列表
              </UButton>
            </div>
          </UCard>
        </div>
      </div>
    </template>
  </UDashboardPanel>
</template>
