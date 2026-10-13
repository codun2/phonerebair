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
        <NuxtLink v-if="has('inventory')" to="/admin/inventory" :class="{ on: active === 'inventory' }"><i class="ti ti-packages"></i> {{ t('المخزون') }}</NuxtLink>
        <NuxtLink to="/admin/technicians" :class="{ on: active === 'technicians' }"><i class="ti ti-users"></i> {{ t('الفنيون') }}</NuxtLink>
        <NuxtLink to="/admin/bookings" :class="{ on: active === 'bookings' }">
          <i class="ti ti-calendar"></i> {{ t('الحجوزات') }}
          <span v-if="newBookings" class="nbadge">{{ newBookings }}</span>
        </NuxtLink>
        <div class="grp">{{ t('أخرى') }}</div>
        <NuxtLink to="/admin/prices" :class="{ on: active === 'prices' }"><i class="ti ti-tag"></i> {{ t('الأسعار والأعطال') }}</NuxtLink>
        <NuxtLink to="/admin/catalog" :class="{ on: active === 'catalog' }"><i class="ti ti-list-details"></i> {{ t('الماركات والموديلات') }}</NuxtLink>
        <NuxtLink to="/admin/settings" :class="{ on: active === 'settings' }"><i class="ti ti-settings"></i> {{ t('الإعدادات') }}</NuxtLink>
      </nav>
      <div class="side-foot">
        <div style="margin-bottom:10px"><LangSwitch /></div>
        <div class="su2"><div class="av2">{{ (user?.name || 'م').slice(0,2) }}</div><div><b>{{ user?.name || t('المدير') }}</b><small>{{ t('مدير عام') }}</small></div></div>
        <NuxtLink to="/" class="sf"><i class="ti ti-world"></i> {{ t('عرض الموقع') }}</NuxtLink>
        <button class="sf" @click="logout"><i class="ti ti-logout"></i> {{ t('خروج') }}</button>
      </div>
    </aside>
    <main class="dmain"><slot /></main>
    <ConfirmDialog />
  </div>
</template>

<script setup>
defineProps({ active: { type: String, default: 'overview' } })
const { user, logout } = useAuth()
const { t } = useI18n()
const { api } = useApi()
const { has, ensure } = useFeatures()

const newBookings = ref(0)
onMounted(async () => {
  await ensure()
  try { newBookings.value = (await api('/dashboard/stats')).new_bookings } catch {}
})
</script>

<style scoped>
.dash { display: grid; grid-template-columns: 245px minmax(0, 1fr); min-height: 100vh; }
.side { background: var(--ink); color: #C3C8D2; padding: 18px 0 12px; display: flex; flex-direction: column; }
.su { padding: 0 18px 16px; display: flex; align-items: center; gap: 11px; border-bottom: 1px solid var(--ink-3); margin-bottom: 8px; }
.av { width: 40px; height: 40px; border-radius: 10px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 21px; flex-shrink: 0; }
.su b { color: #fff; font-size: 14px; display: block; }
.su small { font-size: 11px; color: var(--muted); }
nav { display: flex; flex-direction: column; flex: 1; padding: 0 10px; }
.grp { font-size: 11px; color: var(--muted); font-weight: 600; padding: 12px 10px 5px; }
nav a { display: flex; align-items: center; gap: 11px; padding: 10px 12px; font-size: 14px; cursor: pointer; color: #C3C8D2; border-radius: 9px; margin-bottom: 1px; }
nav a:hover { background: var(--ink-2); color: #fff; }
nav a.on { background: var(--accent); color: #fff; }
.nbadge { background: var(--accent); color: #fff; font-size: 11px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; margin-inline-start: auto; }
nav a.on .nbadge { background: #fff; color: var(--accent); }
.side-foot { border-top: 1px solid var(--ink-3); padding: 10px 10px 0; margin-top: 8px; }
.su2 { display: flex; align-items: center; gap: 10px; padding: 8px 8px 12px; }
.av2 { width: 34px; height: 34px; border-radius: 50%; background: var(--ink-3); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
.su2 b { color: #fff; font-size: 13px; display: block; } .su2 small { font-size: 11px; color: var(--muted); }
.sf { display: flex; align-items: center; gap: 11px; padding: 10px 12px; font-size: 13.5px; color: #8A909C; background: none; border: none; cursor: pointer; width: 100%; font-family: inherit; border-radius: 9px; }
.sf:hover { color: #fff; background: var(--ink-2); }
.dmain { padding: 26px; background: var(--bg); }
@media (max-width: 820px) { .dash { grid-template-columns: 1fr; } .side { flex-direction: row; flex-wrap: wrap; padding: 12px; } nav { flex-direction: row; flex-wrap: wrap; } .grp { display: none; } .side-foot { border: none; } .su2 { display: none; } }
</style>
