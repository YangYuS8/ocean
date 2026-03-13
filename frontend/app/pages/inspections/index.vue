<script setup lang="ts">
type InspectionTask = {
  id: number
  task_code: string
  title: string
  task_type: string
  priority: string
  status: string
  location_text: string | null
  planned_at: string | null
  due_at: string | null
  assigned_to: null | {
    id: number
    display_name: string
  }
}

type PaginatedResponse<T> = {
  data: T[]
  meta: {
    page: number
    page_size: number
    total: number
  }
}

const { baseURL } = useApiClient()
const status = ref('')
const keyword = ref('')

const query = computed(() => ({
  ...(status.value ? { status: status.value } : {}),
  ...(keyword.value ? { keyword: keyword.value } : {})
}))

const { data, pending, error, refresh } = await useFetch<PaginatedResponse<InspectionTask>>('/api/inspection-tasks', {
  baseURL,
  query
})

const tasks = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta?.total ?? 0)

const statusOptions = [
  { label: '全部状态', value: '' },
  { label: '已指派', value: 'assigned' },
  { label: '进行中', value: 'in_progress' },
  { label: '已提交', value: 'submitted' }
]

const formatDateTime = (value: string | null) => {
  if (!value) {
    return '未设置'
  }

  return new Intl.DateTimeFormat('zh-CN', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value))
}

const statusTone = (value: string) => {
  switch (value) {
    case 'assigned':
      return 'warning'
    case 'in_progress':
      return 'primary'
    case 'submitted':
      return 'success'
    default:
      return 'neutral'
  }
}

const priorityLabel = (value: string) => {
  return {
    low: '低',
    normal: '普通',
    high: '高',
    urgent: '紧急'
  }[value] ?? value
}
</script>

<template>
  <UDashboardPanel id="inspections">
    <template #header>
      <UDashboardNavbar title="巡检任务">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <section class="grid gap-4 xl:grid-cols-[0.95fr_2.05fr]">
        <UCard>
          <template #header>
            <div>
              <h2 class="text-lg font-semibold text-highlighted">
                筛选任务
              </h2>
              <p class="mt-1 text-sm text-toned">
                聚焦今日待执行、处理中和已提交的巡检任务。
              </p>
            </div>
          </template>

          <div class="space-y-4">
            <label class="block space-y-2 text-sm text-toned">
              <span class="font-medium text-highlighted">任务状态</span>
              <select v-model="status" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>

            <label class="block space-y-2 text-sm text-toned">
              <span class="font-medium text-highlighted">关键字</span>
              <input
                v-model.trim="keyword"
                type="text"
                placeholder="任务编号、标题、点位"
                class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary"
              >
            </label>

            <div class="flex items-center justify-between rounded-2xl bg-primary/5 px-4 py-3 text-sm">
              <span class="text-toned">当前任务总数</span>
              <span class="font-semibold text-highlighted">{{ total }}</span>
            </div>

            <UButton color="neutral" variant="outline" block @click="refresh()">
              刷新列表
            </UButton>
          </div>
        </UCard>

        <div class="space-y-4">
          <UAlert
            v-if="error"
            color="error"
            variant="soft"
            title="任务列表加载失败"
            :description="'请检查后端接口或稍后重试。'"
          />

          <UCard v-else-if="pending">
            <div class="space-y-3">
              <div class="h-5 w-40 rounded bg-muted/60" />
              <div class="h-20 rounded-2xl bg-muted/40" />
              <div class="h-20 rounded-2xl bg-muted/40" />
            </div>
          </UCard>

          <UCard v-else-if="tasks.length === 0">
            <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
              <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                <UIcon name="i-lucide-clipboard-search" class="size-6" />
              </div>
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  当前没有匹配的巡检任务
                </h2>
                <p class="mt-1 text-sm text-toned">
                  你可以调整筛选条件，或稍后等待新的任务被指派。
                </p>
              </div>
            </div>
          </UCard>

          <div v-else class="grid gap-4">
            <UCard v-for="task in tasks" :key="task.id" class="overflow-hidden">
              <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <UBadge :color="statusTone(task.status)" variant="soft">
                      {{ task.status }}
                    </UBadge>
                    <UBadge color="neutral" variant="outline">
                      {{ priorityLabel(task.priority) }}优先级
                    </UBadge>
                    <span class="text-xs uppercase tracking-[0.2em] text-muted">
                      {{ task.task_code }}
                    </span>
                  </div>

                  <div>
                    <h2 class="text-lg font-semibold text-highlighted">
                      {{ task.title }}
                    </h2>
                    <p class="mt-1 text-sm text-toned">
                      {{ task.location_text || '未设置巡检点位' }}
                    </p>
                  </div>

                  <dl class="grid gap-3 text-sm text-toned sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                      <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                        类型
                      </dt>
                      <dd class="mt-1 text-default">
                        {{ task.task_type }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                        计划时间
                      </dt>
                      <dd class="mt-1 text-default">
                        {{ formatDateTime(task.planned_at) }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                        截止时间
                      </dt>
                      <dd class="mt-1 text-default">
                        {{ formatDateTime(task.due_at) }}
                      </dd>
                    </div>
                    <div>
                      <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                        指派人
                      </dt>
                      <dd class="mt-1 text-default">
                        {{ task.assigned_to?.display_name || '未指派' }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <div class="flex shrink-0 items-center gap-3 lg:flex-col lg:items-end">
                  <UButton :to="`/inspections/${task.id}`" trailing icon="i-lucide-chevron-right">
                    查看详情
                  </UButton>
                </div>
              </div>
            </UCard>
          </div>
        </div>
      </section>
    </template>
  </UDashboardPanel>
</template>
