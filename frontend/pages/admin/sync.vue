<template>
  <DashShell active="sync">
    <div class="ph">
      <div><div class="page-title">{{ t('المزامنة ودون اتصال') }}</div><p class="page-sub">{{ t('العمليات المحفوظة على هذا الجهاز وحالة الرفع للسيرفر') }}</p></div>
      <button class="btn btn-accent" :disabled="!online || syncing || !pending" @click="flush"><i class="ti ti-refresh"></i> {{ t('مزامنة الآن') }}</button>
    </div>

    <div class="kpis">
      <div class="kpi"><div class="ic" :style="{ background: online ? '#E3F6EC' : '#FFF4E0', color: online ? '#137A4B' : '#8A5300' }"><i class="ti" :class="online ? 'ti-wifi' : 'ti-wifi-off'"></i></div>
        <div class="num sm">{{ online ? t('متصل') : t('غير متصل') }}</div><div class="lbl">{{ t('حالة الاتصال') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#E6F1FB;color:var(--accent-d)"><i class="ti ti-clock"></i></div>
        <div class="num">{{ pending }}</div><div class="lbl">{{ t('بانتظار الرفع') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#FDECEC;color:#A32020"><i class="ti ti-alert-triangle"></i></div>
        <div class="num">{{ failed }}</div><div class="lbl">{{ t('رفضها السيرفر') }}</div></div>
      <div class="kpi"><div class="ic" style="background:#F1F2F5;color:var(--muted)"><i class="ti ti-history"></i></div>
        <div class="num sm" dir="ltr">{{ lastSync ? lastSync.slice(0, 16).replace('T', ' ') : '—' }}</div><div class="lbl">{{ t('آخر مزامنة') }}</div></div>
    </div>

    <div class="panel">
      <div class="sec-t"><i class="ti ti-inbox" style="color:var(--accent)"></i> {{ t('صندوق الانتظار') }}</div>
      <table>
        <thead><tr><th>{{ t('العملية') }}</th><th>{{ t('التفاصيل') }}</th><th>{{ t('الوقت') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
        <tbody>
          <tr v-for="o in ops" :key="o.id">
            <td><b>{{ t(o.label) }}</b></td>
            <td class="muted">{{ describe(o) }}</td>
            <td class="muted" dir="ltr">{{ o.created_at.slice(0, 16).replace('T', ' ') }}</td>
            <td>
              <span v-if="o.status === 'pending'" class="pill pill-gray">{{ t('بالانتظار') }}</span>
              <span v-else class="pill" style="background:#FDECEC;color:#A32020">{{ t('مرفوضة') }}</span>
              <div v-if="o.error" class="muted" style="margin-top:4px;color:#A32020">{{ o.error }}</div>
            </td>
            <td style="white-space:nowrap">
              <button v-if="o.status === 'failed'" class="act" :title="t('إعادة المحاولة')" @click="retry(o.id)"><i class="ti ti-refresh"></i></button>
              <button class="act" :title="t('تجاهل')" @click="drop(o)"><i class="ti ti-trash"></i></button>
            </td>
          </tr>
          <tr v-if="!ops.length"><td colspan="5" class="empty">{{ t('لا توجد عمليات معلّقة — كل شيء مرفوع للسيرفر') }}</td></tr>
        </tbody>
      </table>
    </div>

    <div class="panel" style="margin-top:16px">
      <div class="sec-t"><i class="ti ti-device-desktop" style="color:var(--accent)"></i> {{ t('تطبيق على الجهاز') }}</div>
      <p class="hint-p">{{ t('ثبّت لوحة التحكم كتطبيق على الكمبيوتر أو الجوال: تفتح بنافذة مستقلة، وتعمل دون إنترنت بآخر بيانات محفوظة. التذاكر الجديدة وتغيير الحالات والمخزون والفنيين تُحفظ على الجهاز وتُرفع تلقائياً عند عودة الاتصال.') }}</p>
      <button v-if="canInstall" class="btn btn-accent" @click="install"><i class="ti ti-download"></i> {{ t('تثبيت التطبيق على الجهاز') }}</button>
      <p v-else class="hint-p muted">{{ installed ? t('التطبيق مثبّت ويعمل الآن كنافذة مستقلة.') : t('إن لم يظهر زر التثبيت: من متصفح Chrome أو Edge افتح القائمة ⋮ واختر «تثبيت التطبيق».') }}</p>
    </div>
  </DashShell>
</template>

<script setup>
const { t } = useI18n()
const { confirmDlg } = useConfirm()
const { online, ops, pending, failed, syncing, lastSync, canInstall, install, flush, retry, discard, reload } = useOffline()
const installed = ref(false)

function describe(o) {
  const b = o.body || {}
  return b.customer_name || b.name || (b.status ? t('الحالة') + ': ' + b.status : '') || o.path
}
async function drop(o) {
  if (!await confirmDlg(t('تجاهل العملية'), t('سيُحذف هذا التغيير من الجهاز ولن يُرفع للسيرفر'))) return
  discard(o.id)
}
onMounted(() => {
  reload()
  installed.value = window.matchMedia?.('(display-mode: standalone)').matches
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.kpi { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
.ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
.num { font-size: 28px; font-weight: 800; } .num.sm { font-size: 17px; }
.lbl { color: var(--muted); font-size: 13px; }
.muted { color: var(--muted); font-size: 12px; }
.hint-p { font-size: 13.5px; line-height: 1.8; margin-bottom: 12px; }
@media (max-width: 900px) { .kpis { grid-template-columns: 1fr 1fr; } }
</style>
