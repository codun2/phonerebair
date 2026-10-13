// بيانات الموقع العام لمحل معيّن (settings/واتساب) مع تخزين مؤقت حسب الـslug
export function useShop(slug: string) {
  const { shopApi } = useApi()
  const settings = useState<Record<string, string>>(`shop_set_${slug}`, () => ({}))
  const loaded = useState<boolean>(`shop_loaded_${slug}`, () => false)

  async function loadSettings() {
    if (loaded.value) return settings.value
    try {
      settings.value = await shopApi(slug, '/settings')
      loaded.value = true
    } catch {}
    return settings.value
  }

  const waLink = computed(() => {
    const n = (settings.value.whatsapp || '').replace(/\D/g, '')
    return n ? `https://wa.me/${n}` : null
  })

  // تغميق لون hex بنسبة (لاشتقاق درجة أغمق للأزرار/الدعوات)
  function darken(hex: string, amt = 0.22): string {
    const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex || '')
    if (!m) return hex
    const f = (h: string) => Math.max(0, Math.round(parseInt(h, 16) * (1 - amt)))
    const h2 = (n: number) => n.toString(16).padStart(2, '0')
    return `#${h2(f(m[1]))}${h2(f(m[2]))}${h2(f(m[3]))}`
  }

  // متغيّرات CSS تُطبَّق على جذر الصفحة لتلوين كل عناصر المحل حسب لونه الأساسي
  const themeVars = computed<Record<string, string>>(() => {
    const p = settings.value.theme_primary
    return p ? { '--accent': p, '--accent-d': darken(p, 0.22) } : {}
  })

  // هل يظهر القسم؟ (افتراضيًا نعم، ما لم يُضبط '0')
  const show = (key: string) => settings.value['show_' + key] !== '0'

  const base = `/s/${slug}`

  return { settings, loadSettings, waLink, base, slug, themeVars, show }
}
