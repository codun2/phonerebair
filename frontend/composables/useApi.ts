export function useApi() {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('sr_token', { maxAge: 60 * 60 * 24 * 30 })
  const off = useOffline()

  async function raw<T = any>(path: string, opts: any = {}): Promise<T> {
    return await $fetch<T>(config.public.apiBase + path, {
      ...opts,
      headers: {
        Accept: 'application/json',
        ...(token.value ? { Authorization: `Bearer ${token.value}` } : {}),
        ...(opts.headers || {}),
      },
      onResponse({ response }: any) {
        if (import.meta.client && (opts.method || 'GET').toUpperCase() === 'GET') {
          off.fromCache.value = response.headers.get('X-Pr-Cache') === 'fallback'
        }
      },
      onResponseError({ response }: any) {
        // جلسة منتهية: التوكن لم يعد صالحاً → تنظيف وإرجاع لتسجيل الدخول
        // (نتجاهلها دون اتصال لأن 401 قد تكون من الكاش، ونتجاهل نداء الدخول نفسه)
        if (import.meta.client && response?.status === 401 && token.value
            && navigator.onLine && !path.startsWith('/login')) {
          token.value = null
          off.clearApiCache()
          if (!useRoute().path.startsWith('/login')) {
            navigateTo('/login?expired=1', { replace: true })
          }
        }
      },
    })
  }

  // كل نداءات اللوحة تمرّ من هنا: القراءة تُدمج مع العمليات المعلّقة،
  // والكتابة الآمنة تُحفظ في صندوق الانتظار إذا انقطع الاتصال.
  async function api<T = any>(path: string, opts: any = {}): Promise<T> {
    const method = (opts.method || 'GET').toUpperCase()
    if (!import.meta.client) return raw<T>(path, opts)

    await off.ready()
    if (method === 'GET') {
      const local = off.localRecord(path)
      if (local) return local as T
      return off.overlay(path, await raw<T>(path, opts)) as T
    }

    const rule = off.ruleFor(method, path)
    if (rule && !navigator.onLine) return (await off.enqueue(method, path, opts.body, opts.offline)) as T
    // يوجد عمليات أقدم بالانتظار → هذه تصطف خلفها حتى يبقى الترتيب صحيحاً
    if (rule && off.pending.value) {
      const r = await off.enqueue(method, path, opts.body, opts.offline)
      off.flush()
      return r as T
    }
    // تعديل سجل مؤقت لم يُرفع بعد → يدخل الصندوق خلف عملية إنشائه
    if (rule && /tmp_/.test(path)) return (await off.enqueue(method, path, opts.body, opts.offline)) as T
    try {
      const { offline, ...rest } = opts
      return await raw<T>(path, rest)
    } catch (e: any) {
      if (rule && off.isNetworkError(e)) return (await off.enqueue(method, path, opts.body, opts.offline)) as T
      throw e
    }
  }

  // نداء الموقع العام لمحل معيّن: /api/shops/{slug}/...
  function shopApi<T = any>(slug: string, path: string, opts: any = {}): Promise<T> {
    return raw<T>(`/shops/${slug}${path}`, opts)
  }

  const fileUrl = (p: string) => config.public.apiBase.replace('/api', '') + '/storage/' + p

  return { api, shopApi, token, fileUrl }
}
