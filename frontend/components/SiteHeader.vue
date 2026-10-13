<template>
  <div class="nav">
    <NuxtLink :to="base" class="brand">
      <div class="logo"><i class="ti ti-device-mobile"></i></div>
      <div><div class="bt">{{ settings.shop_name || 'محل الصيانة' }}</div><div class="bs">MOBILE FIX</div></div>
    </NuxtLink>
    <div class="links">
      <NuxtLink :to="base">{{ t('الرئيسية') }}</NuxtLink>
      <NuxtLink :to="base + '/book'">{{ t('احجز تصليح') }}</NuxtLink>
      <NuxtLink :to="base + '/track'">{{ t('تتبّع طلبك') }}</NuxtLink>
    </div>
    <div class="right">
      <span v-if="settings.phone" class="phone"><i class="ti ti-phone"></i> {{ settings.phone }}</span>
      <LangSwitch v-if="hasFeature('bilingual')" light />
      <NuxtLink to="/login" class="btn btn-accent"><i class="ti ti-lock"></i> {{ t('دخول لوحة التحكم') }}</NuxtLink>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ slug: { type: String, required: true } })
const { settings, loadSettings, base, hasFeature } = useShop(props.slug)
const { t } = useI18n()
onMounted(loadSettings)
</script>

<style scoped>
.nav { background: var(--ink); color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 15px 28px; }
.brand { display: flex; align-items: center; gap: 11px; cursor: pointer; }
.logo { width: 40px; height: 40px; border-radius: 10px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.bt { font-size: 19px; font-weight: 800; }
.bs { font-size: 10px; color: var(--muted); letter-spacing: 3px; }
.links { display: flex; gap: 24px; font-size: 14.5px; color: #C3C8D2; }
.links a:hover { color: #fff; }
.right { display: flex; align-items: center; gap: 14px; }
.phone { color: #C3C8D2; font-size: 14px; display: flex; align-items: center; gap: 6px; }
@media (max-width: 820px) { .links, .phone { display: none; } }
</style>
