<template>
  <DashShell active="technicians">
    <div class="ph">
      <div><div class="page-title">{{ tr('الفنيون') }}</div><p class="page-sub">{{ tr('الفريق والتذاكر المسندة') }}</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-user-plus"></i> {{ tr('إضافة فني') }}</button>
    </div>

    <div class="grid">
      <div v-for="t in techs" :key="t.id" class="panel card">
        <div class="chead">
          <div class="tav">{{ initials(t.name) }}</div>
          <div class="info">
            <b>{{ t.name }}</b>
            <div class="muted">{{ t.specialty || tr('فني') }}</div>
          </div>
          <span class="pill" :class="t.active ? 'pill-g' : 'pill-gray'">{{ t.active ? tr('نشط') : tr('متوقف') }}</span>
        </div>

        <div v-if="t.phone || t.email" class="contact">
          <span v-if="t.phone" dir="ltr"><i class="ti ti-phone"></i> {{ t.phone }}</span>
          <span v-if="t.email" dir="ltr"><i class="ti ti-mail"></i> {{ t.email }}</span>
        </div>

        <div class="cstats">
          <div><div class="n">{{ t.open }}</div><div class="l">{{ tr('تذاكر مفتوحة') }}</div></div>
          <div><div class="n">{{ t.month }}</div><div class="l">{{ tr('هذا الشهر') }}</div></div>
        </div>

        <div class="cfoot">
          <button class="cbtn" @click="edit(t)"><i class="ti ti-edit"></i> {{ tr('تعديل') }}</button>
          <button class="cbtn danger" @click="remove(t)"><i class="ti ti-trash"></i> {{ tr('حذف') }}</button>
        </div>
      </div>
      <div v-if="!techs.length" class="empty">{{ tr('لا يوجد فنيون') }}</div>
    </div>

    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="mhead">
          <div class="mav">{{ initials(form.name) || '+' }}</div>
          <div>
            <div class="mtitle">{{ form.id ? tr('تعديل فني') : tr('إضافة فني جديد') }}</div>
            <div class="msub">{{ tr('عبّئ بيانات الفني التالية') }}</div>
          </div>
          <button class="xbtn" @click="form = null"><i class="ti ti-x"></i></button>
        </div>

        <div class="mbody">
          <div v-if="err" class="err">{{ err }}</div>

          <div class="fsec">
            <h4><i class="ti ti-user"></i> {{ tr('المعلومات الأساسية') }}</h4>
            <div class="frow"><label>{{ tr('الاسم') }} <span class="req">*</span></label><input v-model="form.name" :placeholder="tr('مثال: خالد ناصر')" /></div>
            <div class="g2">
              <div class="frow"><label>{{ tr('الجوال') }}</label><input v-model="form.phone" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" placeholder="05xxxxxxxx" /></div>
              <div class="frow"><label>{{ tr('البريد الإلكتروني') }}</label><input v-model="form.email" type="email" dir="ltr" :style="{ textAlign: isEn ? 'left' : 'right' }" placeholder="name@example.com" /></div>
            </div>
          </div>

          <div class="fsec">
            <h4><i class="ti ti-tool"></i> {{ tr('بيانات العمل') }}</h4>
            <div class="g2">
              <div class="frow">
                <label>{{ tr('التخصص') }}</label>
                <input v-model="form.specialty" list="spec-list" :placeholder="tr('اختر أو اكتب')" />
                <datalist id="spec-list">
                  <option v-for="s in specialties" :key="s" :value="s" />
                </datalist>
              </div>
              <div class="frow">
                <label>{{ tr('الحالة') }}</label>
                <div class="toggle">
                  <button type="button" :class="{ on: form.active }" @click="form.active = true">{{ tr('نشط') }}</button>
                  <button type="button" :class="{ on: !form.active }" @click="form.active = false">{{ tr('متوقف') }}</button>
                </div>
              </div>
            </div>
            <div class="frow"><label>{{ tr('ملاحظات') }}</label><textarea v-model="form.notes" rows="2" :placeholder="tr('أي ملاحظة إضافية عن الفني (اختياري)')"></textarea></div>
          </div>
        </div>

        <div class="mactions">
          <button class="btn btn-accent btn-lg" :disabled="busy" @click="save"><i class="ti ti-device-floppy"></i> {{ busy ? '...' : tr('حفظ الفني') }}</button>
          <button class="btn btn-out btn-lg" @click="form = null">{{ tr('إلغاء') }}</button>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const { t: tr, isEn } = useI18n()
