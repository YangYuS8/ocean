import type { NavigationMenuItem } from '@nuxt/ui'
import { createSharedComposable } from '@vueuse/core'

const _useWorkspace = () => {
  const route = useRoute()
  const router = useRouter()
  const sidebarOpen = ref(false)

  const primaryLinks = computed<NavigationMenuItem[][]>(() => [[
    {
      label: '总览',
      icon: 'i-lucide-layout-dashboard',
      to: '/',
      active: route.path === '/',
      onSelect: () => { sidebarOpen.value = false }
    },
    {
      label: '样本管理',
      icon: 'i-lucide-flask-conical',
      to: '/samples',
      active: route.path.startsWith('/samples'),
      onSelect: () => { sidebarOpen.value = false }
    },
    {
      label: '巡检任务',
      icon: 'i-lucide-clipboard-check',
      to: '/inspections',
      active: route.path.startsWith('/inspections'),
      onSelect: () => { sidebarOpen.value = false }
    },
    {
      label: '仪器监控',
      icon: 'i-lucide-activity',
      to: '/equipment',
      active: route.path.startsWith('/equipment'),
      onSelect: () => { sidebarOpen.value = false }
    },
    {
      label: '统计报表',
      icon: 'i-lucide-chart-column-big',
      to: '/reports',
      active: route.path.startsWith('/reports'),
      onSelect: () => { sidebarOpen.value = false }
    },
    {
      label: '系统设置',
      icon: 'i-lucide-settings',
      to: '/settings',
      active: route.path.startsWith('/settings'),
      onSelect: () => { sidebarOpen.value = false }
    }
  ]])

  const supportLinks = computed<NavigationMenuItem[][]>(() => [[
    {
      label: '项目说明',
      icon: 'i-lucide-book-open-text',
      to: '/settings'
    }
  ]])

  const commandGroups = computed(() => [{
    id: 'workspace',
    label: '工作区',
    items: primaryLinks.value.flat().map(item => ({
      label: item.label as string,
      icon: item.icon as string,
      to: item.to as string
    }))
  }])

  defineShortcuts({
    'g-o': () => router.push('/'),
    'g-s': () => router.push('/samples'),
    'g-i': () => router.push('/inspections'),
    'g-e': () => router.push('/equipment'),
    'g-r': () => router.push('/reports'),
    'g-t': () => router.push('/settings')
  })

  return {
    sidebarOpen,
    primaryLinks,
    supportLinks,
    commandGroups
  }
}

export const useWorkspace = createSharedComposable(_useWorkspace)
