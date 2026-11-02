<template>
  <DashShell active="tickets">
    <div v-if="!t.id" class="empty" style="padding:60px 0">{{ tr('جاري التحميل...') }}</div>
    <template v-else>
      <div class="ph">
        <div>
          <div class="page-title" style="display:flex;align-items:center;gap:10px">
            <span style="color:var(--accent-d)">{{ t.ticket_no }}</span>
            <StatusPill :status="t.status" :label="tr(statusLabel)" />
            <span v-if="t._pending" class="pill pill-y">{{ tr('بانتظار الرفع للسيرفر') }}</span>
          </div>
          <p class="page-sub">{{ t.created_at?.slice(0, 16).replace('T', ' ') }} · {{ t.creator || '' }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <NuxtLink :to="printUrl('label')" class="btn btn-out" target="_blank"><i class="ti ti-qrcode"></i> {{ tr('ملصق') }}</NuxtLink>
          <NuxtLink :to="printUrl('receipt')" class="btn btn-out" target="_blank"><i class="ti ti-printer"></i> {{ tr('إيصال استلام') }}</NuxtLink>
          <NuxtLink :to="printUrl('invoice')" class="btn btn-out" target="_blank"><i class="ti ti-file-invoice"></i> {{ tr('فاتورة') }}</NuxtLink>
          <a v-if="waLink" :href="waLink" target="_blank" class="btn btn-wa"><i class="ti ti-brand-whatsapp"></i> {{ tr('إشعار الزبون') }}</a>
          <NuxtLink to="/admin/tickets" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ tr('رجوع') }}</NuxtLink>
        </div>
      </div>

      <div class="cols">
        <!-- العمود الرئيسي -->
        <div>
          <!-- الزبون والجهاز -->
          <div class="panel">
            <div class="sec-t"><i class="ti ti-user" style="color:var(--accent)"></i> {{ tr('الزبون والجهاز') }}</div>
            <div class="grid2">
              <div class="rowline"><span>{{ tr('الزبون') }}</span>
                <b>
                  <NuxtLink v-if="t.customer_id" :to="'/admin/customers/' + t.customer_id" class="link">{{ t.customer_name }}</NuxtLink>
                  <template v-else>{{ t.customer_name }}</template>
                </b>
              </div>
              <div class="rowline"><span>{{ tr('الجوال') }}</span><b dir="ltr">{{ t.customer_phone }}</b></div>
              <div class="rowline"><span>{{ tr('نوع الجهاز') }}</span><b>{{ tr(deviceTypes[t.device_type] || t.device_type || '—') }}</b></div>
              <div class="rowline"><span>{{ tr('الجهاز') }}</span><b>{{ t.make }} {{ t.model }}</b></div>
              <div class="rowline"><span>IMEI</span><b dir="ltr">{{ t.imei || '—' }}</b></div>
              <div class="rowline"><span>{{ tr('الرقم التسلسلي') }}</span><b dir="ltr">{{ t.serial || '—' }}</b></div>
              <div class="rowline"><span>{{ tr('العطل') }}</span><b>{{ t.repair_type?.name || '—' }}</b></div>
              <div class="rowline"><span>{{ tr('كلمة مرور الجهاز') }}</span>
                <b v-if="!t.has_password" class="muted">{{ tr('لم تُسجَّل') }}</b>
                <b v-else-if="pw !== null" dir="ltr">{{ pw }} <button class="act sm" @click="pw = null"><i class="ti ti-eye-off"></i></button></b>
                <button v-else class="btn btn-out btn-sm" @click="showPw"><i class="ti ti-lock-open"></i> {{ tr('إظهار') }}</button>
              </div>
            </div>
            <div v-if="t.description" class="note">{{ t.description }}</div>

            <!-- حالة الجهاز عند الاستلام -->
            <div v-if="condEntries.length || (t.accessories || []).length" class="split">
              <div v-if="condEntries.length">
                <div class="mini-t">{{ tr('حالة الجهاز عند الاستلام') }}</div>
                <div class="chips2">
                  <span v-for="[k, v] in condEntries" :key="k" class="chip2">{{ tr(condLabels[k] || k) }}: <b>{{ v }}</b></span>
                </div>
              </div>
              <div v-if="(t.accessories || []).length">
                <div class="mini-t">{{ tr('الملحقات المستلمة') }}</div>
                <div class="chips2"><span v-for="a in t.accessories" :key="a" class="chip2"><i class="ti ti-check"></i> {{ tr(a) }}</span></div>
              </div>
            </div>
          </div>

          <!-- القطع المستخدمة -->
          <div class="panel">
            <div class="sec-t"><i class="ti ti-packages" style="color:var(--accent)"></i> {{ tr('قطع الغيار المستخدمة') }}
              <span class="muted" style="font-weight:400;font-size:12px"> — {{ tr('تُخصم من المخزون تلقائياً') }}</span>
            </div>
            <table v-if="(t.parts || []).length">
              <thead><tr><th>{{ tr('القطعة') }}</th><th>{{ tr('الكمية') }}</th><th>{{ tr('التكلفة') }}</th><th>{{ tr('سعر البيع') }}</th><th></th></tr></thead>
              <tbody>
                <tr v-for="p in t.parts" :key="p.id">
                  <td><b>{{ p.name }}</b></td>
                  <td>{{ p.qty }}</td>
                  <td>{{ money(p.unit_cost * p.qty) }}</td>
                  <td>{{ money(p.unit_price * p.qty) }}</td>
                  <td style="text-align:left"><button class="act danger" :title="tr('إزالة وإرجاع للمخزون')" @click="removePart(p)"><i class="ti ti-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
            <div v-else class="empty-sm">{{ tr('لم تُستخدم قطع بعد') }}</div>

            <div v-if="hasInv" class="addpart">
              <select v-model="partForm.inventory_item_id">
                <option :value="null">{{ tr('— اختر قطعة من المخزون —') }}</option>
                <option v-for="it in stock" :key="it.id" :value="it.id" :disabled="!it.qty">{{ it.name }} ({{ tr('متوفر') }} {{ it.qty }})</option>
              </select>
              <input v-model.number="partForm.qty" type="number" min="1" :placeholder="tr('الكمية')" dir="ltr" style="width:90px" />
              <button class="btn btn-accent" :disabled="!partForm.inventory_item_id || busyPart" @click="addPart"><i class="ti ti-plus"></i> {{ tr('إضافة') }}</button>
            </div>
            <div v-else class="hint"><i class="ti ti-info-circle"></i> {{ tr('فعّل ميزة المخزون لربط القطع بالتذكرة') }}</div>
            <div v-if="errPart" class="err">{{ errPart }}</div>
          </div>

          <!-- الصور -->
          <div class="panel">
            <div class="sec-t"><i class="ti ti-camera" style="color:var(--accent)"></i> {{ tr('صور الجهاز (توثيق الحالة)') }}</div>
            <div class="media">
              <div v-for="m in t.media || []" :key="m.id || m.file_path" class="shot">
                <img :src="fileUrl(m.file_path)" @click="zoom = fileUrl(m.file_path)" />
                <span class="tag" :class="m.kind">{{ m.kind === 'after' ? tr('بعد') : tr('قبل') }}</span>
                <button class="x" @click="removeMedia(m)"><i class="ti ti-x"></i></button>
              </div>
              <label class="shot add">
                <i class="ti ti-camera-plus"></i><span>{{ tr('إضافة صورة') }}</span>
                <select v-model="mediaKind" @click.stop style="margin-top:6px;font-size:11px;padding:3px 6px">
                  <option value="before">{{ tr('قبل') }}</option><option value="after">{{ tr('بعد') }}</option>
                </select>
                <input type="file" accept="image/*" multiple hidden @change="upload" />
              </label>
            </div>
            <div v-if="zoom" class="ov" @click="zoom = null"><img :src="zoom" style="max-width:92vw;max-height:92vh;border-radius:12px" /></div>
          </div>

          <!-- سجل الحالات -->
          <div class="panel">
            <div class="sec-t"><i class="ti ti-history" style="color:var(--accent)"></i> {{ tr('سجل الحالات والأحداث') }}</div>
            <div class="tl">
              <div v-for="e in t.events || []" :key="e.id" class="ev">
                <span class="dot"></span>
                <div>
                  <b>{{ tr(label(e.status)) }}</b><span v-if="e.note" class="muted"> — {{ e.note }}</span>
                  <div class="muted">{{ e.created_at?.slice(0, 16).replace('T', ' ') }} · {{ e.user?.name || '' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- العمود الجانبي -->
        <div>
          <!-- الحالة -->
          <div class="panel">
            <div class="sec-t">{{ tr('تغيير الحالة') }}</div>
            <select v-model="newStatus" class="frow" style="width:100%">
              <option v-for="st in statuses" :key="st.key" :value="st.key">{{ tr(st.label) }}</option>
            </select>
            <input v-model="statusNote" :placeholder="tr('ملاحظة (اختياري)')" class="frow" style="width:100%" />
            <button class="btn btn-accent btn-block" @click="changeStatus"><i class="ti ti-check"></i> {{ tr('تحديث الحالة') }}</button>
          </div>

          <!-- التسعير والموافقة -->
          <div class="panel">
            <div class="sec-t">{{ tr('التكلفة وموافقة العميل') }}</div>
            <div class="rowline"><span>{{ tr('التقدير الأولي') }}</span><b>{{ t.estimated_cost ? money(t.estimated_cost) : '—' }}</b></div>
            <div class="rowline"><span>{{ tr('السعر المتفق عليه') }}</span><b>{{ t.price ? money(t.price) : '—' }}</b></div>
            <div class="rowline"><span>{{ tr('موعد التسليم المتوقع') }}</span>
              <b :style="t.overdue ? 'color:#A32020' : ''">{{ t.promised_at?.slice(0, 10) || '—' }} <i v-if="overdue" class="ti ti-alert-triangle" style="color:#A32020"></i></b>
            </div>
            <div v-if="t.approved_at" class="okline"><i class="ti ti-circle-check"></i> {{ tr('وافق العميل') }} · {{ t.approved_at.slice(0, 16).replace('T', ' ') }} ({{ tr(viaLabels[t.approval_via] || t.approval_via || '') }})</div>
            <template v-else>
              <div class="g2" style="margin-top:10px">
                <input v-model.number="apr.price" type="number" :placeholder="tr('السعر النهائي')" dir="ltr" />
                <select v-model="apr.via">
                  <option value="in_person">{{ tr('حضورياً') }}</option>
                  <option value="phone">{{ tr('هاتفياً') }}</option>
                  <option value="whatsapp">{{ tr('واتساب') }}</option>
                </select>
              </div>
              <div class="g2" style="margin-top:10px">
                <button class="btn btn-accent" @click="approve(true)"><i class="ti ti-thumb-up"></i> {{ tr('وافق العميل') }}</button>
                <button class="btn btn-out" style="color:#A32020" @click="approve(false)"><i class="ti ti-thumb-down"></i> {{ tr('رفض') }}</button>
              </div>
            </template>
          </div>

          <!-- المدفوعات -->
          <div class="panel">
            <div class="sec-t">{{ tr('المدفوعات') }}</div>
            <div class="paybig" :class="{ due: balance > 0 }">
              <div><small>{{ tr('المدفوع') }}</small><b>{{ money(paid) }}</b></div>
              <div><small>{{ tr('المتبقي') }}</small><b>{{ money(balance) }}</b></div>
            </div>
            <div v-for="p in t.payments || []" :key="p.id" class="payrow">
              <div><b>{{ money(p.amount) }}</b> <span class="muted">{{ tr(methodLabel(p.method)) }}</span>
                <div class="muted">{{ (p.paid_at || '').slice(0, 16).replace('T', ' ') }} · {{ p.user?.name || '' }}</div>
              </div>
              <button class="act danger sm" @click="removePayment(p)"><i class="ti ti-trash"></i></button>
            </div>
            <div class="g2" style="margin-top:10px">
              <input v-model.number="payForm.amount" type="number" :placeholder="tr('المبلغ')" dir="ltr" />
              <select v-model="payForm.method">
                <option v-for="m in payMethods" :key="m.key" :value="m.key">{{ tr(m.label) }}</option>
              </select>
            </div>
            <button class="btn btn-accent btn-block" style="margin-top:10px" :disabled="!payForm.amount || busyPay" @click="addPayment"><i class="ti ti-cash"></i> {{ tr('تسجيل دفعة') }}</button>
            <div v-if="errPay" class="err">{{ errPay }}</div>
          </div>

          <!-- الضمان والفني -->
          <div class="panel">
            <div class="sec-t">{{ tr('الضمان والإسناد') }}</div>
            <div class="rowline"><span>{{ tr('الفني المسؤول') }}</span><b>{{ t.technician?.name || '—' }}</b></div>
            <div class="rowline"><span>{{ tr('مدة الضمان') }}</span><b>{{ t.warranty_months || 0 }} {{ tr('أشهر') }}</b></div>
            <div class="rowline" v-if="t.warranty_until"><span>{{ tr('الضمان حتى') }}</span>
              <b :style="warrantyActive ? 'color:var(--green)' : 'color:var(--muted)'">{{ t.warranty_until }} {{ warrantyActive ? '✓' : '(' + tr('منتهي') + ')' }}</b>
            </div>
            <button class="btn btn-out btn-block" style="margin-top:10px" @click="editing = true"><i class="ti ti-edit"></i> {{ tr('تعديل بيانات التذكرة') }}</button>
            <button class="btn btn-out btn-block" style="margin-top:8px;color:#A32020" @click="removeTicket"><i class="ti ti-trash"></i> {{ tr('حذف التذكرة') }}</button>
          </div>
        </div>
      </div>

      <!-- تعديل البيانات -->
      <div v-if="editing" class="ov" @click.self="editing = false">
        <div class="modal" style="width:560px">
          <div class="sec-t">{{ tr('تعديل بيانات التذكرة') }}</div>
          <div class="g2 frow">
            <div><label>{{ tr('اسم الزبون') }}</label><input v-model="ef.customer_name" /></div>
            <div><label>{{ tr('رقم الجوال') }}</label><input v-model="ef.customer_phone" dir="ltr" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ tr('الماركة') }}</label><input v-model="ef.make" /></div>
            <div><label>{{ tr('الموديل') }}</label><input v-model="ef.model" /></div>
          </div>
          <div class="g2 frow">
            <div><label>IMEI</label><input v-model="ef.imei" dir="ltr" /></div>
            <div><label>{{ tr('الرقم التسلسلي') }}</label><input v-model="ef.serial" dir="ltr" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ tr('التقدير الأولي') }}</label><input v-model.number="ef.estimated_cost" type="number" dir="ltr" /></div>
            <div><label>{{ tr('السعر المتفق عليه') }}</label><input v-model.number="ef.price" type="number" dir="ltr" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ tr('موعد التسليم المتوقع') }}</label><input v-model="ef.promised_at" type="date" dir="ltr" /></div>
            <div><label>{{ tr('الضمان (أشهر)') }}</label><input v-model.number="ef.warranty_months" type="number" dir="ltr" /></div>
          </div>
          <div class="g2 frow">
            <div><label>{{ tr('الفني المسؤول') }}</label>
              <select v-model="ef.technician_id"><option :value="null">{{ tr('— بدون —') }}</option><option v-for="x in techs" :key="x.id" :value="x.id">{{ x.name }}</option></select>
            </div>
            <div><label>{{ tr('كلمة مرور الجهاز') }}</label><input v-model="ef.device_password" dir="ltr" :placeholder="t.has_password ? tr('(محفوظة — اتركها فارغة للإبقاء)') : ''" /></div>
          </div>
          <div class="frow"><label>{{ tr('وصف المشكلة') }}</label><textarea v-model="ef.description" rows="2"></textarea></div>
          <div v-if="errEdit" class="err">{{ errEdit }}</div>
          <div style="display:flex;gap:10px">
            <button class="btn btn-accent" @click="saveEdit"><i class="ti ti-check"></i> {{ tr('حفظ التعديل') }}</button>
            <button class="btn btn-out" @click="editing = false">{{ tr('إلغاء') }}</button>
          </div>
        </div>
      </div>
    </template>
  </DashShell>
