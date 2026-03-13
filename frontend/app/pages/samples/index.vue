<script setup lang="ts">
type Sample = {
  id: number
  sample_code: string
  inspection_task_id: number | null
  sample_type: string
  name: string | null
  status: string
  collection_time: string | null
  location_text: string | null
  collector_id: number | null
  collector_name: string | null
}

type PaginatedResponse<T> = {
  data: T[]
  meta: {
    page: number
    page_size: number
    total: number
  }
}

type SampleCreateResponse = {
  data: {
    id: number
    sample_code: string
    status: string
    created_at: string
  }
}

const { baseURL, request, getErrorMessage } = useApiClient()
const router = useRouter()

const filters = reactive({
  status: '',
  sample_type: '',
  sample_code: ''
})

const query = computed(() => ({
  ...(filters.status ? { status: filters.status } : {}),
  ...(filters.sample_type ? { sample_type: filters.sample_type } : {}),
  ...(filters.sample_code ? { sample_code: filters.sample_code } : {})
}))

const { data, pending, error, refresh } = await useFetch<PaginatedResponse<Sample>>('/api/samples', {
  baseURL,
  query
})

const samples = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta?.total ?? 0)

const showCreateForm = ref(false)
const createPending = ref(false)
const createError = ref('')

const sampleTypes = ['', 'water', 'sediment', 'organism', 'equipment_swab']
const sampleStatuses = ['', 'registered', 'received', 'testing', 'completed']

const form = reactive({
  sample_code: '',
  inspection_task_id: '',
  sample_type: 'water',
  name: '',
  collection_time: '',
  location_text: '',
  collector_id: '',
  notes: ''
})

const formatDateTime = (value: string | null) => {
  if (!value) {
    return '未设置'
  }

  return new Intl.DateTimeFormat('zh-CN', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value))
}

const resetForm = () => {
  form.sample_code = ''
  form.inspection_task_id = ''
  form.sample_type = 'water'
  form.name = ''
  form.collection_time = ''
  form.location_text = ''
  form.collector_id = ''
  form.notes = ''
  createError.value = ''
}

const createSample = async () => {
  createPending.value = true
  createError.value = ''

  try {
    const payload = {
      sample_code: form.sample_code,
      sample_type: form.sample_type,
      ...(form.inspection_task_id ? { inspection_task_id: Number(form.inspection_task_id) } : {}),
      ...(form.name ? { name: form.name } : {}),
      ...(form.collection_time ? { collection_time: form.collection_time } : {}),
      ...(form.location_text ? { location_text: form.location_text } : {}),
      ...(form.collector_id ? { collector_id: Number(form.collector_id) } : {}),
      ...(form.notes ? { notes: form.notes } : {})
    }

    const response = await request<SampleCreateResponse>('/api/samples', {
      method: 'POST',
      body: payload
    })

    resetForm()
    showCreateForm.value = false
    await refresh()
    await router.push(`/samples/${response.data.id}`)
  }
  catch (requestError) {
    createError.value = getErrorMessage(requestError, '样本创建失败，请检查表单后重试。')
  }
  finally {
    createPending.value = false
  }
}
</script>

