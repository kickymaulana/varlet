<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Snackbar, Dialog } from '@varlet/ui'

interface Participant {
  id: number; nomor_induk: string; nama_lengkap: string; departemen: string
  lokasi_kerja: string; nomor_hp: string | null; nomor_kupon: string | null
  is_present: boolean; eligible_for_draw: boolean; attended_at: string | null
}

const page = usePage()
const pp = page.props as any
const baseUrl = pp.app_url || ''
const csrf = pp.csrf_token || ''

const props = defineProps<{
  participants: any
  filters: { search: string | null; departemen: string | null; eligible_for_draw: string | null }
  stats: { total: number; present: number; absent: number }
}>()

const searchInput = ref(props.filters.search || '')
const departemenFilter = ref(props.filters.departemen || '')
const eligibleFilter = ref(props.filters.eligible_for_draw !== null ? String(props.filters.eligible_for_draw) : '')
const showForm = ref(false)
const editing = ref<Participant | null>(null)

const form = ref({
  nomor_induk: '', nama_lengkap: '', departemen: '', lokasi_kerja: '', nomor_hp: '', eligible_for_draw: true, is_present: false,
})

const openCreate = () => {
  editing.value = null
  form.value = { nomor_induk: '', nama_lengkap: '', departemen: '', lokasi_kerja: '', nomor_hp: '', eligible_for_draw: true, is_present: false }
  showForm.value = true
}

const openEdit = (p: Participant) => {
  editing.value = p
  form.value = {
    nomor_induk: p.nomor_induk, nama_lengkap: p.nama_lengkap,
    departemen: p.departemen, lokasi_kerja: p.lokasi_kerja,
    nomor_hp: p.nomor_hp || '', eligible_for_draw: p.eligible_for_draw,
    is_present: p.is_present,
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

const buildUrl = () => {
  const params = new URLSearchParams()
  if (searchInput.value) params.set('search', searchInput.value)
  if (departemenFilter.value) params.set('departemen', departemenFilter.value)
  if (eligibleFilter.value !== '') params.set('eligible_for_draw', eligibleFilter.value)
  return `${baseUrl}/admin/participants?${params.toString()}`
}

const search = () => {
  window.location.href = buildUrl()
}

const clearFilters = () => {
  searchInput.value = ''
  departemenFilter.value = ''
  eligibleFilter.value = ''
  window.location.href = `${baseUrl}/admin/participants`
}

const confirmResetAttendance = () => {
  Dialog({
    title: 'Reset Kehadiran?',
    message: 'Ini akan mengosongkan status hadir & nomor kupon SEMUA peserta. eligible_for_draw TIDAK berubah. Lanjutkan?',
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
    onConfirm: async () => {
      const res = await fetch(`${baseUrl}/admin/participants/reset-attendance`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }
      })
      if (res.ok) { Snackbar.success('Kehadiran direset'); window.location.reload() }
      else { Snackbar.error('Gagal reset') }
    }
  })
}
</script>

<template>
  <Head title="Peserta - Admin" />
  <div class="layout">
    <header class="top-bar">
      <a :href="baseUrl + '/admin/dashboard'" class="back"><var-icon name="chevron-left" :size="24" color="#0f172a" /></a>
      <h1>👥 Peserta</h1>
      <var-button text type="warning" @click="confirmResetAttendance" style="margin-right: 8px;">
        Reset Kehadiran
      </var-button>
      <var-button text round @click="openCreate"><var-icon name="plus" :size="22" color="#4f46e5" /></var-button>
    </header>
    <main class="content">
      <!-- Search & Filters -->
      <div class="search-box">
        <var-input v-model="searchInput" placeholder="Cari NIK atau Nama..." @keyup.enter="search">
          <template #append-icon><var-button size="small" @click="search"><var-icon name="magnify" :size="16" /></var-button></template>
        </var-input>
      </div>

      <div class="filter-row">
        <var-select v-model="departemenFilter" placeholder="Semua Departemen" style="width: 200px;" @change="search">
          <var-option value="">Semua Departemen</var-option>
          <var-option v-for="p in participants.data" :key="p.departemen" :value="p.departemen">{{ p.departemen }}</var-option>
        </var-select>
        <var-select v-model="eligibleFilter" placeholder="Semua Undian" style="width: 180px;" @change="search">
          <var-option value="">Semua Undian</var-option>
          <var-option value="true">Dapat Undian</var-option>
          <var-option value="false">Tidak Dapat Undian</var-option>
        </var-select>
        <var-button v-if="searchInput || departemenFilter || eligibleFilter !== ''" type="default" @click="clearFilters">
          <var-icon name="close" :size="16" /> Hapus Filter
        </var-button>
      </div>

      <!-- Statistics -->
      <div class="stats-row">
        <div class="stat-box total">
          <span class="stat-label">Total</span>
          <span class="stat-value">{{ stats.total }}</span>
        </div>
        <div class="stat-box present">
          <span class="stat-label">Hadir</span>
          <span class="stat-value">{{ stats.present }}</span>
        </div>
        <div class="stat-box absent">
          <span class="stat-label">Belum Hadir</span>
          <span class="stat-value">{{ stats.absent }}</span>
        </div>
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
          <div class="switch-row">
            <span class="switch-label">Sudah Check-in</span>
            <var-switch v-model="form.is_present" :true-value="true" :false-value="false" />
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
          <thead><tr><th>NIK</th><th>Nama</th><th>Dept</th><th>No HP</th><th>Kupon</th><th>Hadir</th><th>Undian</th><th>Check-in</th><th></th></tr></thead>
          <tbody>
            <tr v-for="p in participants.data" :key="p.id">
              <td class="mono">{{ p.nomor_induk }}</td>
              <td>{{ p.nama_lengkap }}</td>
              <td>{{ p.departemen }}</td>
              <td class="mono">{{ p.nomor_hp || '—' }}</td>
              <td class="mono">{{ p.nomor_kupon || '—' }}</td>
              <td><var-chip :type="p.is_present ? 'success' : 'default'" size="mini">{{ p.is_present ? 'Ya' : 'Tidak' }}</var-chip></td>
              <td><var-chip :type="p.eligible_for_draw ? 'warning' : 'default'" size="mini">{{ p.eligible_for_draw ? 'Ya' : 'Tidak' }}</var-chip></td>
              <td class="mono">{{ p.attended_at || '—' }}</td>
              <td class="actions">
                <var-button size="small" text round @click="openEdit(p)"><var-icon name="cog" :size="16" color="#d97706" /></var-button>
                <var-button size="small" text round @click="confirmDelete(p)"><var-icon name="delete" :size="16" color="#ef4444" /></var-button>
              </td>
            </tr>
            <tr v-if="!participants.data.length"><td colspan="9" class="empty">Tidak ada data</td></tr>
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
.filter-row {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
        flex-wrap: wrap;
        background: #fff;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
      }
      .stats-row {
        display: flex;
        gap: 12px;
        margin-top: 12px;
        flex-wrap: wrap;
      }
      .stat-box {
        flex: 1;
        min-width: 120px;
        background: #fff;
        border-radius: 12px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      }
      .stat-box.total { border-left: 4px solid #4f46e5; }
      .stat-box.present { border-left: 4px solid #10b981; }
      .stat-box.absent { border-left: 4px solid #ef4444; }
      .stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
      .stat-value { font-size: 24px; font-weight: 700; color: #0f172a; }
</style>