</template>

<script setup>
const { api, fileUrl } = useApi()
const { t: tr, isEn } = useI18n()
const { confirmDlg } = useConfirm()
const { settings, loadSettings } = useSettings()
const { has, ensure } = useFeatures()
const route = useRoute()

const t = ref({})
const statuses = ref([])
const payMethods = ref([])
const statusLabel = ref('')
const newStatus = ref('')
const statusNote = ref('')
const pw = ref(null)
const stock = ref([])
const techs = ref([])
const partForm = ref({ inventory_item_id: null, qty: 1 })
const payForm = ref({ amount: null, method: 'cash' })
const apr = ref({ price: null, via: 'in_person' })
const errPart = ref(''); const errPay = ref(''); const errEdit = ref('')
const busyPart = ref(false); const busyPay = ref(false)
const editing = ref(false)
const ef = ref({})
const mediaKind = ref('before')
const zoom = ref(null)
const hasInv = ref(false)

const deviceTypes = { phone: 'جوال', tablet: 'تابلت', laptop: 'لابتوب', watch: 'ساعة ذكية', other: 'أخرى' }
const condLabels = { screen: 'الشاشة', body: 'الهيكل', camera: 'الكاميرا', power: 'التشغيل', battery: 'البطارية', note: 'ملاحظة' }
const viaLabels = { in_person: 'حضورياً', phone: 'هاتفياً', whatsapp: 'واتساب' }

