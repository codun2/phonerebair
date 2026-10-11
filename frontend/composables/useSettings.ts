export function useSettings() {
  const { api } = useApi()
  const settings = useState<Record<string, string>>('sr_settings', () => ({}))

  async function loadSettings() {
    if (Object.keys(settings.value).length) return settings.value
    try { settings.value = await api('/site/settings') } catch {}
    return settings.value
  }

  const waLink = computed(() => {
    const n = (settings.value.whatsapp || '').replace(/\D/g, '')
    return n ? `https://wa.me/${n}` : null
  })

  return { settings, loadSettings, waLink }
}
