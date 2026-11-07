// استعلام دوري خفيف لتحديث البيانات مباشرةً دون إعادة تحميل الصفحة.
// يتوقف تلقائياً عند إخفاء التبويب أو انقطاع الإنترنت (توفيراً للموارد)،
// ويحدّث فوراً عند عودة التبويب أو عودة الإنترنت. يُنظَّف عند مغادرة الصفحة.
// stop() يوقف كل شيء نهائياً (المؤقّت + المستمعات) — يُستدعى عند وصول الطلب
// لحالة نهائية فلا يستهلك ذاكرة الجهاز ولا موارد السيرفر بعدها.
export function usePoll(fn: () => any | Promise<any>, intervalMs = 5000) {
  if (!import.meta.client) return { stop() {}, resume() {}, tickNow: async () => {} }

  let timer: ReturnType<typeof setInterval> | null = null
  let attached = false
  let inFlight = false

  async function tick() {
    if (inFlight || document.hidden || !navigator.onLine) return
    inFlight = true
    try { await fn() } catch { /* تجاهل الأخطاء العابرة للشبكة */ } finally { inFlight = false }
  }
  const onVisibility = () => { if (!document.hidden) tick() }
  const onOnline = () => tick()

  function start() {
    if (!attached) {
      document.addEventListener('visibilitychange', onVisibility)
      window.addEventListener('online', onOnline)
      attached = true
    }
    if (!timer) timer = setInterval(tick, intervalMs)
  }
  function stop() {
    if (timer) { clearInterval(timer); timer = null }
    if (attached) {
      document.removeEventListener('visibilitychange', onVisibility)
      window.removeEventListener('online', onOnline)
      attached = false
    }
  }

  onMounted(start)
  onBeforeUnmount(stop)

  return { stop, resume: start, tickNow: tick }
}
