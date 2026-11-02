<template>
  <div class="print-root" :class="type">
    <div v-if="!t.id" style="text-align:center;padding:40px;font-family:sans-serif">...</div>

    <!-- ملصق صغير يُلصق على الجهاز -->
    <div v-else-if="type === 'label'" class="label">
      <div class="qr" v-html="qrSvg"></div>
      <div class="linfo">
        <b class="lno">{{ t.ticket_no }}</b>
        <div>{{ t.customer_name }}</div>
        <div dir="ltr">{{ t.customer_phone }}</div>
        <div>{{ t.make }} {{ t.model }}</div>
        <div v-if="faultText">{{ faultText }}</div>
        <div class="ldate">{{ (t.created_at || '').slice(0, 10) }}</div>
      </div>
    </div>

    <!-- إيصال استلام / فاتورة -->
    <div v-else class="doc">
      <header>
        <div>
          <h1>{{ settings.shop_name || 'موبايل فيكس' }}</h1>
          <div class="sub">{{ settings.address || '' }}<span v-if="settings.phone"> · {{ settings.phone }}</span></div>
        </div>
        <div class="hq">
          <div class="qr sm" v-html="qrSvg"></div>
          <b class="dno">{{ t.ticket_no }}</b>
        </div>
      </header>
      <div class="doctitle">{{ type === 'invoice' ? 'فاتورة إصلاح' : 'إيصال استلام جهاز' }}</div>

      <table class="meta">
        <tr><td>الزبون</td><td><b>{{ t.customer_name }}</b></td><td>الجوال</td><td dir="ltr">{{ t.customer_phone }}</td></tr>
        <tr><td>الجهاز</td><td>{{ t.make }} {{ t.model }}</td><td>التاريخ</td><td dir="ltr">{{ (t.created_at || '').slice(0, 16).replace('T', ' ') }}</td></tr>
        <tr><td>IMEI</td><td dir="ltr">{{ t.imei || '—' }}</td><td>السيريال</td><td dir="ltr">{{ t.serial || '—' }}</td></tr>
        <tr><td>العطل</td><td colspan="3">{{ faultText }}{{ t.description ? ' — ' + t.description : '' }}</td></tr>
      </table>

      <template v-if="type === 'receipt'">
        <div class="sect" v-if="condEntries.length">حالة الجهاز عند الاستلام</div>
        <div class="chips" v-if="condEntries.length"><span v-for="[k, v] in condEntries" :key="k">{{ condLabels[k] || k }}: <b>{{ v }}</b></span></div>
        <div class="sect" v-if="(t.accessories || []).length">الملحقات المستلمة</div>
        <div class="chips" v-if="(t.accessories || []).length"><span v-for="a in t.accessories" :key="a">{{ a }}</span></div>
        <table class="lines">
          <tr><td>التكلفة التقديرية</td><td class="l">{{ t.estimated_cost ? money(t.estimated_cost) : 'تُحدد بعد الفحص' }}</td></tr>
          <tr v-if="t.promised_at"><td>موعد التسليم المتوقع</td><td class="l">{{ t.promised_at.slice(0, 10) }}</td></tr>
        </table>
        <div class="terms">
          <b>شروط الاستلام:</b>
          <div>• هذا الإيصال لازم لاستلام الجهاز. المحل غير مسؤول عن الأجهزة غير المستلمة بعد 30 يوماً من جاهزيتها.</div>
          <div>• التكلفة النهائية بعد الفحص وبموافقة الزبون قبل البدء بالإصلاح.</div>
          <div>• الزبون مسؤول عن نسخ بياناته احتياطياً قبل التسليم.</div>
        </div>
      </template>

      <template v-else>
        <table class="lines inv">
          <tr class="h"><td>البيان</td><td>الكمية</td><td>السعر</td></tr>
          <tr v-for="p in t.parts || []" :key="p.id"><td>{{ p.name }}</td><td>{{ p.qty }}</td><td>{{ money(p.unit_price * p.qty) }}</td></tr>
          <tr><td>أجرة الفحص والإصلاح{{ (t.parts || []).length ? ' (بعد خصم القطع)' : '' }}</td><td>1</td><td>{{ money(labor) }}</td></tr>
          <tr class="tot"><td colspan="2">الإجمالي</td><td>{{ money(t.price || 0) }}</td></tr>
          <tr><td colspan="2">المدفوع</td><td>{{ money(t.paid || 0) }}</td></tr>
          <tr class="due" v-if="(t.balance || 0) > 0"><td colspan="2">المتبقي</td><td>{{ money(t.balance) }}</td></tr>
        </table>

        <!-- سجل الدفعات بتواريخها وتسلسلها -->
        <template v-if="paymentsAsc.length">
          <div class="sect">سجل الدفعات</div>
          <table class="lines pays">
            <tr class="h"><td>#</td><td>التاريخ</td><td>الطريقة</td><td>المبلغ</td></tr>
            <tr v-for="(p, i) in paymentsAsc" :key="p.id">
              <td>{{ i + 1 }}</td>
              <td>{{ (p.paid_at || '').slice(0, 16).replace('T', ' ') }}</td>
              <td>{{ methodLabel(p.method) }}</td>
              <td>{{ money(p.amount) }}</td>
            </tr>
          </table>
        </template>

        <div class="terms" v-if="t.warranty_months">
          <b>الضمان:</b> {{ t.warranty_months }} أشهر على الإصلاح{{ t.warranty_until ? ' — حتى ' + t.warranty_until : '' }}.
          لا يشمل الضمان الكسر أو السوائل أو سوء الاستخدام.
        </div>
      </template>

      <footer>شكراً لثقتكم — {{ settings.shop_name || '' }}</footer>
    </div>

    <div class="noprint pbtns" v-if="t.id">
      <button class="pbtn" @click="doPrint"><i class="ti ti-printer"></i> طباعة</button>
      <button v-if="type !== 'label'" class="pbtn pdf" :disabled="pdfBusy" @click="downloadPdf">
        <i class="ti" :class="pdfBusy ? 'ti-loader-2 spin' : 'ti-file-download'"></i> {{ pdfBusy ? 'جارٍ التجهيز…' : 'تنزيل PDF' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import qrcode from 'qrcode-generator'

const { api, token } = useApi()
const { settings, loadSettings } = useSettings()
const route = useRoute()
const type = computed(() => ['label', 'invoice'].includes(route.query.type) ? route.query.type : 'receipt')
const t = ref({})

const condLabels = { screen: 'الشاشة', body: 'الهيكل', camera: 'الكاميرا', power: 'التشغيل', battery: 'البطارية', note: 'ملاحظة' }
const methodLabels = { cash: 'نقدي', card: 'شبكة/بطاقة', transfer: 'تحويل', other: 'أخرى' }
const methodLabel = (m) => methodLabels[m] || m
const condEntries = computed(() => Object.entries(t.value.condition || {}).filter(([, v]) => v))
const faultText = computed(() => (t.value.repair_types?.length ? t.value.repair_types.join('، ') : (t.value.repair_type?.name || '')))
const paymentsAsc = computed(() => [...(t.value.payments || [])].sort((a, b) => (a.paid_at || '').localeCompare(b.paid_at || '')))
const money = (n) => Number(n || 0).toLocaleString('ar-EG') + ' ر.س'
const labor = computed(() => Math.max(0, (Number(t.value.price) || 0) - (t.value.parts || []).reduce((a, p) => a + p.qty * Number(p.unit_price), 0)))

const qrSvg = computed(() => {
  if (!t.value.ticket_no) return ''
  const qr = qrcode(0, 'M')
  qr.addData(t.value.ticket_no)
  qr.make()
  return qr.createSvgTag({ cellSize: 3, margin: 0, scalable: true })
})

function doPrint() { window.print() }

const pdfBusy = ref(false)
async function downloadPdf() {
  if (pdfBusy.value) return
  pdfBusy.value = true
  try {
    const html2pdf = (await import('html2pdf.js')).default
    // تأكد من تحميل الخط العربي قبل الالتقاط حتى لا يخرج بخط بديل
    if (document.fonts?.ready) await document.fonts.ready
    const el = document.querySelector('.doc')
    const docType = type.value === 'invoice' ? 'فاتورة' : 'ايصال'
    await html2pdf().set({
      margin: [10, 10, 10, 10],
      filename: `${docType}-${t.value.ticket_no || ''}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
    }).from(el).save()
  } catch (e) {
    // احتياطي: نافذة الطباعة (حفظ كـ PDF)
    window.print()
  } finally { pdfBusy.value = false }
}

onMounted(async () => {
  if (!token.value) return navigateTo('/login')
  loadSettings()
  const r = await api('/dashboard/tickets/' + route.params.id)
  t.value = r.ticket
  setTimeout(() => window.print(), 600)
})
</script>

<style scoped>
.print-root { font-family: Tajawal, sans-serif; direction: rtl; color: #111; background: #fff; min-height: 100vh; }
.pbtns { position: fixed; bottom: 18px; inset-inline-start: 18px; display: flex; gap: 10px; }
.pbtn { padding: 11px 20px; border: none; border-radius: 10px; background: #185FA5; color: #fff; font-family: inherit; font-size: 14px; cursor: pointer; display: flex; gap: 8px; align-items: center; box-shadow: 0 4px 14px rgba(20,33,58,.18); }
.pbtn.pdf { background: #137A4B; }
.pbtn:disabled { opacity: .7; cursor: default; }
.spin { animation: spin 1s linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }

/* الملصق: مقاس ~80×50مم */
.label { width: 80mm; padding: 4mm; display: flex; gap: 4mm; align-items: center; border: 1px dashed #bbb; margin: 10px auto; }
.label .qr :deep(svg) { width: 17mm; height: 17mm; display: block; }
.linfo { font-size: 10.5pt; line-height: 1.45; }
.lno { font-size: 13pt; }
.ldate { color: #555; font-size: 9pt; }

/* المستند A5/A4 */
.doc { max-width: 720px; margin: 0 auto; padding: 26px 30px; }
.doc header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #185FA5; padding-bottom: 12px; }
.doc h1 { font-size: 20px; color: #0C447C; margin: 0; }
.doc .sub { font-size: 11.5px; color: #555; margin-top: 3px; }
.hq { text-align: center; }
.qr.sm :deep(svg) { width: 52px; height: 52px; }
.dno { display: block; font-size: 13px; color: #0C447C; margin-top: 3px; }
.doctitle { text-align: center; font-size: 16px; font-weight: 800; margin: 14px 0; }
table.meta { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 12.5px; margin-bottom: 10px; border: 1px solid #e3e6ec; border-radius: 10px; overflow: hidden; }
table.meta td { border-bottom: 1px solid #eef0f4; border-inline-start: 1px solid #eef0f4; padding: 9px 12px; }
table.meta tr:last-child td { border-bottom: none; }
table.meta td:last-child { border-inline-start: none; }
table.meta td:nth-child(odd) { background: #f6f8fa; color: #6b7280; width: 15%; white-space: nowrap; font-weight: 600; }
.sect { font-weight: 800; font-size: 12.5px; margin: 12px 0 6px; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; font-size: 11.5px; }
.chips span { border: 1px solid #ddd; border-radius: 6px; padding: 3px 9px; }
table.lines { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; margin-top: 14px; border: 1px solid #e3e6ec; border-radius: 10px; overflow: hidden; }
table.lines td { border-bottom: 1px solid #eef0f4; padding: 10px 12px; }
table.lines tr:last-child td { border-bottom: none; }
table.lines td.l { text-align: start; font-weight: 700; }
/* الفاتورة: رأس أزرق + أعمدة محاذاة + صفوف المجاميع مميّزة */
table.lines.inv .h td { background: #185FA5; color: #fff; font-weight: 700; }
table.lines.inv td:nth-child(2) { width: 16%; text-align: center; color: #6b7280; }
table.lines.inv td:last-child { width: 26%; font-weight: 600; }
table.lines.inv .tot td { background: #f6f8fa; font-weight: 800; font-size: 14px; border-top: 1.5px solid #185FA5; }
table.lines.inv .due td { color: #A32020; font-weight: 800; background: #fdf2f2; }
table.lines.pays { margin-top: 10px; font-size: 12.5px; }
table.lines.pays .h td { background: #475569; color: #fff; font-weight: 700; }
table.lines.pays td:first-child { width: 10%; text-align: center; color: #6b7280; }
table.lines.pays td:last-child { width: 24%; font-weight: 600; }
.terms { margin-top: 16px; font-size: 11px; color: #444; line-height: 1.8; background: #f8f9fb; border-radius: 8px; padding: 10px 14px; }
.doc footer { text-align: center; margin-top: 22px; font-size: 12px; color: #666; }

@media print {
  .noprint { display: none !important; }
  .label { border: none; margin: 0; }
  .doc { padding: 0; max-width: none; }
  .print-root.label-root { margin: 0; }
}
@page { margin: 10mm; }
</style>
