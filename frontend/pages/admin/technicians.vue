<template>
  <DashShell active="technicians">
    <div class="ph">
      <div><div class="page-title">الفنيون</div><p class="page-sub">الفريق والتذاكر المسندة</p></div>
      <button class="btn btn-accent" @click="openNew"><i class="ti ti-user-plus"></i> إضافة فني</button>
    </div>

    <div class="grid">
      <div v-for="t in techs" :key="t.id" class="panel card">
        <div class="top">
          <div class="tav">{{ t.name.slice(0,2) }}</div>
          <div style="flex:1"><b>{{ t.name }}</b><div class="muted">{{ t.specialty || 'فني' }}</div></div>
          <span class="pill" :class="t.active ? 'pill-g' : 'pill-gray'">{{ t.active ? 'نشط' : 'متوقف' }}</span>
        </div>
        <div class="mini">
          <div><div class="n">{{ t.open }}</div><div class="l">تذاكر مفتوحة</div></div>
          <div><div class="n">{{ t.month }}</div><div class="l">هذا الشهر</div></div>
        </div>
        <div class="acts">
          <button class="act" @click="edit(t)"><i class="ti ti-edit"></i></button>
          <button class="act danger" @click="remove(t)"><i class="ti ti-trash"></i></button>
        </div>
      </div>
      <div v-if="!techs.length" class="empty">لا يوجد فنيون</div>
    </div>

    <div v-if="form" class="ov" @click.self="form = null">
      <div class="modal">
        <div class="sec-t">{{ form.id ? 'تعديل فني' : 'فني جديد' }}</div>
        <div class="frow"><label>الاسم *</label><input v-model="form.name" /></div>
        <div class="g2 frow">
          <div><label>الجوال</label><input v-model="form.phone" dir="ltr" style="text-align:right" /></div>
          <div><label>التخصص</label><input v-model="form.specialty" placeholder="فني أول" /></div>
        </div>
        <label class="chk"><input type="checkbox" v-model="form.active" style="width:auto" /> نشط</label>
        <div v-if="err" class="err">{{ err }}</div>
        <div style="display:flex;gap:10px;margin-top:10px">
          <button class="btn btn-accent" @click="save"><i class="ti ti-check"></i> حفظ</button>
          <button class="btn btn-out" @click="form = null">إلغاء</button>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const techs = ref([])
const form = ref(null)
const err = ref('')

async function load() { techs.value = await api('/dashboard/technicians') }
function openNew() { err.value = ''; form.value = { name: '', phone: '', specialty: '', active: true } }
function edit(t) { err.value = ''; form.value = { ...t } }
async function save() {
  err.value = ''
  if (!form.value.name) { err.value = 'الاسم مطلوب'; return }
  try {
    if (form.value.id) await api('/dashboard/technicians/' + form.value.id, { method: 'PUT', body: form.value })
    else await api('/dashboard/technicians', { method: 'POST', body: form.value })
    form.value = null; await load()
  } catch { err.value = 'تعذّر الحفظ' }
}
async function remove(t) {
  if (!await confirmDlg('حذف الفني', t.name)) return
  await api('/dashboard/technicians/' + t.id, { method: 'DELETE' }); await load()
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.card { position: relative; }
.top { display: flex; align-items: center; gap: 11px; }
.tav { width: 46px; height: 46px; border-radius: 50%; background: #E6F1FB; color: var(--accent-d); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }
.muted { color: var(--muted); font-size: 12px; }
.mini { display: flex; gap: 16px; margin-top: 14px; }
.mini .n { font-size: 18px; font-weight: 700; } .mini .l { font-size: 11px; color: var(--muted); }
.acts { position: absolute; top: 16px; inset-inline-start: 16px; }
.chk { display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 4px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 440px; max-width: 92vw; }
@media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
</style>