const money = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG') + ' ' + tr('ر.س')
const label = (k) => statuses.value.find((s) => s.key === k)?.label || k
const methodLabel = (k) => payMethods.value.find((m) => m.key === k)?.label || k
const condEntries = computed(() => Object.entries(t.value.condition || {}).filter(([, v]) => v))
const paid = computed(() => t.value.paid ?? 0)
const balance = computed(() => t.value.balance ?? Math.max(0, (t.value.price || 0) - paid.value))
const overdue = computed(() => t.value.promised_at && !['delivered', 'cancelled', 'rejected', 'unrepairable'].includes(t.value.status) && t.value.promised_at.slice(0, 10) < new Date().toISOString().slice(0, 10))
const warrantyActive = computed(() => t.value.warranty_until && t.value.warranty_until >= new Date().toISOString().slice(0, 10))
const printUrl = (type) => `/admin/print/${t.value.id}?type=${type}`

// رسائل واتساب لكل حالة
const fmtN = (n) => Number(n).toLocaleString('ar-EG')
const waMessages = {
  draft: (x) => `تم استلام جهازك (${x.device}) وفتح تذكرة رقم ${x.ticket_no}.`,
  received: (x) => `تم استلام جهازك (${x.device}) وفتح تذكرة رقم ${x.ticket_no}. سنبدأ الفحص ونوافيك بالتحديثات.`,
  inspecting: (x) => `جهازك (${x.device}) — تذكرة ${x.ticket_no}: قيد الفحص الآن وسنوافيك بنتيجة الفحص والتكلفة.`,
  awaiting_approval: (x) => `جهازك (${x.device}) — تذكرة ${x.ticket_no}: بانتظار موافقتك على التكلفة${x.price ? ' (' + fmtN(x.price) + ')' : ''} للبدء بالتصليح.`,
  waiting_part: (x) => `جهازك (${x.device}) — تذكرة ${x.ticket_no}: بانتظار وصول قطعة الغيار، وسنباشر فور وصولها.`,
  in_repair: (x) => `بدأنا تصليح جهازك (${x.device}) — تذكرة ${x.ticket_no}. سنعلمك فور الانتهاء.`,
  testing: (x) => `جهازك (${x.device}) — تذكرة ${x.ticket_no}: انتهى التصليح وجاري الاختبار النهائي.`,
  ready: (x) => `جهازك (${x.device}) جاهز للاستلام ✅ — تذكرة ${x.ticket_no}.${x.balance > 0 ? ' المتبقي ' + fmtN(x.balance) + '.' : ''}`,
  delivered: (x) => `شكراً لثقتك بنا 🙏 تم تسليم جهازك (${x.device}) — تذكرة ${x.ticket_no}.${x.warranty_months ? ' الضمان ' + x.warranty_months + ' أشهر.' : ''}`,
  rejected: (x) => `بخصوص تذكرة ${x.ticket_no} (${x.device}): سجّلنا عدم الموافقة على التكلفة. جهازك جاهز للاستلام.`,
  unrepairable: (x) => `نأسف — تعذّر تصليح جهازك (${x.device}) — تذكرة ${x.ticket_no}. جهازك جاهز للاستلام.`,
  cancelled: (x) => `بخصوص تذكرة ${x.ticket_no} (${x.device}): تم إلغاء الطلب. للاستفسار تواصل معنا.`,
}
function intlPhone(p) {
  let n = (p || '').replace(/\D/g, '')
  if (n.startsWith('00')) n = n.slice(2)
  else if (n.startsWith('0')) n = '966' + n.slice(1)
  return n
}
const waLink = computed(() => {
  if (!t.value?.customer_phone) return null
  const ctx = { ...t.value, device: [t.value.make, t.value.model].filter(Boolean).join(' ') || 'جهازك', balance: balance.value }
  const line = (waMessages[t.value.status] || waMessages.received)(ctx)
  const text = `مرحباً ${t.value.customer_name || ''}،\n${line}\n\n${settings.value.shop_name || ''}`
  return `https://wa.me/${intlPhone(t.value.customer_phone)}?text=${encodeURIComponent(text)}`
})

