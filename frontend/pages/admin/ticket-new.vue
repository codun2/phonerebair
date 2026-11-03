<template>
  <DashShell active="ticket-new">
    <div class="ph">
      <div><div class="page-title">{{ tr('تذكرة تصليح جديدة') }}</div><p class="page-sub">{{ tr('سجّل جهاز الزبون وافتح تذكرة') }}</p></div>
      <NuxtLink to="/admin/tickets" class="btn btn-out"><i class="ti ti-arrow-right"></i> {{ tr('رجوع') }}</NuxtLink>
    </div>

    <div v-if="loadErr" class="reload-bar">
      <i class="ti ti-alert-triangle"></i> {{ tr(loadErr) }}
      <button class="btn btn-out btn-sm" @click="loadCatalog"><i class="ti ti-refresh"></i> {{ tr('إعادة المحاولة') }}</button>
    </div>

    <div v-if="done" class="ok-box" style="max-width:520px">
      <i class="ti ti-circle-check" style="font-size:26px"></i>
      <div style="margin-top:8px">{{ tr('تم فتح التذكرة') }} <b>{{ done }}</b></div>
      <div v-if="queued" class="q-note"><i class="ti ti-cloud-upload"></i> {{ tr('تم فتح التذكرة على الجهاز — ستُرفع للسيرفر ويُعطى رقمها النهائي عند عودة الاتصال') }}</div>
      <div style="margin-top:12px;display:flex;gap:10px;justify-content:center">
        <NuxtLink v-if="doneId" :to="'/admin/tickets/' + doneId" class="btn btn-accent">{{ tr('فتح صفحة التذكرة') }}</NuxtLink>
        <NuxtLink to="/admin/tickets" class="btn btn-out">{{ tr('عرض التذاكر') }}</NuxtLink>
        <button class="btn btn-out" @click="reset">{{ tr('تذكرة أخرى') }}</button>
      </div>
    </div>

    <div v-else class="cols">
      <div>
        <!-- الزبون -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-user" style="color:var(--accent)"></i> {{ tr('بيانات الزبون') }}</div>
          <div class="g2 frow">
            <div style="position:relative">
              <label>{{ tr('رقم الجوال *') }}</label>
              <PhoneInput v-model="f.customer_phone" placeholder="5xxxxxxxx" @update:modelValue="suggest" />
              <div v-if="matches.length" class="sugg">
                <button v-for="c in matches" :key="c.id" type="button" @mousedown.prevent="pick(c)">
                  <b>{{ c.name }}</b> <span dir="ltr">{{ c.phone }}</span> <small>{{ c.tickets_count }} {{ tr('تذاكر سابقة') }}</small>
                </button>
              </div>
            </div>
            <div><label>{{ tr('اسم الزبون *') }}</label><input v-model="f.customer_name" :placeholder="tr('الاسم الكامل')" /></div>
          </div>
          <div v-if="picked" class="okline"><i class="ti ti-user-check"></i> {{ tr('عميل مسجّل') }} — <NuxtLink :to="'/admin/customers/' + picked.id" class="link" target="_blank">{{ tr('فتح ملفه وسجل أجهزته') }}</NuxtLink></div>
        </div>

        <!-- الجهاز -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-device-mobile" style="color:var(--accent)"></i> {{ tr('بيانات الجهاز') }}</div>
          <div class="g3 frow">
            <div><label>{{ tr('نوع الجهاز') }}</label>
              <select v-model="f.device_type"><option v-for="(l, k) in deviceTypes" :key="k" :value="k">{{ tr(l) }}</option></select>
            </div>
            <div><label>{{ tr('الماركة') }}</label>
              <select v-model="f.make" @change="onMake"><option v-for="m in cat.makes" :key="m.id" :value="m.name">{{ m.name }}</option></select>
            </div>
            <div><label>{{ tr('الموديل') }}</label>
              <select v-model="f.model" @change="autoPrice"><option v-for="md in models" :key="md.id" :value="md.name">{{ md.name }}</option></select>
            </div>
          </div>
          <div class="g2 frow">
            <div><label>IMEI</label><input v-model="f.imei" placeholder="356..." dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
            <div><label>{{ tr('الرقم التسلسلي (Serial)') }}</label><input v-model="f.serial" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
          </div>
          <div class="frow">
            <label>{{ tr('أنواع الأعطال') }} <span class="muted" style="font-weight:400">({{ tr('يمكن اختيار أكثر من عطل') }})</span></label>
            <MultiSelect v-model="f.repair_type_ids" :options="cat.repair_types" :placeholder="tr('— اختر الأعطال —')" :search-placeholder="tr('بحث عن عطل…')" :empty-text="tr('لا نتائج')" @change="autoPrice" />
          </div>
          <div class="g2 frow">
            <div><label>{{ tr('كلمة مرور الجهاز (اختياري)') }}</label>
              <input v-model="f.device_password" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" :placeholder="tr('تُحفظ مشفّرة ولا تظهر إلا عند الطلب')" />
            </div>
          </div>
          <div class="frow"><label>{{ tr('وصف المشكلة') }}</label><textarea v-model="f.description" rows="2" :placeholder="tr('مثال: الشاشة مكسورة واللمس لا يعمل...')"></textarea></div>
        </div>

        <!-- حالة الجهاز عند الاستلام -->
        <div class="panel">
          <div class="sec-t"><i class="ti ti-checklist" style="color:var(--accent)"></i> {{ tr('حالة الجهاز عند الاستلام') }}</div>
          <div class="g2 frow">
            <div v-for="c in condFields" :key="c.key">
              <label>{{ tr(c.label) }}</label>
              <select v-model="cond[c.key]"><option value="">{{ tr('— لم يُفحص —') }}</option><option v-for="o in c.opts" :key="o" :value="o">{{ tr(o) }}</option></select>
            </div>
          </div>
          <div class="frow"><label>{{ tr('ملاحظات الحالة (خدوش، كسور...)') }}</label><input v-model="cond.note" /></div>

          <div class="mini-t">{{ tr('الملحقات المستلمة مع الجهاز') }}</div>
          <div class="accs">
            <label v-for="a in accOptions" :key="a" class="accchip" :class="{ on: f.accessories.includes(a) }">
              <input type="checkbox" :value="a" v-model="f.accessories" hidden />{{ tr(a) }}
            </label>
            <input v-model="accExtra" :placeholder="tr('أخرى... (اضغط إدخال)')" style="width:150px" @keyup.enter="addAcc" />
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="sec-t"><i class="ti ti-receipt" style="color:var(--accent)"></i> {{ tr('السعر والإسناد') }}</div>
        <div class="g2 frow">
          <div><label>{{ tr('التقدير الأولي') }}</label><input v-model.number="f.estimated_cost" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" @input="priceEdited = true" /></div>
          <div><label>{{ tr('السعر المتفق عليه') }}</label><input v-model.number="f.price" type="number" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" /></div>
        </div>
        <div class="frow"><label>{{ tr('موعد التسليم المتوقع') }}</label><input v-model="f.promised_at" type="date" dir="ltr" /></div>
        <div class="frow"><label>{{ tr('الضمان (أشهر)') }}</label>
          <select v-model.number="f.warranty_months"><option :value="6">{{ tr('٦ أشهر') }}</option><option :value="3">{{ tr('٣ أشهر') }}</option><option :value="1">{{ tr('شهر واحد') }}</option><option :value="0">{{ tr('بدون') }}</option></select>
        </div>
        <div class="frow"><label>{{ tr('إسناد إلى فني') }}</label>
          <select v-model="f.technician_id"><option :value="null">{{ tr('— بدون —') }}</option><option v-for="t in techs" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        </div>
        <div class="frow"><label>{{ tr('الحالة') }}</label>
          <select v-model="f.status"><option v-for="st in statuses" :key="st.key" :value="st.key">{{ tr(st.label) }}</option></select>
        </div>
        <div class="hint"><i class="ti ti-bell"></i> {{ tr('سيصل الزبون رسالة عند فتح التذكرة وكل تغيير للحالة (عند تفعيل واتساب).') }}</div>
        <div v-if="err" class="err">{{ err }}</div>
        <button class="btn btn-accent btn-block btn-lg" :disabled="busy" @click="submit"><i class="ti ti-check"></i> {{ tr('فتح التذكرة') }}</button>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t: tr, isEn } = useI18n()