const techs = ref([])
const form = ref(null)
const err = ref('')
const busy = ref(false)

const specialties = ['فني أول', 'شاشات', 'بطاريات', 'لوحات أم (مايكرو)', 'سوفتوير', 'كاميرات ومنافذ شحن', 'صيانة عامة']

const initials = (name) => (name || '').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('')

async function load() { techs.value = await api('/dashboard/technicians') }
function openNew() { err.value = ''; form.value = { name: '', phone: '', email: '', specialty: '', notes: '', active: true } }
function edit(t) { err.value = ''; form.value = { ...t } }
async function save() {
  err.value = ''
  if (!form.value.name) { err.value = tr('الاسم مطلوب'); return }
  busy.value = true
  try {
    if (form.value.id) await api('/dashboard/technicians/' + form.value.id, { method: 'PUT', body: form.value })
    else await api('/dashboard/technicians', { method: 'POST', body: form.value })
    form.value = null; await load()
  } catch { err.value = tr('تعذّر الحفظ') }
  finally { busy.value = false }
}
async function remove(t) {
  if (!await confirmDlg(tr('حذف الفني'), t.name)) return
  await api('/dashboard/technicians/' + t.id, { method: 'DELETE' }); await load()
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.card { padding: 0; overflow: hidden; }

/* header */
.chead { display: flex; align-items: center; gap: 12px; padding: 16px 16px 14px; }
.tav { width: 46px; height: 46px; border-radius: 50%; background: #E6F1FB; color: var(--accent-d); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
.info { flex: 1; min-width: 0; }
.info b { display: block; font-size: 15px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.muted { color: var(--muted); font-size: 12px; }

.contact { display: flex; flex-direction: column; gap: 5px; padding: 0 16px 12px; font-size: 12.5px; color: var(--muted); }
.contact i { color: var(--accent); margin-inline-end: 4px; }

/* stats */
.cstats { display: flex; gap: 10px; padding: 12px 16px; background: #F7F9FC; border-top: 1px solid var(--line-2); }
.cstats > div { flex: 1; text-align: center; }
.cstats .n { font-size: 20px; font-weight: 800; color: var(--ink); }
.cstats .l { font-size: 11px; color: var(--muted); margin-top: 2px; }

/* footer actions */
.cfoot { display: flex; border-top: 1px solid var(--line-2); }
.cbtn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 11px; background: #fff; border: none; font-family: inherit; font-size: 13px; color: var(--ink); cursor: pointer; }
.cbtn:first-child { border-inline-end: 1px solid var(--line-2); }
.cbtn:hover { background: #F2F5F9; }
.cbtn.danger { color: var(--accent); }

.empty { grid-column: 1 / -1; text-align: center; color: var(--muted); padding: 30px; }

/* modal */
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.45); z-index: 50; display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal { background: #fff; border-radius: 16px; width: 540px; max-width: 100%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; }
.mhead { display: flex; align-items: center; gap: 12px; padding: 18px 20px; border-bottom: 1px solid var(--line-2); }
.mav { width: 46px; height: 46px; border-radius: 12px; background: var(--accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 17px; flex-shrink: 0; }
.mtitle { font-size: 17px; font-weight: 800; }
.msub { font-size: 12.5px; color: var(--muted); }
.xbtn { margin-inline-start: auto; width: 34px; height: 34px; border-radius: 9px; border: none; background: #F2F5F9; color: var(--muted); cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }
.xbtn:hover { background: #E7EBF1; }

.mbody { padding: 18px 20px; overflow-y: auto; }
.fsec { margin-bottom: 18px; }
.fsec h4 { font-size: 13px; font-weight: 800; color: var(--accent-d); margin-bottom: 12px; display: flex; align-items: center; gap: 7px; }
.fsec h4 i { font-size: 17px; }
.frow { margin-bottom: 12px; }
.frow:last-child { margin-bottom: 0; }
.frow label { display: block; font-size: 13px; margin-bottom: 6px; color: var(--ink); }
.req { color: var(--accent); }
.g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.toggle { display: flex; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; height: 42px; }
.toggle button { flex: 1; border: none; background: #fff; font-family: inherit; font-size: 13px; cursor: pointer; color: var(--muted); }
.toggle button.on { background: var(--accent); color: #fff; font-weight: 700; }

.mactions { display: flex; gap: 10px; padding: 16px 20px; border-top: 1px solid var(--line-2); }

@media (max-width: 1000px) { .grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 640px) { .grid { grid-template-columns: 1fr; } .g2 { grid-template-columns: 1fr; } }
</style>
