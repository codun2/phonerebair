export function useAuth() {
  const { api, token } = useApi()
  const off = useOffline()
  const user = useState<any>('sr_user', () => null)

  async function fetchMe() {
    if (!token.value) return null
    try {
      const r = await api<{ user: any }>('/me')
      user.value = r.user
      off.setUser(r.user?.id)
      return r.user
    } catch (e: any) {
      // دون اتصال ولا نسخة محفوظة: لا نُخرج المستخدم، فقط ارفض الجلسة الفعلية المنتهية
      const st = e?.response?.status || e?.statusCode
      if (st === 401 || st === 403) {
        token.value = null
        user.value = null
      }
      return null
    }
  }

  async function login(email: string, password: string) {
    const r = await api<{ user: any; token: string }>('/login', {
      method: 'POST',
      body: { email, password },
    })
    off.clearApiCache(true)
    user.value = r.user
    token.value = r.token
    off.setUser(r.user?.id)
    return r
  }

  async function logout() {
    if (off.pending.value && !window.confirm(`لديك ${off.pending.value} عملية لم تُرفع بعد. ستبقى محفوظة على هذا الجهاز وتُرفع عند دخولك مجدداً. متابعة الخروج؟`)) return
    try { await api('/logout', { method: 'POST' }) } catch {}
    off.clearApiCache()
    token.value = null
    user.value = null
    await navigateTo('/login')
  }

  return { user, token, fetchMe, login, logout }
}