const cat = ref({ makes: [], repair_types: [] })
const techs = ref([])
const statuses = ref([])
const done = ref('')
const doneId = ref(null)
const queued = ref(false)
const err = ref('')
const busy = ref(false)
const matches = ref([])
const picked = ref(null)
const accExtra = ref('')
let debounce = null

const deviceTypes = { phone: 'جوال', tablet: 'تابلت', laptop: 'لابتوب', watch: 'ساعة ذكية', other: 'أخرى' }
const condFields = [
  { key: 'screen', label: 'الشاشة', opts: ['سليمة', 'خدوش', 'مكسورة'] },
  { key: 'body', label: 'الهيكل', opts: ['سليم', 'خدوش', 'مكسور'] },
  { key: 'camera', label: 'الكاميرا', opts: ['سليمة', 'لا تعمل'] },
  { key: 'power', label: 'التشغيل', opts: ['يعمل', 'لا يعمل'] },
]
const accOptions = ['شاحن', 'كيبل', 'كفر', 'شريحة', 'ذاكرة', 'علبة']

const blank = () => ({ customer_id: null, customer_name: '', customer_phone: '', device_type: 'phone', make: '', model: '', imei: '', serial: '', repair_type_ids: [], description: '', device_password: '', estimated_cost: null, price: null, promised_at: '', warranty_months: 6, technician_id: null, status: 'received', accessories: [] })
const f = ref(blank())
const cond = ref({ screen: '', body: '', camera: '', power: '', note: '' })

