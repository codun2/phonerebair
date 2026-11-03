<template>
  <div class="dash">
    <aside class="side">
      <div class="su">
        <div class="av"><i class="ti ti-device-mobile"></i></div>
        <div><b>{{ user?.shop?.name || 'موبايل فيكس' }}</b><small>{{ t('لوحة التحكم') }}</small></div>
      </div>
      <nav>
        <div class="grp">{{ t('الرئيسية') }}</div>
        <NuxtLink to="/admin" :class="{ on: active === 'overview' }"><i class="ti ti-layout-dashboard"></i> {{ t('لوحة المعلومات') }}</NuxtLink>
        <NuxtLink to="/admin/tickets" :class="{ on: active === 'tickets' }"><i class="ti ti-ticket"></i> {{ t('التذاكر') }}</NuxtLink>
        <NuxtLink to="/admin/ticket-new" :class="{ on: active === 'ticket-new' }"><i class="ti ti-plus"></i> {{ t('تذكرة جديدة') }}</NuxtLink>
        <div class="grp">{{ t('الإدارة') }}</div>
        <NuxtLink to="/admin/customers" :class="{ on: active === 'customers' }"><i class="ti ti-address-book"></i> {{ t('العملاء') }}</NuxtLink>
        <NuxtLink to="/admin/finance" :class="{ on: active === 'finance' }"><i class="ti ti-cash-banknote"></i> {{ t('المالية') }}</NuxtLink>
        <NuxtLink v-if="has('inventory')" to="/admin/inventory" :class="{ on: active === 'inventory' }"><i class="ti ti-packages"></i> {{ t('المخزون') }}</NuxtLink>
        <NuxtLink v-if="has('inventory')" to="/admin/suppliers" :class="{ on: active === 'suppliers' }"><i class="ti ti-truck"></i> {{ t('الموردون') }}</NuxtLink>
        <NuxtLink to="/admin/technicians" :class="{ on: active === 'technicians' }"><i class="ti ti-users"></i> {{ t('الفنيون') }}</NuxtLink>
        <NuxtLink to="/admin/bookings" :class="{ on: active === 'bookings' }">
          <i class="ti ti-calendar"></i> {{ t('الحجوزات') }}
          <span v-if="newBookings" class="nbadge">{{ newBookings }}</span>
        </NuxtLink>
        <div class="grp">{{ t('أخرى') }}</div>
        <NuxtLink to="/admin/prices" :class="{ on: active === 'prices' }"><i class="ti ti-tag"></i> {{ t('الأسعار والأعطال') }}</NuxtLink>
        <NuxtLink to="/admin/catalog" :class="{ on: active === 'catalog' }"><i class="ti ti-list-details"></i> {{ t('الماركات والموديلات') }}</NuxtLink>
        <NuxtLink to="/admin/settings" :class="{ on: active === 'settings' }"><i class="ti ti-settings"></i> {{ t('الإعدادات') }}</NuxtLink>
        <NuxtLink to="/admin/sync" :class="{ on: active === 'sync' }">
          <i class="ti ti-cloud-computing"></i> {{ t('المزامنة ودون اتصال') }}
          <span v-if="pending + failed" class="nbadge" :class="{ warn: failed }">{{ pending + failed }}</span>
        </NuxtLink>
      </nav>
    </aside>
    <main ref="dmain" class="dmain">
      <!-- شريط علوي: المدير والأدوات (نُقلت من الشريط الجانبي) -->
      <header class="topbar">
        <div class="tb-user">
          <div class="av2">{{ (user?.name || 'م').slice(0,2) }}</div>
          <div><b>{{ user?.name || t('المدير') }}</b><small>{{ t('مدير عام') }}</small></div>
        </div>
        <div class="tb-actions">
          <button v-if="canInstall" class="tb-btn accent" @click="install"><i class="ti ti-download"></i> {{ t('تثبيت التطبيق على الجهاز') }}</button>
          <LangSwitch />
          <a v-if="user?.shop?.slug" :href="'/s/' + user.shop.slug" target="_blank" class="tb-btn"><i class="ti ti-world"></i> {{ t('عرض الموقع') }}</a>
          <button class="tb-btn" @click="logout"><i class="ti ti-logout"></i> {{ t('خروج') }}</button>
        </div>
      </header>

      <div class="content">
        <div v-if="!online" class="netbar off">
          <i class="ti ti-wifi-off"></i>
          <span>{{ t('أنت تعمل دون اتصال — التغييرات تُحفظ على الجهاز وتُرفع تلقائياً عند عودة الإنترنت') }}</span>
          <NuxtLink v-if="pending" to="/admin/sync" class="nb-l">{{ pending }} {{ t('بالانتظار') }}</NuxtLink>
        </div>
        <div v-else-if="syncing" class="netbar sync"><i class="ti ti-refresh"></i> {{ t('جاري رفع التغييرات المحفوظة...') }}</div>
        <div v-else-if="failed" class="netbar bad">
          <i class="ti ti-alert-triangle"></i> {{ failed }} {{ t('عملية رفضها السيرفر') }}
          <NuxtLink to="/admin/sync" class="nb-l">{{ t('مراجعة') }}</NuxtLink>
        </div>
        <div v-else-if="fromCache" class="netbar off"><i class="ti ti-database"></i> {{ t('تعرض آخر بيانات محفوظة — السيرفر لا يستجيب حالياً') }}</div>
        <slot />
      </div>
    </main>
    <ConfirmDialog />
  </div>
