const statusLabelMap = {
  queued: '待执行',
  running: '进行中',
  succeeded: '已完成',
  failed: '失败',
  cancelled: '已取消'
}

const actionMap = {
  retry: '重新检测',
  result: '新增结果',
  exception: '处理异常',
  wait: '等待结果',
  observe: '查看详情'
}

export const buildSampleListItemPreview = ({ sample, results, exceptions, analysisJobs }) => {
  const openExceptions = exceptions.filter((item) => item.status === 'open')
  const failedJobs = analysisJobs.filter((item) => item.status === 'failed')
  const activeJobs = analysisJobs.filter((item) => item.status === 'queued' || item.status === 'running')

  if (failedJobs.length > 0) {
    return {
      tone: 'warning',
      summary: `最近有 ${failedJobs.length} 次自动检测失败`,
      nextAction: actionMap.retry,
      chips: ['自动检测失败']
    }
  }

  if (openExceptions.length > 0) {
    return {
      tone: 'warning',
      summary: `存在未解决异常（${openExceptions.length}）`,
      nextAction: actionMap.exception,
      chips: ['异常待处理']
    }
  }

  if (results.length === 0 && sample?.status !== 'completed') {
    return {
      tone: 'primary',
      summary: '等待录入结果',
      nextAction: actionMap.result,
      chips: activeJobs.length > 0 ? ['检测进行中'] : ['待录结果']
    }
  }

  if (activeJobs.length > 0) {
    return {
      tone: 'primary',
      summary: `当前有 ${activeJobs.length} 个自动检测任务进行中`,
      nextAction: actionMap.wait,
      chips: ['检测进行中']
    }
  }

  return {
    tone: 'success',
    summary: '当前样本可继续查看详情或补充信息',
    nextAction: actionMap.observe,
    chips: [sample?.status || 'unknown']
  }
}

export const buildSampleHistoryPreview = (analysisJobs) => {
  const allItems = analysisJobs.map((job) => ({
    id: job.id,
    status: job.status,
    statusLabel: statusLabelMap[job.status] || job.status,
    summary: job.result_summary || job.error_message || '暂无摘要',
    time: job.finished_at || job.queued_at || null
  }))

  return {
    totalCount: analysisJobs.length,
    allItems,
    items: allItems.slice(0, 3)
  }
}