async function load() {
  const r = await api('/dashboard/tickets/' + route.params.id)
  t.value = r.ticket
  // من قائمة الحالات حتى تنعكس التغييرات المعلّقة (أوفلاين) على الفور
  statusLabel.value = (r.statuses || []).find((s) => s.key === r.ticket.status)?.label || r.status_label || ''
  statuses.value = r.statuses || []
  payMethods.value = r.payment_methods || []
  newStatus.value = r.ticket.status
  apr.value.price = r.ticket.price || r.ticket.estimated_cost || null
}

async function changeStatus() {
  await api('/dashboard/tickets/' + t.value.id + '/status', { method: 'POST', body: { status: newStatus.value, note: statusNote.value || undefined } })
  statusNote.value = ''
  await load()
}

async function showPw() {
  try { pw.value = (await api('/dashboard/tickets/' + t.value.id + '/password')).password || '—' } catch { pw.value = '—' }
}

async function approve(ok) {
  if (!await confirmDlg(ok ? tr('توثيق موافقة العميل') : tr('توثيق رفض العميل'), ok ? tr('سيُسجَّل السعر وتنتقل التذكرة إلى قيد التصليح') : tr('ستتحول التذكرة إلى «رفض العميل»'))) return
  await api('/dashboard/tickets/' + t.value.id + '/approval', { method: 'POST', body: { approved: ok, via: apr.value.via, price: apr.value.price || undefined } })
  await load()
}

