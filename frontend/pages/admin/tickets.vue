<template>
  <DashShell active="tickets">
    <div class="ph">
      <div><div class="page-title">{{ tr('التذاكر') }}</div><p class="page-sub">{{ tr('إدارة كل طلبات التصليح') }}</p></div>
      <NuxtLink to="/admin/ticket-new" class="btn btn-accent"><i class="ti ti-plus"></i> {{ tr('تذكرة جديدة') }}</NuxtLink>
    </div>

    <div class="chips">
      <button class="chip" :class="{ on: !filter }" @click="setFilter('')">{{ tr('الكل') }} ({{ total }})</button>
      <button v-for="st in statuses" :key="st.key" class="chip" :class="{ on: filter === st.key }" @click="setFilter(st.key)">
        {{ tr(st.label) }} ({{ counts[st.key] || 0 }})
      </button>
    </div>

    <table>
      <thead><tr><th>{{ tr('التذكرة') }}</th><th>{{ tr('الزبون') }}</th><th>{{ tr('الجهاز / العطل') }}</th><th>{{ tr('السعر') }}</th><th>{{ tr('الحالة') }}</th><th>{{ tr('الفني') }}</th><th>{{ tr('التاريخ') }}</th></tr></thead>
      <tbody>
        <tr v-for="t in tickets" :key="t.id" @click="open(t)" style="cursor:pointer">
          <td><b style="color:var(--accent-d)">{{ t.ticket_no }}</b></td>
          <td>{{ t.customer_name }}<div class="muted" dir="ltr" style="text-align:right">{{ t.customer_phone }}</div></td>
          <td>{{ t.device }} <span class="muted">· {{ t.repair_type }}</span></td>
          <td>{{ t.price ? fmt(t.price) + ' ' + tr('ر.س') : '—' }}</td>
          <td><StatusPill :status="t.status" :label="t.status_label" /></td>
          <td>{{ t.technician || '—' }}</td>
          <td class="muted">{{ t.created_at }}</td>
        </tr>
        <tr v-if="!tickets.length"><td colspan="7" class="empty">{{ tr('لا توجد تذاكر بهذا الفلتر') }}</td></tr>
      </tbody>
    </table>

    <!-- drawer -->
    <div v-if="sel" class="ov" @click.self="sel = null">
      <div class="drawer">
        <div class="dh">
          <div><b style="color:var(--accent-d);font-size:18px">{{ sel.ticket_no }}</b><div class="muted">{{ det.created_at }}</div></div>
          <button class="act" @click="sel = null"><i class="ti ti-x"></i></button>
        </div>
        <div class="rowline"><span>{{ tr('الزبون') }}</span><b>{{ det.customer_name }} · <span dir="ltr">{{ det.customer_phone }}</span></b></div>
        <div class="rowline"><span>{{ tr('الجهاز') }}</span><b>{{ det.make }} {{ det.model }} <span v-if="det.imei" class="muted">({{ det.imei }})</span></b></div>
        <div class="rowline"><span>{{ tr('العطل') }}</span><b>{{ det.repair_type?.name || '—' }}</b></div>
        <div class="rowline"><span>{{ tr('السعر / الضمان') }}</span><b>{{ det.price ? fmt(det.price)+' '+tr('ر.س') : '—' }} · {{ det.warranty_months || 0 }} {{ tr('أشهر') }}</b></div>
        <div v-if="det.description" class="note">{{ det.description }}</div>

        <label style="margin-top:16px">{{ tr('تغيير الحالة') }}</label>
        <div class="g2">
          <select v-model="newStatus"><option v-for="st in statuses" :key="st.key" :value="st.key">{{ tr(st.label) }}</option></select>
          <button class="btn btn-accent" @click="changeStatus"><i class="ti ti-check"></i> {{ tr('تحديث') }}</button>
        </div>

        <a v-if="waLink" :href="waLink" target="_blank" class="btn btn-wa btn-block" style="margin-top:10px">
          <i class="ti ti-brand-whatsapp"></i> {{ tr('إشعار الزبون بالحالة عبر واتساب') }}
        </a>

        <div class="sec-t" style="margin-top:20px">{{ tr('سجل الحالات') }}</div>
        <div class="tl">
          <div v-for="e in det.events || []" :key="e.id" class="ev">
            <span class="dot"></span>
            <div><b>{{ tr(label(e.status)) }}</b><span v-if="e.note" class="muted"> — {{ e.note }}</span><div class="muted">{{ e.created_at?.slice(0,16).replace('T',' ') }}</div></div>
          </div>
        </div>

        <button class="btn btn-out btn-block" style="margin-top:18px;color:var(--accent)" @click="remove"><i class="ti ti-trash"></i> {{ tr('حذف التذكرة') }}</button>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const { settings, loadSettings } = useSettings()
