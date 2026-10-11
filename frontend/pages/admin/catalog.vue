<template>
  <DashShell active="catalog">
    <h1 class="page-title">الماركات والموديلات</h1>
    <p class="page-sub">حدّد ماركات الجوالات وموديلاتها — تظهر في حاسبة السعر والحجز وفتح التذاكر</p>

    <div class="panel" style="max-width:520px; margin-bottom:20px;">
      <h3 class="sec-t">إضافة ماركة جديدة</h3>
      <form style="display:flex; gap:10px;" @submit.prevent="addMake">
        <input v-model="newMake" placeholder="مثال: Realme" style="flex:1;" />
        <button class="btn btn-accent" :disabled="busy || !newMake.trim()"><i class="ti ti-plus"></i> إضافة</button>
      </form>
    </div>

    <div v-if="pending" class="empty">جارِ التحميل…</div>
    <div v-else-if="!makes.length" class="empty">لا توجد ماركات بعد — أضف أول ماركة فوق</div>

    <div class="mgrid">
      <div v-for="m in makes" :key="m.id" class="mcard">
        <div class="mhead">
          <template v-if="editMake?.id === m.id">
            <div class="editrow">
              <input v-model="editMake.name" @keyup.enter="saveMake" @keyup.esc="editMake = null" />
              <button class="act ok" @click="saveMake"><i class="ti ti-check"></i></button>
              <button class="act" @click="editMake = null"><i class="ti ti-x"></i></button>
            </div>
          </template>
          <template v-else>
            <b><i class="ti ti-device-mobile"></i> {{ m.name }}</b>
            <div>
              <button class="act" @click="editMake = { id: m.id, name: m.name }"><i class="ti ti-edit"></i></button>
              <button class="act danger" @click="delMake(m)"><i class="ti ti-trash"></i></button>
            </div>
          </template>
        </div>
        <div class="models">
          <template v-for="mo in m.models" :key="mo.id">
            <span v-if="editModel?.id === mo.id" class="mo editing">
              <input v-model="editModel.name" @keyup.enter="saveModel" @keyup.esc="editModel = null" />
              <i class="ti ti-check ok" @click="saveModel"></i>
              <i class="ti ti-x" @click="editModel = null"></i>
            </span>
            <span v-else class="mo">
              {{ mo.name }}
              <i class="ti ti-edit" @click="editModel = { id: mo.id, name: mo.name }"></i>
              <i class="ti ti-x" @click="delModel(m, mo)"></i>
            </span>
          </template>
          <span v-if="!m.models.length" class="none">لا موديلات بعد</span>
        </div>
        <div class="addmo">
          <input v-model="newModels[m.id]" placeholder="موديل جديد…" @keyup.enter="addModel(m)" />
          <button type="button" class="btn btn-out" @click="addModel(m)"><i class="ti ti-plus"></i></button>
        </div>
      </div>
    </div>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()

const makes = ref([])
const newModels = reactive({})
const editMake = ref(null)
const editModel = ref(null)
const newMake = ref('')
const pending = ref(true)
const busy = ref(false)

async function load() {
  makes.value = await api('/dashboard/catalog')
  pending.value = false
}
async function addMake() {
  busy.value = true
  try { await api('/dashboard/makes', { method: 'POST', body: { name: newMake.value.trim() } }); newMake.value = ''; await load() }
  catch (e) { alert(e?.data?.message || 'الماركة موجودة مسبقاً') }
  finally { busy.value = false }
}
async function saveMake() {
  const name = editMake.value?.name?.trim(); if (!name) return
  try { await api(`/dashboard/makes/${editMake.value.id}`, { method: 'PUT', body: { name } }); editMake.value = null; await load() }
  catch (e) { alert(e?.data?.message || 'الاسم مستخدم مسبقاً') }
}
async function delMake(m) {
  if (!(await confirmDlg(`حذف ${m.name}؟`, 'سيُحذف مع كل موديلاته'))) return
  await api(`/dashboard/makes/${m.id}`, { method: 'DELETE' }); await load()
}
async function addModel(m) {
  const name = (newModels[m.id] || '').trim(); if (!name) return
  await api(`/dashboard/makes/${m.id}/models`, { method: 'POST', body: { name } })
  newModels[m.id] = ''; await load()
}
async function saveModel() {
  const name = editModel.value?.name?.trim(); if (!name) return
  await api(`/dashboard/models/${editModel.value.id}`, { method: 'PUT', body: { name } })
  editModel.value = null; await load()
}
async function delModel(m, mo) {
  if (!(await confirmDlg(`حذف موديل ${mo.name}؟`))) return
  await api(`/dashboard/models/${mo.id}`, { method: 'DELETE' }); await load()
}
onMounted(load)
</script>

<style scoped>
.mgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.mcard { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; }
.mhead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.mhead b { display: flex; align-items: center; gap: 7px; font-size: 15px; }
.editrow { display: flex; gap: 6px; width: 100%; }
.models { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 12px; }
.mo { background: var(--bg); border: 1px solid var(--line); border-radius: 8px; padding: 5px 10px; font-size: 13px; display: flex; align-items: center; gap: 6px; }
.mo i { cursor: pointer; color: var(--muted); font-size: 14px; }
.mo i:hover { color: var(--accent); }
.mo .ok { color: var(--green); }
.mo.editing input { width: 90px; border: none; padding: 2px 4px; }
.none { color: var(--muted); font-size: 12px; }
.addmo { display: flex; gap: 8px; }
.addmo input { flex: 1; }
.act.ok { color: var(--green); }
</style>
