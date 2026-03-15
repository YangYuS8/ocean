const formatCounts = (counts = {}) => {
  const entries = Object.entries(counts).filter(([, value]) => Number(value) > 0)

  if (entries.length === 0) {
    return ''
  }

  return entries.map(([label, value]) => `${label} x${value}`).join(', ')
}

export const buildSampleImageSuggestionView = ({ sample, suggestion }) => {
  if (!sample?.main_image) {
    return {
      state: 'missing-main-image',
      tone: 'warning',
      title: '先上传主图',
      description: '当前样本还没有主图，补齐主图后才能手动运行自动检测。',
      summary: '尚未提供检测输入图像。',
      meta: '',
      primaryAction: 'upload'
    }
  }

  if (!suggestion || suggestion.state === 'idle') {
    return {
      state: 'idle',
      tone: 'neutral',
      title: '自动检测建议',
      description: '当前主图还没有自动检测建议，可以手动运行一次 YOLO 检测。',
      summary: '尚未生成建议。',
      meta: '',
      primaryAction: 'run'
    }
  }

  const countsSummary = formatCounts(suggestion.suggestion?.counts)
  const topScore = suggestion.suggestion?.confidence_summary?.top_score
  const meta = typeof topScore === 'number' ? `最高置信度 ${topScore.toFixed(2)}` : ''
  const hasFindings = suggestion.suggestion?.has_findings !== false && countsSummary

  if (suggestion.state === 'refreshing') {
    return {
      state: 'refreshing',
      tone: 'primary',
      title: '正在重新检测',
      description: '系统正在刷新当前主图的自动检测建议，暂时保留上一次建议供人工参考。',
      summary: suggestion.summary || countsSummary || '正在刷新建议。',
      meta,
      primaryAction: 'busy'
    }
  }

  if (suggestion.state === 'running') {
    return {
      state: 'running',
      tone: 'primary',
      title: '自动检测进行中',
      description: '系统正在分析当前主图，请稍后刷新或等待任务完成。',
      summary: suggestion.summary || '正在生成建议。',
      meta,
      primaryAction: 'busy'
    }
  }

  if (suggestion.state === 'failed') {
    return {
      state: 'failed',
      tone: 'error',
      title: '自动检测失败',
      description: '本次自动检测未完成，可以稍后重新发起。',
      summary: suggestion.summary || '本次未生成可用建议。',
      meta: '',
      primaryAction: 'rerun'
    }
  }

  if (suggestion.state === 'stale') {
    return {
      state: 'stale',
      tone: 'warning',
      title: '建议已过期',
      description: '主图已更新，当前展示的是历史建议。若要继续参考，请重新运行自动检测。',
      summary: suggestion.summary || countsSummary || '存在历史建议，但已不对应当前主图。',
      meta,
      primaryAction: 'rerun'
    }
  }

  if (!hasFindings) {
    return {
      state: 'empty',
      tone: 'neutral',
      title: '未检测到明确目标',
      description: '模型已完成分析，但这次没有识别出满足阈值的明确目标，仅供人工参考。',
      summary: suggestion.summary || '未检测到明确目标。',
      meta,
      primaryAction: 'rerun'
    }
  }

  return {
    state: 'ready',
    tone: 'success',
    title: '自动检测建议',
    description: '建议基于当前主图生成，仅供人工参考，不会自动写入正式结果。',
    summary: suggestion.summary || countsSummary,
    meta,
    primaryAction: 'rerun'
  }
}
