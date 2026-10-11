export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('sr_token', { maxAge: 60 * 60 * 24 * 30 })

  async function api<T = any>(path: string, opts: any = {}): Promise<T> {
    return await $fetch<T>(config.public.apiBase + path, {
      ...opts,
      headers: {
        Accept: 'application/json',
        ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
        ...(opts.headers || {}),
      },
    })
  }

  const fileUrl = (p: string) => config.public.apiBase.replace('/api', '') + '/storage/' + p

  return { api, token, fileUrl }
}
