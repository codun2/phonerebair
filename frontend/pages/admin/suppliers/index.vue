<template>
  <DashShell active="suppliers">
    <div class="ph">
      <div><div class="page-title">{{ t('الموردون') }}</div><p class="page-sub">{{ t('مصادر قطع الغيار وبياناتهم وتوريداتهم') }}</p></div>
      <NuxtLink to="/admin/suppliers/new" class="btn btn-accent"><i class="ti ti-truck"></i> {{ t('مورّد جديد') }}</NuxtLink>
    </div>

    <div class="toolbar">
      <div class="search"><i class="ti ti-search"></i>
        <input v-model="q" :placeholder="t('بحث: اسم المورد / المسؤول / الجوال / التخصص')" @input="onSearch" />
      </div>
    </div>

    <table>
      <thead><tr><th>{{ t('المورد') }}</th><th>{{ t('المسؤول') }}</th><th>{{ t('الجوال') }}</th><th>{{ t('التخصص') }}</th><th>{{ t('القطع') }}</th><th>{{ t('الحالة') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="s in suppliers" :key="s.id" @click="navigateTo('/admin/suppliers/' + s.id)" style="cursor:pointer">
          <td><b>{{ s.name }}</b><div v-if="s.city" class="muted">{{ s.city }}</div></td>
          <td>{{ s.contact_name || '—' }}</td>
          <td dir="ltr" style="text-align:right">{{ s.phone || '—' }}</td>
          <td>{{ s.categories || '—' }}</td>
          <td>{{ s.items_count ?? 0 }}</td>
          <td><span class="pill" :class="s.active ? 'pill-g' : 'pill-gray'">{{ s.active ? t('نشط') : t('متوقف') }}</span></td>
          <td style="text-align:left;white-space:nowrap" @click.stop>
            <NuxtLink :to="'/admin/suppliers/new?edit=' + s.id" class="act" :title="t('تعديل')"><i class="ti ti-edit"></i></NuxtLink>
            <button class="act danger" :title="t('حذف')" @click="remove(s)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!suppliers.length"><td colspan="7" class="empty">{{ t('لا يوجد موردون بعد — أضف أول مورد') }}</td></tr>
      </tbody>
    </table>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t } = useI18n()
const { confirmDlg } = useConfirm()
const suppliers = ref([])
const q = ref('')
let debounce = null

async function load() {
  suppliers.value = await api('/dashboard/suppliers' + (q.value.trim() ? '?search=' + encodeURIComponent(q.value.trim()) : ''))
}
function onSearch() { clearTimeout(debounce); debounce = setTimeout(load, 350) }
async function remove(s) {
  if (!await confirmDlg(t('حذف المورد'), s.name + ' — ' + t('ستبقى القطع في المخزون ويُفكّ ربطها به فقط'))) return
  await api('/dashboard/suppliers/' + s.id, { method: 'DELETE' }); await load()
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar { margin-bottom: 14px; }
.search { position: relative; max-width: 460px; }
.search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input { width: 100%; padding-inline-start: 36px; }
.muted { color: var(--muted); font-size: 12px; }
a.act { text-decoration: none; }
</style>
