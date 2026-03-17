const normalizeDateInput = (value) => {
  if (typeof value !== 'string') {
    return value
  }

  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/.test(value)) {
    return `${value.replace(' ', 'T')}Z`
  }

  return value
}

export const formatDateTimeInShanghai = (value) => {
  if (!value) {
    return '未设置'
  }

  const normalizedValue = normalizeDateInput(value)

  return new Intl.DateTimeFormat('zh-CN', {
    timeZone: 'Asia/Shanghai',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).format(new Date(normalizedValue))
}

export const buildImageSuggestionToast = ({ previousState, nextState, summary }) => {
  const wasBusy = previousState === 'running' || previousState === 'refreshing'

  if (!wasBusy) {
    return null
  }

  if (nextState === 'current' || nextState === 'empty') {
    return {
      title: '自动检测已完成',
      description: summary || '当前主图的自动检测建议已更新。',
      color: 'success',
      icon: 'i-lucide-badge-check'
    }
  }

  if (nextState === 'failed') {
    return {
      title: '自动检测失败',
      description: summary || '自动检测未完成，请稍后重试。',
      color: 'error',
      icon: 'i-lucide-circle-alert'
    }
  }

  return null
}

export const shouldPollImageSuggestion = (state) => state === 'running' || state === 'refreshing'
