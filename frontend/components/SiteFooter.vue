<template>
  <footer class="footer">
    <div class="fcols">
      <div>
        <div class="brand"><div class="logo"><i class="ti ti-device-mobile"></i></div><b>{{ settings.shop_name || 'محل الصيانة' }}</b></div>
        <p class="about">{{ settings.about || 'مركز صيانة جوالات معتمد — تصليح احترافي بقطع أصلية وضمان على كل خدمة.' }}</p>
      </div>
      <div>
        <h5>روابط</h5>
        <NuxtLink :to="base + '/book'">احجز تصليح</NuxtLink>
        <NuxtLink :to="base + '/track'">تتبّع طلبك</NuxtLink>
      </div>
      <div>
        <h5>تواصل</h5>
        <p v-if="settings.phone"><i class="ti ti-phone"></i> {{ settings.phone }}</p>
        <a v-if="waLink" :href="waLink" target="_blank"><i class="ti ti-brand-whatsapp"></i> واتساب</a>
        <p v-if="settings.address"><i class="ti ti-map-pin"></i> {{ settings.address }}</p>
        <p v-if="settings.hours"><i class="ti ti-clock"></i> {{ settings.hours }}</p>
      </div>
    </div>
    <div class="fbottom"><span>© 2026 {{ settings.shop_name || 'محل الصيانة' }} — كل الحقوق محفوظة</span></div>
  </footer>
</template>

<script setup>
const props = defineProps({ slug: { type: String, required: true } })
const { settings, loadSettings, waLink, base } = useShop(props.slug)
onMounted(loadSettings)
</script>

<style scoped>
.footer { background: #0E1526; color: #8A909C; padding: 40px 40px 22px; }
.fcols { display: grid; grid-template-columns: 2fr 1fr 1.4fr; gap: 26px; margin-bottom: 26px; }
.brand { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.logo { width: 36px; height: 36px; border-radius: 9px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 19px; }
.brand b { color: #fff; font-size: 17px; font-weight: 800; }
.about { font-size: 13.5px; max-width: 280px; line-height: 1.8; }
h5 { color: #fff; font-size: 14px; font-weight: 700; margin-bottom: 14px; }
.footer a, .footer p { display: flex; align-items: center; gap: 7px; font-size: 13.5px; color: #8A909C; margin-bottom: 9px; cursor: pointer; }
.footer a:hover { color: var(--accent); }
.fbottom { border-top: 1px solid var(--ink-3); padding-top: 18px; font-size: 12.5px; }
@media (max-width: 820px) { .fcols { grid-template-columns: 1fr; } }
</style>
