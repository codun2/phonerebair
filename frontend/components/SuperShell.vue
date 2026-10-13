<template>
  <div class="dash">
    <aside class="side">
      <div class="su">
        <div class="av"><i class="ti ti-building-store"></i></div>
        <div><b>{{ t('إدارة المنصّة') }}</b><small>Super Admin</small></div>
      </div>
      <nav>
        <div class="grp">{{ t('المنصّة') }}</div>
        <NuxtLink to="/super" :class="{ on: active === 'overview' }"><i class="ti ti-layout-dashboard"></i> {{ t('نظرة عامة') }}</NuxtLink>
        <NuxtLink to="/super" :class="{ on: active === 'shops' }"><i class="ti ti-building-store"></i> {{ t('المحلات') }}</NuxtLink>
        <NuxtLink to="/super/plans" :class="{ on: active === 'plans' }"><i class="ti ti-tag"></i> {{ t('الباقات') }}</NuxtLink>
      </nav>
      <div class="side-foot">
        <div style="margin-bottom:10px"><LangSwitch /></div>
        <div class="su2"><div class="av2">{{ (user?.name || 'مد').slice(0,2) }}</div><div><b>{{ user?.name || t('المدير العام') }}</b><small>{{ t('مدير المنصّة') }}</small></div></div>
        <button class="sf" @click="logout"><i class="ti ti-logout"></i> {{ t('خروج') }}</button>
      </div>
    </aside>
    <main class="main">
      <slot />
    </main>
  </div>
</template>

<script setup>
defineProps({ active: String })
const { user, fetchMe, logout } = useAuth()
const { t } = useI18n()

onMounted(async () => {
  const u = user.value || await fetchMe()
  if (!u) return navigateTo('/login')
  if (u.role !== 'super_admin') return navigateTo('/admin')
})
</script>

<style scoped>
.dash { display: flex; min-height: 100vh; background: var(--bg, #F4F6FA); }
.side { width: 250px; background: var(--ink); color: #cdd6e6; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; }
.su { display: flex; align-items: center; gap: 11px; padding: 18px 18px; border-bottom: 1px solid rgba(255,255,255,.08); }
.su .av { width: 42px; height: 42px; border-radius: 11px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.su b { color: #fff; font-size: 15px; display: block; } .su small { color: #8b98ad; font-size: 11px; }
nav { padding: 12px 10px; flex: 1; overflow-y: auto; }
.grp { font-size: 11px; color: #6b7893; margin: 14px 10px 6px; font-weight: 700; }
nav a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 9px; color: #cdd6e6; font-size: 14px; margin-bottom: 2px; }
nav a:hover { background: rgba(255,255,255,.06); }
nav a.on { background: var(--accent); color: #fff; }
.side-foot { padding: 12px; border-top: 1px solid rgba(255,255,255,.08); }
.su2 { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.av2 { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.1); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }
.su2 b { color: #fff; font-size: 13px; display: block; } .su2 small { color: #8b98ad; font-size: 11px; }
.sf { width: 100%; display: flex; align-items: center; gap: 8px; justify-content: center; padding: 9px; border-radius: 9px; background: rgba(255,255,255,.06); color: #cdd6e6; font-size: 13px; cursor: pointer; border: none; }
.sf:hover { background: rgba(255,255,255,.12); }
.main { flex: 1; padding: 26px 30px; overflow-x: auto; }
@media (max-width: 820px) { .dash { flex-direction: column; } .side { width: 100%; height: auto; position: static; flex-direction: row; flex-wrap: wrap; } nav { display: flex; gap: 4px; } .grp { display: none; } }
</style>