async function addPart() {
  errPart.value = ''; busyPart.value = true
  try {
    await api('/dashboard/tickets/' + t.value.id + '/parts', { method: 'POST', body: partForm.value })
    partForm.value = { inventory_item_id: null, qty: 1 }
    await Promise.all([load(), loadStock()])
  } catch (e) { errPart.value = e?.data?.message || tr('تعذّر الحفظ') } finally { busyPart.value = false }
}
async function removePart(p) {
  if (!await confirmDlg(tr('إزالة القطعة'), tr('ستُعاد الكمية إلى المخزون'))) return
  await api('/dashboard/tickets/' + t.value.id + '/parts/' + p.id, { method: 'DELETE' })
  await Promise.all([load(), loadStock()])
}

async function addPayment() {
  errPay.value = ''; busyPay.value = true
  try {
    await api('/dashboard/tickets/' + t.value.id + '/payments', { method: 'POST', body: payForm.value })
    payForm.value = { amount: null, method: payForm.value.method }
    await load()
  } catch (e) { errPay.value = e?.data?.message || tr('تعذّر الحفظ') } finally { busyPay.value = false }
}
async function removePayment(p) {
  if (!await confirmDlg(tr('حذف الدفعة'), money(p.amount))) return
  await api('/dashboard/tickets/' + t.value.id + '/payments/' + p.id, { method: 'DELETE' })
  await load()
}

