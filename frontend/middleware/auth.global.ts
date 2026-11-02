// حارس الدخول: صفحات اللوحة (/admin, /super) تتطلب توكناً.
// بدون توكن → تسجيل الدخول (يمنع عرض صفحات مخبّأة بلا جلسة، فتفشل كل النداءات بصمت).
// وجود التوكن كافٍ هنا؛ صلاحيته الفعلية يتحقق منها السيرفر، وأي 401 لاحق يعيد للدخول.
export default defineNuxtRouteMiddleware((to) => {
  if (!import.meta.client) return
  const guarded = to.path.startsWith('/admin') || to.path.startsWith('/super')
  if (!guarded) return

  const token = useCookie<string | null>('sr_token')
  if (!token.value) {
    return navigateTo('/login?expired=1', { replace: true })
  }
})
