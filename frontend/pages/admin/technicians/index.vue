<template>
  <DashShell active="technicians">
    <div class="ph">
      <div><div class="page-title">{{ tr('الفنيون') }}</div><p class="page-sub">{{ tr('الفريق والتذاكر المسندة') }}</p></div>
      <NuxtLink to="/admin/technicians/new" class="btn btn-accent"><i class="ti ti-user-plus"></i> {{ tr('إضافة فني') }}</NuxtLink>
    </div>

    <table>
      <thead><tr><th>{{ tr('الفني') }}</th><th>{{ tr('التخصص') }}</th><th>{{ tr('الجوال') }}</th><th>{{ tr('البريد') }}</th><th>{{ tr('تذاكر مفتوحة') }}</th><th>{{ tr('هذا الشهر') }}</th><th>{{ tr('الحالة') }}</th><th></th></tr></thead>
      <tbody>
        <tr v-for="t in techs" :key="t.id">
          <td><div class="who"><div class="tav">{{ initials(t.name) }}</div><b>{{ t.name }}</b></div></td>
          <td>{{ t.specialty || tr('فني') }}</td>
          <td dir="ltr" style="text-align:right">{{ t.phone || '—' }}</td>
          <td dir="ltr" style="text-align:right">{{ t.email || '—' }}</td>
          <td><b>{{ t.open }}</b></td>
          <td><b>{{ t.month }}</b></td>
          <td><span class="pill" :class="t.active ? 'pill-g' : 'pill-gray'">{{ t.active ? tr('نشط') : tr('متوقف') }}</span></td>
          <td style="text-align:left;white-space:nowrap">
            <NuxtLink :to="'/admin/technicians/new?edit=' + t.id" class="act" :title="tr('تعديل')"><i class="ti ti-edit"></i></NuxtLink>
            <button class="act danger" :title="tr('حذف')" @click="remove(t)"><i class="ti ti-trash"></i></button>
          </td>
        </tr>
        <tr v-if="!techs.length"><td colspan="8" class="empty">{{ tr('لا يوجد فنيون') }}</td></tr>
      </tbody>
    </table>
  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { confirmDlg } = useConfirm()
const { t: tr } = useI18n()
const techs = ref([])

const initials = (name) => (name || '').trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('')

async function load() { techs.value = await api('/dashboard/technicians') }
async function remove(t) {
  if (!await confirmDlg(tr('حذف الفني'), t.name)) return
  await api('/dashboard/technicians/' + t.id, { method: 'DELETE' }); await load()
}
onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.who { display: flex; align-items: center; gap: 10px; }
.tav { width: 36px; height: 36px; border-radius: 50%; background: #E6F1FB; color: var(--accent-d); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
a.act { text-decoration: none; }
</style>