async function upload(e) {
  const files = [...(e.target.files || [])]
  e.target.value = ''
  const media = (t.value.media || []).map((m) => ({ path: m.file_path, kind: m.kind }))
  for (const f of files) {
    const fd = new FormData(); fd.append('file', f)
    try { const r = await api('/uploads', { method: 'POST', body: fd }); media.push({ path: r.path, kind: mediaKind.value }) } catch {}
  }
  await saveMedia(media)
}
async function removeMedia(m) {
  if (!await confirmDlg(tr('حذف الصورة'), '')) return
  await saveMedia((t.value.media || []).filter((x) => x !== m).map((x) => ({ path: x.file_path, kind: x.kind })))
}
async function saveMedia(media) {
  await api('/dashboard/tickets/' + t.value.id, { method: 'PUT', body: { ...editBody(), media } })
  await load()
}

function editBody() {
  const x = t.value
  return {
    customer_id: x.customer_id, customer_name: x.customer_name, customer_phone: x.customer_phone,
    device_type: x.device_type, make: x.make, model: x.model, imei: x.imei, serial: x.serial,
    repair_type_id: x.repair_type?.id || x.repair_type_id, description: x.description,
    condition: x.condition, accessories: x.accessories,
    estimated_cost: x.estimated_cost, price: x.price, promised_at: x.promised_at?.slice(0, 10),
    warranty_months: x.warranty_months, status: x.status, technician_id: x.technician?.id || x.technician_id,
  }
}