<template>
  <UDashboardPanel id="samples">
    <template #header>
      <UDashboardNavbar title="样本管理">
        <template #leading>
          <UDashboardSidebarCollapse />
        </template>
      </UDashboardNavbar>
    </template>

    <template #body>
      <div class="space-y-4">
        <section class="grid gap-4 xl:grid-cols-[0.95fr_2.05fr]">
          <UCard>
            <template #header>
              <div>
                <h2 class="text-lg font-semibold text-highlighted">
                  样本筛选
                </h2>
                <p class="mt-1 text-sm text-toned">
                  查看已登记样本，并快速进入后续结果与分析链路。
                </p>
              </div>
            </template>

            <div class="space-y-4">
              <label class="block space-y-2 text-sm text-toned">
                <span class="font-medium text-highlighted">样本状态</span>
                <select v-model="filters.status" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                  <option v-for="value in sampleStatuses" :key="value" :value="value">
                    {{ value || '全部状态' }}
                  </option>
                </select>
              </label>

              <label class="block space-y-2 text-sm text-toned">
                <span class="font-medium text-highlighted">样本类型</span>
                <select v-model="filters.sample_type" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                  <option v-for="value in sampleTypes" :key="value" :value="value">
                    {{ value || '全部类型' }}
                  </option>
                </select>
              </label>

              <label class="block space-y-2 text-sm text-toned">
                <span class="font-medium text-highlighted">样本编号</span>
                <input
                  v-model.trim="filters.sample_code"
                  type="text"
                  placeholder="如 SP-20260311-001"
                  class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary"
                >
              </label>

              <div class="flex items-center justify-between rounded-2xl bg-primary/5 px-4 py-3 text-sm">
                <span class="text-toned">当前样本总数</span>
                <span class="font-semibold text-highlighted">{{ total }}</span>
              </div>

              <div class="flex gap-3">
                <UButton class="flex-1" @click="showCreateForm = !showCreateForm">
                  {{ showCreateForm ? '收起新建表单' : '登记样本' }}
                </UButton>
                <UButton color="neutral" variant="outline" class="flex-1" @click="refresh()">
                  刷新列表
                </UButton>
              </div>
            </div>
          </UCard>

          <div class="space-y-4">
            <UCard v-if="showCreateForm">
              <template #header>
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    登记样本
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    先提交最小必要字段，详情页中再继续查看结果与关联信息。
                  </p>
                </div>
              </template>

              <form class="grid gap-4 md:grid-cols-2" @submit.prevent="createSample">
                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">样本编号 *</span>
                  <input v-model.trim="form.sample_code" required type="text" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">样本类型 *</span>
                  <select v-model="form.sample_type" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                    <option value="water">water</option>
                    <option value="sediment">sediment</option>
                    <option value="organism">organism</option>
                    <option value="equipment_swab">equipment_swab</option>
                  </select>
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">样本名称</span>
                  <input v-model.trim="form.name" type="text" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">来源任务 ID</span>
                  <input v-model.trim="form.inspection_task_id" type="number" min="1" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">采集人 ID</span>
                  <input v-model.trim="form.collector_id" type="number" min="1" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned">
                  <span class="font-medium text-highlighted">采集时间</span>
                  <input v-model="form.collection_time" type="datetime-local" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned md:col-span-2">
                  <span class="font-medium text-highlighted">采集位置</span>
                  <input v-model.trim="form.location_text" type="text" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary">
                </label>

                <label class="block space-y-2 text-sm text-toned md:col-span-2">
                  <span class="font-medium text-highlighted">备注</span>
                  <textarea v-model.trim="form.notes" rows="3" class="w-full rounded-xl border border-default bg-default px-3 py-2 text-default outline-none transition focus:border-primary"></textarea>
                </label>

                <UAlert
                  v-if="createError"
                  class="md:col-span-2"
                  color="error"
                  variant="soft"
                  title="样本创建失败"
                  :description="createError"
                />

                <div class="flex gap-3 md:col-span-2">
                  <UButton type="submit" :loading="createPending">
                    创建样本
                  </UButton>
                  <UButton type="button" color="neutral" variant="outline" @click="resetForm(); showCreateForm = false">
                    取消
                  </UButton>
                </div>
              </form>
            </UCard>

            <UAlert
              v-if="error"
              color="error"
              variant="soft"
              title="样本列表加载失败"
              :description="'请检查后端接口或稍后重试。'"
            />

            <UCard v-else-if="pending">
              <div class="space-y-3">
                <div class="h-5 w-40 rounded bg-muted/60" />
                <div class="h-20 rounded-2xl bg-muted/40" />
                <div class="h-20 rounded-2xl bg-muted/40" />
              </div>
            </UCard>

            <UCard v-else-if="samples.length === 0">
              <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
                <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                  <UIcon name="i-lucide-flask-conical" class="size-6" />
                </div>
                <div>
                  <h2 class="text-lg font-semibold text-highlighted">
                    当前没有匹配的样本记录
                  </h2>
                  <p class="mt-1 text-sm text-toned">
                    你可以放宽筛选条件，或直接登记一条新的样本记录。
                  </p>
                </div>
              </div>
            </UCard>

            <div v-else class="grid gap-4">
              <UCard v-for="sample in samples" :key="sample.id">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                      <UBadge color="primary" variant="soft">
                        {{ sample.status }}
                      </UBadge>
                      <span class="text-xs uppercase tracking-[0.2em] text-muted">
                        {{ sample.sample_code }}
                      </span>
                    </div>

                    <div>
                      <h2 class="text-lg font-semibold text-highlighted">
                        {{ sample.name || '未命名样本' }}
                      </h2>
                      <p class="mt-1 text-sm text-toned">
                        {{ sample.location_text || '未记录采样位置' }}
                      </p>
                    </div>

                    <dl class="grid gap-3 text-sm text-toned sm:grid-cols-2 xl:grid-cols-4">
                      <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                          类型
                        </dt>
                        <dd class="mt-1 text-default">
                          {{ sample.sample_type }}
                        </dd>
                      </div>
                      <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                          来源任务
                        </dt>
                        <dd class="mt-1 text-default">
                          {{ sample.inspection_task_id ?? '未关联' }}
                        </dd>
                      </div>
                      <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                          采集人
                        </dt>
                        <dd class="mt-1 text-default">
                          {{ sample.collector_name || '未记录' }}
                        </dd>
                      </div>
                      <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-muted">
                          采集时间
                        </dt>
                        <dd class="mt-1 text-default">
                          {{ formatDateTime(sample.collection_time) }}
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <div class="flex shrink-0 items-center gap-3 lg:flex-col lg:items-end">
                    <UButton :to="`/samples/${sample.id}`" trailing icon="i-lucide-chevron-right">
                      查看详情
                    </UButton>
                  </div>
                </div>
              </UCard>
            </div>
          </div>
        </section>
      </div>
    </template>
  </UDashboardPanel>
</template>
