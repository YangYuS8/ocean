type ApiErrorPayload = {
  error?: {
    code?: string
    message?: string
  }
  message?: string
}

export const useApiClient = () => {
  const config = useRuntimeConfig()
  const baseURL = import.meta.server ? (config.public.apiBase || undefined) : undefined

  const request = <T>(path: string, options?: Parameters<typeof $fetch<T>>[1]) => {
    return $fetch<T>(path, {
      baseURL,
      ...options
    })
  }

  const getErrorMessage = (error: unknown, fallback = '请求失败，请稍后重试。') => {
    const payload = (error as { data?: ApiErrorPayload })?.data

    return payload?.error?.message || payload?.message || (error as Error | undefined)?.message || fallback
  }

  return {
    baseURL,
    request,
    getErrorMessage
  }
}