const models = computed(() => cat.value.makes.find(m => m.name === f.value.make)?.models || [])
function onMake() { f.value.model = models.value[0]?.name || ''; autoPrice() }
function addAcc() { if (accExtra.value.trim()) { f.value.accessories.push(accExtra.value.trim()); accExtra.value = '' } }

function suggest() {
  picked.value = null; f.value.customer_id = null
  clearTimeout(debounce)
  // نبحث بآخر الأرقام المهمة حتى نطابق السجلات المحلية (05..) والدولية (‎+966..‎) معاً
  const tail = (f.value.customer_phone || '').replace(/\D/g, '').replace(/^0+/, '').slice(-9)
  if (tail.length < 3) { matches.value = []; return }
  debounce = setTimeout(async () => {
    try { matches.value = (await api('/dashboard/customers?search=' + encodeURIComponent(tail))).slice(0, 5) } catch { matches.value = [] }
  }, 300)
}
function pick(c) {
  picked.value = c
  f.value.customer_id = c.id
  f.value.customer_name = c.name
  f.value.customer_phone = c.phone
  matches.value = []
}

const priceEdited = ref(false) // لا نكتب فوق سعر عدّله المستخدم يدوياً

// يجمع أسعار كل الأعطال المختارة ليعطي التقدير الأولي الكلي
async function autoPrice() {
  const make = cat.value.makes.find(m => m.name === f.value.make)
  const model = models.value.find(x => x.name === f.value.model)
  const ids = f.value.repair_type_ids
  if (!ids.length) { if (!priceEdited.value) f.value.estimated_cost = null; return }
  if (!make) return
  try {
    const quotes = await Promise.all(ids.map(id =>
      api('/site/quote', { method: 'POST', body: { make_id: make.id, car_model_id: model?.id || null, repair_type_id: id } }).catch(() => null)
    ))
    const found = quotes.filter(q => q?.found)
    if (!priceEdited.value && found.length) {
      f.value.estimated_cost = found.reduce((sum, q) => sum + Number(q.price || 0), 0)
      // أقصر ضمان بين الأعطال المسعّرة
      f.value.warranty_months = Math.min(...found.map(q => Number(q.warranty_months ?? 6)))
    }
  } catch {}
}

