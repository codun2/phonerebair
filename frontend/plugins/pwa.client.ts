// تسجيل Service Worker وتشغيل مزامنة صندوق الانتظار (العمل دون اتصال + تثبيت كتطبيق)
export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const off = useOffline()
  off.start({
    apiBase: config.public.apiBase,
    getToken: () => {
      const m = document.cookie.match(/(?:^|;\s*)sr_token=([^;]*)/)
      if (!m) return null
      const v = decodeURIComponent(m[1])
      return v && v !== 'null' ? v.replace(/^"|"$/g, '') : null
    },
  })
  const u = useState<any>('sr_user').value
  if (u?.id) off.setUser(u.id)

  if (!import.meta.dev && 'serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {})
  }
})
