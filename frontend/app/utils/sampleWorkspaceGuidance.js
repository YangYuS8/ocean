/**
 * @typedef {{ status: string }} SampleLike
 * @typedef {{ status: string, title?: string|null }} ExceptionLike
 * @typedef {{ status: string, job_type?: string|null }} AnalysisJobLike
 * @typedef {{ id?: number|string }} ResultLike
 * @typedef {{ kind: string, title: string, description: string }} RiskItem
 * @typedef {{ action: string, title: string, description: string }} NextStep
 * @typedef {{ summaryTone: string, summaryTitle: string, summaryDescription: string, stats: Array<{label: string, value: string}>, risks: RiskItem[], nextStep: NextStep }} WorkspaceGuidance
 */

/**
 * @param {{ sample: SampleLike|null, results: ResultLike[], exceptions: ExceptionLike[], analysisJobs: AnalysisJobLike[] }} input
 * @returns {WorkspaceGuidance}
 */
export function buildSampleWorkspaceGuidance(input) {
  const resultsCount = input.results.length
  const openExceptions = input.exceptions.filter((item) => item.status === 'open')
  const failedJobs = input.analysisJobs.filter((item) => item.status === 'failed')
  const activeJobs = input.analysisJobs.filter((item) => item.status === 'queued' || item.status === 'running')

  /** @type {RiskItem[]} */
  const risks = []

  if (openExceptions.length > 0) {
    risks.push({
      kind: 'exception',
      title: `有 ${openExceptions.length} 条未解决异常`,
      description: '当前样本仍存在待处理问题，建议优先确认是否需要先补充说明或解决异常。'
    })
  }

  if (failedJobs.length > 0) {
    risks.push({
      kind: 'analysis-failed',
      title: `有 ${failedJobs.length} 个失败分析任务`,
      description: '分析过程曾中断或失败，可重新发起分析，或根据失败原因转入异常处理。'
    })
  }

  if (activeJobs.length > 0) {
    risks.push({
      kind: 'analysis-active',
      title: `有 ${activeJobs.length} 个分析任务进行中`,
      description: '当前样本仍有待执行或执行中的分析任务，建议结合其状态决定是否继续等待。'
    })
  }

  /** @type {NextStep} */
  let nextStep
  if (failedJobs.length > 0) {
    nextStep = {
      action: 'retry-analysis',
      title: '优先重新发起分析',
      description: '当前存在失败的分析任务，建议先补跑或结合失败原因记录异常。'
    }
  }
  else if (resultsCount === 0) {
    nextStep = {
      action: 'result',
      title: '优先录入结果',
      description: '当前样本还没有结果记录，建议先补充结果，帮助后续判断异常与分析结论。'
    }
  }
  else if (openExceptions.length > 0) {
    nextStep = {
      action: 'exception',
      title: '优先处理未解决异常',
      description: '当前样本已有结果，但仍存在开放中的异常，建议先确认风险是否已消除。'
    }
  }
  else if (activeJobs.length > 0) {
    nextStep = {
      action: 'wait',
      title: '等待当前分析完成',
      description: '当前样本已有分析在进行中，可稍后回来查看摘要并继续处理。'
    }
  }
  else {
    nextStep = {
      action: 'observe',
      title: '当前样本可以继续观察或补充信息',
      description: '当前没有明显阻塞，可根据现场情况补充结果、异常说明或新的分析任务。'
    }
  }

  const summaryTone = failedJobs.length > 0 || openExceptions.length > 0
    ? 'warning'
    : activeJobs.length > 0
      ? 'primary'
      : 'success'

  const sampleStatus = input.sample?.status || 'unknown'

  return {
    summaryTone,
    summaryTitle: `当前样本处于 ${sampleStatus} 阶段`,
    summaryDescription: '工作台摘要会根据结果、异常和分析任务的组合状态，提示当前风险与推荐下一步。',
    stats: [
      { label: '结果记录', value: String(resultsCount) },
      { label: '未解决异常', value: String(openExceptions.length) },
      { label: '失败分析', value: String(failedJobs.length) },
      { label: '进行中分析', value: String(activeJobs.length) }
    ],
    risks,
    nextStep
  }
}
