// مفاتيح الميزات لمحل المستخدم المسجّل (لوحة التحكم)
export function useFeatures() {
  const { user, fetchMe } = useAuth()

  // يضمن تحميل المستخدم (مع المحل ومميزاته) بعد إعادة تحميل الصفحة
  async function ensure() {
    if (!user.value) await fetchMe()
  }

  const has = (key: string) => {
    const f = user.value?.shop?.features
    return Array.isArray(f) && f.includes(key)
  }

  return { has, ensure, user }
}
