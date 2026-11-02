<template>
  <DashShell active="customers">
    <div class="ph">
      <div><div class="page-title">{{ t('العملاء') }}</div><p class="page-sub">{{ t('ملفات العملاء وسجل أجهزتهم وديونهم') }}</p></div>
      <NuxtLink to="/admin/customers/new" class="btn btn-accent"><i class="ti ti-user-plus"></i> {{ t('عميل جديد') }}</NuxtLink>
    </div>

    <div class="toolbar">
      <div class="search"><i class="ti ti-search"></i>
        <input v-model="q" :placeholder="t('بحث بالاسم أو رقم الجوال')" @input="onSearch" />
      </div>
    </div>

    <table>
      <thead><tr><th>{{ t('العميل') }}</th><th>{{ t('الجوال') }}</th><th>{{ t('التذاكر') }}</th><th>{{ t('إجمالي التعامل') }}</th><th>{{ t('المدفوع') }}</th><th>{{ t('الدين') }}</th><th>{{ t('مسجّل منذ') }}</th></tr></thead>
      <tbody>
        <tr v-for="c in customers" :key="c.id" @click="navigateTo('/admin/customers/' + c.id)" style="cursor:pointer">
          <td><b>{{ c.name }}</b><div v-if="c._pending" class="muted" style="color:#8A5300"><i class="ti ti-cloud-upload"></i> {{ t('بانتظار الرفع') }}</div></td>
          <td dir="ltr" style="text-align:right">{{ c.phone }}</td>
          <td>{{ c.tickets_count ?? 0 }}</td>
          <td>{{ fmt(c.charged) }}</td>
          <td>{{ fmt(c.paid) }}</td>
          <td><b v-if="c.balance > 0" style="color:#A32020">{{ fmt(c.balance) }}</b><span v-else class="muted">—</span></td>
          <td class="muted">{{ c.created_at }}</td>
        </tr>
        <tr v-if="!customers.length"><td colspan="7" class="empty">{{ t('لا يوجد عملاء بعد — يُنشأ ملف العميل تلقائياً مع أول تذكرة') }}</td></tr>
      </tbody>
    </table>

  </DashShell>
</template>

<script setup>
const { api } = useApi()
const { t, isEn } = useI18n()
const customers = ref([])
const q = ref('')
let debounce = null

const fmt = (n) => Number(n || 0).toLocaleString(isEn.value ? 'en-US' : 'ar-EG')

async function load() {
  customers.value = await api('/dashboard/customers' + (q.value.trim() ? '?search=' + encodeURIComponent(q.value.trim()) : ''))
}
function onSearch() { clearTimeout(debounce); debounce = setTimeout(load, 350) }

onMounted(load)
</script>

<style scoped>
.ph { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.toolbar { margin-bottom: 14px; }
.search { position: relative; max-width: 420px; }
.search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input { width: 100%; padding-inline-start: 36px; }
.muted { color: var(--muted); font-size: 12px; }
.ov { position: fixed; inset: 0; background: rgba(20,30,50,.4); z-index: 50; display: flex; align-items: center; justify-content: center; }
.modal { background: #fff; border-radius: 14px; padding: 22px; width: 480px; max-width: 92vw; }
</style>
