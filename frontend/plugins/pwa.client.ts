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
    const hadController = !!navigator.serviceWorker.controller
    let reloading = false
    // وصل إصدار جديد وتولّى التحكم → أعد التحميل مرة واحدة ليلتقط التطبيق المثبّت الكود الجديد
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (reloading || !hadController) return
      reloading = true
      window.location.reload()
    })
    navigator.serviceWorker.register('/sw.js', { scope: '/' }).then((reg) => {
      // افحص وجود تحديث عند فتح التطبيق وكل نصف ساعة
      reg.update().catch(() => {})
      setInterval(() => reg.update().catch(() => {}), 30 * 60 * 1000)
    }).catch(() => {})
  }
})
