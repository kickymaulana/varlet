<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Snackbar, Dialog } from '@varlet/ui'

interface Participant {
  id: number; nomor_induk: string; nama_lengkap: string; departemen: string
  lokasi_kerja: string; nomor_hp: string | null; nomor_kupon: string | null
  is_present: boolean; eligible_for_draw: boolean
}

const page = usePage()
const pp = page.props as any
const baseUrl = pp.app_url || ''
const csrf = pp.csrf_token || ''

const props = defineProps<{
  participants: any
  filters: { search: string | null }
}>()

const searchInput = ref(props.filters.search || '')
const showForm = ref(false)
const editing = ref<Participant | null>(null)

const form = ref({
  nomor_induk: '', nama_lengkap: '', departemen: '', lokasi_kerja: '', nomor_hp: '', eligible_for_draw: true,
})

const openCreate = () => {
  editing.value = null
  form.value = { nomor_induk: '', nama_lengkap: '', departemen: '', lokasi_kerja: '', nomor_hp: '', eligible_for_draw: true }
  showForm.value = true
}

const openEdit = (p: Participant) => {
  editing.value = p
  form.value = {
    nomor_induk: p.nomor_induk, nama_lengkap: p.nama_lengkap,
    departemen: p.departemen, lokasi_kerja: p.lokasi_kerja,
    nomor_hp: p.nomor_hp || '', eligible_for_draw: p.eligible_for_draw,
  }
  showForm.value = true
}

const submitForm = async () => {
  if (!form.value.nama_lengkap.trim() || !form.value.nomor_induk.trim()) {
    Snackbar.warning('Nama dan NIK harus diisi'); return
  }

  const url = editing.value
    ? `${baseUrl}/admin/participants/${editing.value.id}`
    : `${baseUrl}/admin/participants`
  const method = editing.value ? 'PUT' : 'POST'

  const res = await fetch(url, {
    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
    body: JSON.stringify(form.value),
  })

  if (res.ok || res.redirected) { window.location.reload() }
  else { Snackbar.error('Gagal menyimpan') }
}

const confirmDelete = (p: Participant) => {
  Dialog({
    title: 'Hapus?', message: `Hapus "${p.nama_lengkap}"?`,
    onConfirm: async () => {
      const res = await fetch(`${baseUrl}/admin/participants/${p.id}`, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf },
      })
      if (res.ok) { Snackbar.success('Dihapus'); window.location.reload() }
    },
  })
}

const search = () => {
  window.location.href = `${baseUrl}/admin/participants?search=${encodeURIComponent(searchInput.value)}`
}
</script>

<template>
  <Head title="Peserta - Admin" />
  <div class="layout">
    <header class="top-bar">
      <a :href="baseUrl + '/admin/dashboard'" class="back"><var-icon name="chevron-left" :size="24" color="#0f172a" /></a>
      <h1>👥 Peserta</h1>
      <var-button text round @click="openCreate"><var-icon name="plus" :size="22" color="#4f46e5" /></var-button>
    </header>
    <main class="content">
      <!-- Search -->
      <div class="search-box">
        <var-input v-model="searchInput" placeholder="Cari NIK atau Nama..." @keyup.enter="search">
          <template #append-icon><var-button size="small" @click="search"><var-icon name="magnify" :size="16" /></var-button></template>
        </var-input>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="card form-card">
        <h3>{{ editing ? 'Edit Peserta' : 'Tambah Peserta' }}</h3>
        <div class="form-grid">
          <var-input v-model="form.nomor_induk" label="NIK *" placeholder="Contoh: D240027" />
          <var-input v-model="form.nama_lengkap" label="Nama Lengkap *" placeholder="Nama" />
          <var-input v-model="form.departemen" label="Departemen" placeholder="Dept" />
          <var-input v-model="form.lokasi_kerja" label="Lokasi Kerja" placeholder="Contoh: Dalu 1" />
          <var-input v-model="form.nomor_hp" label="No HP" placeholder="08..." />
          <div class="switch-row">
            <span class="switch-label">Dapat Undian</span>
            <var-switch v-model="form.eligible_for_draw" :true-value="true" :false-value="false" />
          </div>
        </div>
        <div class="form-actions">
          <var-button text @click="showForm = false">Batal</var-button>
          <var-button type="primary" @click="submitForm">{{ editing ? 'Simpan' : 'Tambah' }}</var-button>
        </div>
      </div>

      <!-- Table -->
      <div class="card table-wrap">
        <table>
          <thead><tr><th>NIK</th><th>Nama</th><th>Dept</th><th>Hadir</th><th>Undian</th><th></th></tr></thead>
          <tbody>
            <tr v-for="p in participants.data" :key="p.id">
              <td class="mono">{{ p.nomor_induk }}</td>
              <td>{{ p.nama_lengkap }}</td>
              <td>{{ p.departemen }}</td>
              <td><var-chip :type="p.is_present ? 'success' : 'default'" size="mini">{{ p.is_present ? 'Ya' : 'Tidak' }}</var-chip></td>
              <td><var-chip :type="p.eligible_for_draw ? 'warning' : 'default'" size="mini">{{ p.eligible_for_draw ? 'Ya' : 'Tidak' }}</var-chip></td>
              <td class="actions">
                <var-button size="small" text round @click="openEdit(p)"><var-icon name="pencil" :size="16" color="#d97706" /></var-button>
                <var-button size="small" text round @click="confirmDelete(p)"><var-icon name="delete" :size="16" color="#ef4444" /></var-button>
              </td>
            </tr>
            <tr v-if="!participants.data.length"><td colspan="6" class="empty">Tidak ada data</td></tr>
          </tbody>
        </table>
        <!-- Pagination -->
        <div class="pagination">
          <a v-if="participants.prev_page_url" :href="participants.prev_page_url + '&search=' + searchInput" class="page-btn">Sebelumnya</a>
          <span class="page-info">{{ participants.from }}–{{ participants.to }} dari {{ participants.total }}</span>
          <a v-if="participants.next_page_url" :href="participants.next_page_url + '&search=' + searchInput" class="page-btn">Selanjutnya</a>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.layout { display: flex; flex-direction: column; min-height: 100vh; background: #f8fafc; font-family: Roboto, sans-serif; }
.top-bar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; }
.top-bar h1 { margin: 0; font-size: 16px; }
.back { display: flex; text-decoration: none; }
.content { flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 16px; max-width: 900px; margin: 0 auto; width: 100%; }
.search-box { background: #fff; border-radius: 12px; padding: 4px; }
.card { background: #fff; border-radius: 20px; padding: 16px; border: 1px solid #f1f5f9; }
.card h3 { margin: 0 0 12px; font-size: 14px; }
.form-card { padding: 20px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.switch-row { display: flex; align-items: center; gap: 10px; padding-top: 8px; }
.switch-label { font-size: 13px; color: #64748b; }
.form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; padding: 8px 12px; color: #94a3b8; font-weight: 600; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
td { padding: 10px 12px; border-bottom: 1px solid #f8fafc; }
.mono { font-family: monospace; font-size: 12px; }
.actions { display: flex; gap: 2px; }
.empty { text-align: center; padding: 24px; color: #94a3b8; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 16px; padding-top: 12px; font-size: 13px; }
.page-btn { padding: 6px 16px; border-radius: 8px; background: #f1f5f9; color: #0f172a; text-decoration: none; font-weight: 600; }
.page-btn:hover { background: #e2e8f0; }
.page-info { color: #64748b; }
</style>