</template>

<script setup>
defineProps({ active: { type: String, default: 'overview' } })
const { user, logout } = useAuth()
const { t } = useI18n()
const { api } = useApi()
const { has, ensure } = useFeatures()
const { online, pending, failed, syncing, fromCache, canInstall, install, warmUp, flush } = useOffline()
const router = useRouter()
const route = useRoute()

// عند التنقّل بين الصفحات نُرجع حاوية المحتوى لأعلى حتى تبدأ كل صفحة من رأسها
const dmain = ref(null)
watch(() => route.path, () => { dmain.value?.scrollTo({ top: 0 }) })

const newBookings = ref(0)
onMounted(async () => {
  await ensure()
  try { newBookings.value = (await api('/dashboard/stats')).new_bookings } catch {}
  flush()
  // تحميل مسبق للبيانات والصفحات حتى تفتح اللوحة كاملة دون اتصال
  setTimeout(() => warmUp(api, router, user.value?.shop?.features || []), 1500)
})
</script>

<style scoped>
/* الشاشة لا تتمرّر؛ المحتوى فقط هو الذي يتمرّر داخلياً */
.dash { display: grid; grid-template-columns: 245px minmax(0, 1fr); height: 100vh; overflow: hidden; }
/* الشريط الجانبي ثابت بالكامل: الشعار أعلى، القائمة تتمرّر داخلياً، المدير واللغة ثابتان أسفله */
.side { background: var(--ink); color: #C3C8D2; padding: 18px 0 12px; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
.su { padding: 0 18px 16px; display: flex; align-items: center; gap: 11px; border-bottom: 1px solid var(--ink-3); margin-bottom: 8px; flex-shrink: 0; }
.av { width: 40px; height: 40px; border-radius: 10px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 21px; flex-shrink: 0; }
.su b { color: #fff; font-size: 14px; display: block; }
.su small { font-size: 11px; color: var(--muted); }
nav { display: flex; flex-direction: column; flex: 1; padding: 0 10px; overflow-y: auto; overflow-x: hidden; min-height: 0; }
nav::-webkit-scrollbar { width: 5px; } nav::-webkit-scrollbar-thumb { background: var(--ink-3); border-radius: 4px; }
.grp { font-size: 11px; color: var(--muted); font-weight: 600; padding: 12px 10px 5px; }
nav a { display: flex; align-items: center; gap: 11px; padding: 10px 12px; font-size: 14px; cursor: pointer; color: #C3C8D2; border-radius: 9px; margin-bottom: 1px; }
nav a:hover { background: var(--ink-2); color: #fff; }
nav a.on { background: var(--accent); color: #fff; }
.nbadge { background: var(--accent); color: #fff; font-size: 11px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; margin-inline-start: auto; }
nav a.on .nbadge { background: #fff; color: var(--accent); }
.dmain { background: var(--bg); height: 100vh; overflow-y: auto; }
/* شريط علوي ثابت فوق المحتوى */
.topbar { position: sticky; top: 0; z-index: 20; background: #fff; border-bottom: 1px solid var(--line); padding: 11px 26px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.tb-user { display: flex; align-items: center; gap: 10px; }
.av2 { width: 38px; height: 38px; border-radius: 50%; background: #E6F1FB; color: var(--accent-d); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
.tb-user b { color: var(--ink); font-size: 14px; display: block; line-height: 1.2; } .tb-user small { font-size: 11.5px; color: var(--muted); }
.tb-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding-inline-start: 14px; border-inline-start: 1px solid var(--line); }
.tb-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 13px; font-size: 13px; color: var(--ink); background: #fff; border: 1px solid var(--line); border-radius: 9px; cursor: pointer; font-family: inherit; text-decoration: none; }
.tb-btn:hover { background: var(--bg); border-color: #C7CCD6; }
.tb-btn.accent { background: var(--accent); color: #fff; border-color: var(--accent); }
.tb-btn.accent:hover { background: var(--accent-d); }
.content { padding: 26px; }
.netbar { display: flex; align-items: center; gap: 9px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 16px; flex-wrap: wrap; }
.netbar i { font-size: 18px; }
.netbar.off { background: #FFF4E0; color: #8A5300; border: 1px solid #F5D9A8; }
.netbar.sync { background: #E6F1FB; color: var(--accent-d); border: 1px solid #C9DEF4; }
.netbar.bad { background: #FDECEC; color: #A32020; border: 1px solid #F3C4C4; }
.nb-l { margin-inline-start: auto; text-decoration: underline; color: inherit; }
.nbadge.warn { background: #D33; }
.inst { color: #fff !important; background: var(--accent) !important; margin-bottom: 10px; justify-content: center; }
@media (max-width: 820px) { .dash { grid-template-columns: 1fr; height: auto; overflow: visible; } .side { position: static; height: auto; overflow: visible; flex-direction: row; flex-wrap: wrap; padding: 12px; } nav { flex-direction: row; flex-wrap: wrap; overflow: visible; } .dmain { height: auto; overflow: visible; } .grp { display: none; } .topbar { padding: 10px 16px; } .content { padding: 16px; } .tb-actions { padding-inline-start: 0; border: none; } }
</style>
