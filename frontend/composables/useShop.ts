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

  const base = `/s/${slug}`

  return { settings, loadSettings, waLink, base, slug }
}