async function submit() {
  err.value = ''
  if (!f.value.customer_name || !f.value.customer_phone) { err.value = tr('اسم الزبون ورقم الجوال مطلوبان'); return }
  busy.value = true
  try {
    addAcc()
    const condition = Object.fromEntries(Object.entries(cond.value).filter(([, v]) => v))
    const body = { ...f.value, condition, promised_at: f.value.promised_at || null }
    if (!body.device_password) delete body.device_password
    const offline = {
      repair_type: f.value.repair_type_ids.map(id => cat.value.repair_types.find(x => x.id === id)?.name).filter(Boolean).join('، ') || null,
      technician: techs.value.find(x => x.id === f.value.technician_id)?.name || null,
    }
    const r = await api('/dashboard/tickets', { method: 'POST', body, offline })
    done.value = r.ticket_no
    doneId.value = r._queued ? null : r.id
    queued.value = !!r._queued
  } catch (e) { err.value = e?.data?.message || tr('تعذّر حفظ التذكرة — تحقق من الحقول') } finally { busy.value = false }
}
function reset() { done.value = ''; doneId.value = null; queued.value = false; picked.value = null; f.value = blank(); cond.value = { screen: '', body: '', camera: '', power: '', note: '' }; onMake() }

const loadErr = ref('')

// محاولة متكررة: السيرفر قد يتأخر لحظة الإقلاع/التحميل المسبق فلا تُترك القوائم فارغة
async function tryGet(path, tries = 3) {
  for (let i = 0; i < tries; i++) {
    try { return await api(path) } catch (e) {
      if (i === tries - 1) throw e
      await new Promise(r => setTimeout(r, 700 * (i + 1)))
    }
  }
}

async function loadCatalog() {
  loadErr.value = ''
  try {
    const c = await tryGet('/site/catalog')
    cat.value = c
    if (!f.value.make) { f.value.make = c.makes[0]?.name || ''; onMake() }
  } catch { loadErr.value = 'تعذّر تحميل الماركات — تحقق من الاتصال' }
}

onMounted(async () => {
  // كل نداء مستقل: فشل واحد لا يُفرّغ البقية
  await loadCatalog()
  try { techs.value = await tryGet('/dashboard/technicians') } catch {}
  try { statuses.value = (await tryGet('/dashboard/tickets')).statuses.filter(s => !['cancelled', 'rejected', 'unrepairable', 'delivered'].includes(s.key)) } catch {}
  // احتياطي: لو بقيت الحالات فارغة استعمل الافتراضية حتى لا يتعطّل الحفظ
  if (!statuses.value.length) statuses.value = [{ key: 'received', label: 'مُستلم' }, { key: 'inspecting', label: 'قيد الفحص' }]
})
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.cols { display: grid; grid-template-columns: 1.6fr 1fr; gap: 16px; align-items: start; }
.cols > div > .panel { margin-bottom: 16px; }
.g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.hint { background: #E6F1FB; border-radius: 9px; padding: 11px 13px; font-size: 12px; color: var(--accent-d); display: flex; gap: 8px; margin: 4px 0 14px; }
.q-note { margin-top: 10px; font-size: 12.5px; color: #8A5300; background: #FFF4E0; border-radius: 8px; padding: 8px 10px; display: flex; gap: 6px; align-items: center; justify-content: center; }
.sugg { position: absolute; top: 100%; inset-inline: 0; background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 8px 22px rgba(20,33,58,.12); z-index: 20; overflow: hidden; }
.sugg button { display: block; width: 100%; text-align: start; padding: 9px 13px; border: none; background: none; cursor: pointer; font-family: inherit; font-size: 13px; border-bottom: 1px solid var(--line-2); }
.sugg button:hover { background: var(--bg); }
.sugg small { color: var(--muted); }
.okline { background: #E4F6EF; color: var(--green); border-radius: 9px; padding: 9px 13px; font-size: 12.5px; display: flex; gap: 7px; align-items: center; }
.mini-t { font-size: 12.5px; font-weight: 700; color: var(--muted); margin: 6px 0 8px; }
.accs { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.accchip { border: 1px solid var(--line); border-radius: 18px; padding: 7px 14px; font-size: 13px; cursor: pointer; user-select: none; }
.accchip.on { background: var(--accent); border-color: var(--accent); color: #fff; }
.reload-bar { display: flex; align-items: center; gap: 10px; background: #FBE9E8; color: #A32020; border: 1px solid #F3C4C4; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 600; margin-bottom: 14px; }
.reload-bar .btn-sm { margin-inline-start: auto; padding: 6px 12px; font-size: 12.5px; }
@media (max-width: 900px) { .cols, .g3 { grid-template-columns: 1fr; } }
</style>
