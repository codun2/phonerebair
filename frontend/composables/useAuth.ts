export function useAuth() {
  const { api, token } = useApi()
  const user = useState<any>('sr_user', () => null)

  async function fetchMe() {
    if (!token.value) return null
    try {
      const r = await api<{ user: any }>('/me')
      user.value = r.user
      return r.user
    } catch {
      token.value = null
      user.value = null
      return null
    }
  }

  async function login(email: string, password: string) {
    const r = await api<{ user: any; token: string }>('/login', {
      method: 'POST',
      body: { email, password },
    })
    user.value = r.user
    token.value = r.token
    return r
  }

  async function logout() {
    try { await api('/logout', { method: 'POST' }) } catch {}
    token.value = null
    user.value = null
    await navigateTo('/login')
  }

  return { user, token, fetchMe, login, logout }
}