const { t: tr, isEn } = useI18n()
const route = useRoute()

const tickets = ref([])
const counts = ref({})
const statuses = ref([])
const filter = ref('')
const sel = ref(null)
const det = ref({})
const newStatus = ref('')

const total = computed(() => Object.values(counts.value).reduce((a, b) => a + Number(b), 0))
const fmt = (n) => Number(n).toLocaleString('ar-EG')
const label = (k) => statuses.value.find(s => s.key === k)?.label || k

// رقم دولي للواتساب: 05xxxxxxxx → 9665xxxxxxxx (افتراضي السعودية)
function intlPhone(p) {
  let n = (p || '').replace(/\D/g, '')
  if (n.startsWith('00')) n = n.slice(2)
  else if (n.startsWith('0')) n = '966' + n.slice(1)
  return n
}
// رسالة تحديث الحالة الجاهزة
const waMessages = {
  received: (t) => `تم استلام جهازك (${t.device}) وفتح تذكرة رقم ${t.ticket_no}. سنبدأ الفحص ونوافيك بالتحديثات.`,
  awaiting_approval: (t) => `جهازك (${t.device}) — تذكرة ${t.ticket_no}: بانتظار موافقتك على عرض السعر${t.price ? ' (' + fmt(t.price) + ' ر.س)' : ''} للبدء بالتصليح.`,
  in_repair: (t) => `بدأنا تصليح جهازك (${t.device}) — تذكرة ${t.ticket_no}. سنعلمك فور الانتهاء.`,
  ready: (t) => `جهازك (${t.device}) جاهز للاستلام ✅ — تذكرة ${t.ticket_no}.${t.warranty_months ? ' الضمان ' + t.warranty_months + ' أشهر.' : ''}`,
  delivered: (t) => `شكراً لثقتك بنا 🙏 تم تسليم جهازك (${t.device}) — تذكرة ${t.ticket_no}. نتمنى لك تجربة ممتازة.`,
  cancelled: (t) => `بخصوص تذكرة ${t.ticket_no} (${t.device}): تم إلغاء الطلب. للاستفسار تواصل معنا.`,
}
const waLink = computed(() => {
  const t = det.value
  if (!t?.customer_phone) return null
  const ctx = { ...t, device: [t.make, t.model].filter(Boolean).join(' ') || 'جهازك' }
  const shop = settings.value.shop_name || 'موبايل فيكس'
  const line = (waMessages[t.status] || waMessages.received)(ctx)
  const text = `مرحباً ${t.customer_name || ''}،\n${line}\n\n${shop}`
  return `https://wa.me/${intlPhone(t.customer_phone)}?text=${encodeURIComponent(text)}`
})

async function load() {
  const r = await api('/dashboard/tickets' + (filter.value ? '?status=' + filter.value : ''))
  tickets.value = r.tickets; counts.value = r.counts; statuses.value = r.statuses
}
function setFilter(f) { filter.value = f; load() }

async function open(t) {
  sel.value = t
  const r = await api('/dashboard/tickets/' + t.id)
  det.value = r.ticket; newStatus.value = r.ticket.status
}
async function changeStatus() {
  await api('/dashboard/tickets/' + sel.value.id + '/status', { method: 'POST', body: { status: newStatus.value } })
  await open(sel.value); await load()
}
async function remove() {
  if (!await confirmDlg(tr('حذف التذكرة'), tr('لا يمكن التراجع عن هذا الإجراء'))) return
  await api('/dashboard/tickets/' + sel.value.id, { method: 'DELETE' })
  sel.value = null; await load()
}

onMounted(async () => {
  loadSettings()
  await load()
  if (route.query.open) {
    const t = tickets.value.find(x => x.id == route.query.open)
    if (t) open(t)
  }
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.chip { font-family: inherit; padding: 8px 15px; border-radius: 20px; border: 1px solid var(--line); background: #fff; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; }
.chip:hover { border-color: #C7CCD6; } .chip.on { background: var(--accent); border-color: var(--accent); color: #fff; }
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; justify-content: flex-start; }
.drawer { width: 460px; max-width: 92vw; background: #fff; height: 100%; padding: 22px; overflow-y: auto; }
.dh { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
.rowline { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 12px; }
.rowline span { color: var(--muted); }
.note { background: var(--bg); border-radius: 9px; padding: 12px; font-size: 13px; margin-top: 10px; }
.tl .ev { display: flex; gap: 10px; padding: 8px 0; }
.tl .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); margin-top: 6px; flex-shrink: 0; }
</style>
