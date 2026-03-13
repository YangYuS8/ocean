<script setup lang="ts">
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

const route = useRoute()
const { baseURL } = useApiClient()

const sampleId = computed(() => String(route.params.id))

const { data, pending, error } = await useFetch<DetailResponse<SampleDetail>>(() => `/api/samples/${sampleId.value}`, {
  baseURL,
  watch: [sampleId]
})

const { data: resultsData, pending: resultsPending, error: resultsError } = await useFetch<DetailResponse<SampleResult[]>>(() => `/api/samples/${sampleId.value}/results`, {
  baseURL,
  watch: [sampleId]
})

const sample = computed(() => data.value?.data ?? null)
const results = computed(() => resultsData.value?.data ?? [])

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
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  检测结果
                </h2>
                <p class="mt-1 text-sm text-toned">
                  当前先接入只读结果列表，为后续新增结果、异常与分析任务挂载操作入口。
                </p>
              </div>
            </template>

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
        </div>

        <div class="space-y-4">
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