watch(editing, (v) => { if (v) { errEdit.value = ''; ef.value = { ...editBody(), device_password: '' } } })
async function saveEdit() {
  errEdit.value = ''
  try {
    const body = { ...ef.value }
    if (!body.device_password) delete body.device_password
    await api('/dashboard/tickets/' + t.value.id, { method: 'PUT', body })
    editing.value = false
    await load()
  } catch (e) { errEdit.value = e?.data?.message || tr('تعذّر الحفظ — تحقق من الحقول') }
}

async function removeTicket() {
  if (!await confirmDlg(tr('حذف التذكرة'), tr('لا يمكن التراجع عن هذا الإجراء'))) return
  await api('/dashboard/tickets/' + t.value.id, { method: 'DELETE' })
  navigateTo('/admin/tickets')
}

async function loadStock() {
  if (!hasInv.value) return
  try { stock.value = await api('/dashboard/inventory') } catch {}
}

onMounted(async () => {
  loadSettings()
  await ensure()
  hasInv.value = has('inventory')
  await load()
  loadStock()
  try { techs.value = await api('/dashboard/technicians') } catch {}
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.65fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 26px; }
.rowline { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--line-2); font-size: 13.5px; gap: 12px; }
.rowline span { color: var(--muted); }
.note { background: var(--bg); border-radius: 9px; padding: 12px; font-size: 13px; margin-top: 12px; }
.split { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 14px; }
.mini-t { font-size: 12.5px; font-weight: 700; color: var(--muted); margin-bottom: 8px; }
.chips2 { display: flex; flex-wrap: wrap; gap: 6px; }
.chip2 { background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 5px 10px; font-size: 12px; }
.chip2 i { color: var(--green); }
.empty-sm { color: var(--muted); font-size: 13px; padding: 8px 0 14px; }
.addpart { display: flex; gap: 8px; margin-top: 12px; }
.addpart select { flex: 1; }
.hint { background: #E6F1FB; border-radius: 9px; padding: 10px 13px; font-size: 12px; color: var(--accent-d); display: flex; gap: 8px; margin-top: 10px; }
.media { display: flex; flex-wrap: wrap; gap: 10px; }
.shot { position: relative; width: 110px; height: 110px; border-radius: 10px; overflow: hidden; border: 1px solid var(--line); }
.shot img { width: 100%; height: 100%; object-fit: cover; cursor: zoom-in; }
.shot .tag { position: absolute; bottom: 4px; inset-inline-start: 4px; font-size: 10px; background: rgba(20,33,58,.75); color: #fff; padding: 2px 7px; border-radius: 6px; }
.shot .tag.after { background: var(--green); }
.shot .x { position: absolute; top: 4px; inset-inline-end: 4px; width: 20px; height: 20px; border: none; border-radius: 50%; background: rgba(20,33,58,.65); color: #fff; cursor: pointer; font-size: 11px; }
.shot.add { display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); font-size: 12px; gap: 4px; border-style: dashed; }
.shot.add i { font-size: 22px; }
.tl .ev { display: flex; gap: 10px; padding: 8px 0; }
.tl .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent); margin-top: 6px; flex-shrink: 0; }
.okline { margin-top: 10px; background: #E4F6EF; color: var(--green); border-radius: 9px; padding: 10px 13px; font-size: 12.5px; display: flex; gap: 7px; align-items: center; }
.paybig { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px; }
.paybig > div { background: var(--bg); border-radius: 10px; padding: 10px 14px; }
.paybig small { color: var(--muted); font-size: 11.5px; display: block; }
.paybig b { font-size: 18px; }
.paybig.due > div:last-child { background: #FBE9E8; } .paybig.due > div:last-child b { color: #A32020; }
.payrow { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--line-2); font-size: 13px; }
.act.sm { width: 26px; height: 26px; font-size: 13px; }
.btn-sm { padding: 6px 12px; font-size: 12.5px; }
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.55); z-index: 60; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; max-width: 92vw; max-height: 92vh; overflow-y: auto; }
@media (max-width: 1000px) { .cols, .grid2, .split { grid-template-columns: 1fr; } }
</style>
