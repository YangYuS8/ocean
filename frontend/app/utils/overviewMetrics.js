export const buildOverviewMetrics = (summary) => [
  {
    title: '待处理样本',
    value: summary ? String(summary.pending_samples) : '--',
    description: '等待入库、检测或复核的样本总量',
    icon: 'i-lucide-flask-conical'
  },
  {
    title: '今日巡检任务',
    value: summary ? String(summary.today_inspection_tasks) : '--',
    description: '包含船载设备、实验设备与环境监测点位',
    icon: 'i-lucide-clipboard-check'
  },
  {
    title: '异常告警',
    value: summary ? String(summary.open_exceptions) : '--',
    description: '存在需人工确认的高优先级异常项',
    icon: 'i-lucide-bell-ring'
  },
  {
    title: '分析队列',
    value: summary ? String(summary.queued_analysis_jobs) : '--',
    description: '等待 Python 模块处理的图像与统计任务',
    icon: 'i-lucide-cpu'
  }
]
