<template>
  <DashShell active="overview">
    <div class="page-title">لوحة المعلومات</div>
    <p class="page-sub">ملخّص اليوم</p>

    <div class="kpis">
      <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent)"><i class="ti ti-ticket"></i></div><div class="num">{{ s.open_tickets ?? '—' }}</div><div class="lbl">تذاكر مفتوحة</div></div>
      <div class="kpi"><div class="ic" style="background:#E1F5EE;color:var(--green)"><i class="ti ti-cash"></i></div><div class="num">{{ fmt(s.today_revenue) }}</div><div class="lbl">إيراد اليوم (ر.س)</div></div>
      <div class="kpi"><div class="ic" style="background:#EEEDFE;color:#534AB7"><i class="ti ti-tool"></i></div><div class="num">{{ s.in_repair ?? '—' }}</div><div class="lbl">قيد التصليح الآن</div></div>
      <div class="kpi"><div class="ic" style="background:#FBE9E8;color:var(--accent-d)"><i class="ti ti-alert-triangle"></i></div><div class="num">{{ s.low_stock ?? '—' }}</div><div class="lbl">قطع قاربت النفاد</div></div>
    </div>

    <div class="cols">
      <div class="panel">
        <div class="ph"><h3>أحدث التذاكر</h3><NuxtLink to="/admin/tickets" class="link">عرض الكل ←</NuxtLink></div>
        <table>
          <thead><tr><th>التذكرة</th><th>الزبون</th><th>الجهاز</th><th>الحالة</th><th>الفني</th></tr></thead>
          <tbody>
            <tr v-for="t in s.recent || []" :key="t.id" @click="go(t.id)" style="cursor:pointer">
              <td><b style="color:var(--accent-d)">{{ t.ticket_no }}</b></td>
              <td>{{ t.customer_name }}</td>
              <td>{{ t.device }} <span class="muted">· {{ t.repair_type }}</span></td>
              <td><StatusPill :status="t.status" :label="t.status_label" /></td>
              <td>{{ t.technician || '—' }}</td>
            </tr>
            <tr v-if="!(s.recent||[]).length"><td colspan="5" class="empty">لا توجد تذاكر</td></tr>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="ph"><h3>أداء الفنيين اليوم</h3></div>
        <div v-for="t in s.tech_perf || []" :key="t.id" class="trow">
          <div class="tav">{{ t.name.slice(0,2) }}</div>
          <div style="flex:1"><b>{{ t.name }}</b><div class="muted">{{ t.done_today }} تذاكر مكتملة</div></div>
          <span class="pill" :class="t.active ? 'pill-g' : 'pill-gray'">{{ t.active ? 'نشط' : 'متوقف' }}</span>
        </div>
        <div v-if="!(s.tech_perf||[]).length" class="empty">لا يوجد فنيون</div>
        <NuxtLink to="/admin/technicians" class="link" style="display:block;text-align:center;margin-top:12px">إدارة الفنيين ←</NuxtLink>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const s = ref({})
const fmt = (n) => Number(n || 0).toLocaleString('ar-EG')
const go = (id) => navigateTo('/admin/tickets?open=' + id)
onMounted(async () => { try { s.value = await api('/dashboard/stats') } catch {} })
</script>

<style scoped>
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
.num { font-size: 28px; font-weight: 800; }
.lbl { color: var(--muted); font-size: 13px; }
.cols { display: grid; grid-template-columns: 1.7fr 1fr; gap: 16px; }
.ph { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.ph h3 { font-size: 15px; font-weight: 700; }
table { border: none; } th, td { border-bottom: 1px solid var(--line-2); }
.muted { color: var(--muted); font-size: 12px; }
.trow { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--line-2); }
.tav { width: 40px; height: 40px; border-radius: 50%; background: #E6F1FB; color: var(--accent-d); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
@media (max-width: 900px) { .kpis { grid-template-columns: 1fr 1fr; } .cols { grid-template-columns: 1fr; } }
</style>
