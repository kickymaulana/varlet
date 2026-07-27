<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Snackbar, Dialog } from '@varlet/ui'

interface Prize {
  id: number; nama: string; deskripsi: string | null; urutan: number; is_drawn: boolean
}

const page = usePage()
const pageProps = page.props as any
const baseUrl = pageProps.app_url || ''
const csrfToken = pageProps.csrf_token || ''

const props = defineProps<{ prizes: any; filters?: { search?: string } }>()
const searchVal = ref(props.filters?.search || '')

const search = () => { router.get(baseUrl + '/admin/lucky-draw/prizes', { search: searchVal.value }) }

const showForm = ref(false)
const editingId = ref<number | null>(null)
const formNama = ref('')
const formDeskripsi = ref('')

const openCreate = () => {
  editingId.value = null
  formNama.value = ''
  formDeskripsi.value = ''
  showForm.value = true
}

const openEdit = (prize: Prize) => {
  editingId.value = prize.id
  formNama.value = prize.nama
  formDeskripsi.value = prize.deskripsi || ''
  showForm.value = true
}

const submitForm = async () => {
  if (!formNama.value.trim()) {
    Snackbar.warning('Nama hadiah harus diisi')
    return
  }

  const url = editingId.value
    ? `${baseUrl}/admin/lucky-draw/prizes/${editingId.value}`
    : `${baseUrl}/admin/lucky-draw/prizes`

  const method = editingId.value ? 'PUT' : 'POST'

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({ nama: formNama.value, deskripsi: formDeskripsi.value }),
  })

  if (res.ok) {
    Snackbar.success(editingId.value ? 'Hadiah diperbarui' : 'Hadiah ditambahkan')
    window.location.reload()
  } else {
    Snackbar.error('Gagal menyimpan')
  }
}

const confirmDelete = (prize: Prize) => {
  Dialog({
    title: 'Hapus Hadiah?',
    message: `Hapus "${prize.nama}"?`,
    onConfirm: async () => {
      const res = await fetch(`${baseUrl}/admin/lucky-draw/prizes/${prize.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': (window as any).csrf_token || '' },
      })
      if (res.ok) {
        Snackbar.success('Hadiah dihapus')
        window.location.reload()
      }
    },
  })
}
</script>

<template>
  <Head title="Daftar Hadiah - Lucky Draw" />

  <div class="android-layout">
    <header class="top-app-bar">
      <a :href="baseUrl + '/admin/dashboard'" class="back-button">
        <var-icon name="chevron-left" :size="24" color="#0f172a" />
      </a>
      <h1 class="app-bar-title">Daftar Hadiah</h1>
      <var-button text round @click="openCreate">
        <var-icon name="plus" :size="22" color="#4f46e5" />
      </var-button>
    </header>

    <main class="android-content">
      <div class="hero-card">
        <div class="hero-text">
          <h3>Hadiah Lucky Draw 🎁</h3>
          <p>Atur daftar hadiah yang akan diundi. Urutan sesuai urutan tampil.</p>
        </div>
        <var-icon name="gift" class="hero-icon" />
      </div>

      <!-- Form -->
      <div v-if="showForm" class="form-card">
        <h3>{{ editingId ? 'Edit Hadiah' : 'Tambah Hadiah Baru' }}</h3>
        <div class="form-inline">
          <var-input v-model="formNama" label="Nama Hadiah" placeholder="Contoh: TV 42 inch" />
          <var-input v-model="formDeskripsi" label="Deskripsi (opsional)" placeholder="Sponsor: ..." />
          <div class="form-actions">
            <var-button text @click="showForm = false">Batal</var-button>
            <var-button type="primary" @click="submitForm">Simpan</var-button>
          </div>
        </div>
      </div>

      <!-- Search -->
      <var-input v-model="searchVal" placeholder="Cari hadiah..." @keyup.enter="search">
        <template #append-icon><var-button size="small" @click="search"><var-icon name="magnify" :size="16" /></var-button></template>
      </var-input>

      <!-- List Prizes -->
      <div v-if="prizes.data?.length === 0 && !showForm" class="empty-card">
        <var-icon name="gift" :size="48" color="#cbd5e1" />
        <p>Belum ada hadiah. Tambahkan hadiah untuk mulai undian.</p>
        <var-button type="primary" @click="openCreate">Tambah Hadiah</var-button>
      </div>

      <div v-else class="prize-list">
        <div v-for="(prize, i) in (prizes.data || prizes)" :key="prize.id" class="prize-card" :class="{ drawn: prize.is_drawn }">
          <div class="prize-number">{{ i + 1 }}</div>
          <div class="prize-info">
            <h3>{{ prize.nama }}</h3>
            <p v-if="prize.deskripsi">{{ prize.deskripsi }}</p>
            <var-chip v-if="prize.is_drawn" size="small" type="success">Sudah Diundi</var-chip>
            <var-chip v-else size="small" type="warning">Belum Diundi</var-chip>
          </div>
          <div class="prize-actions">
            <var-button size="small" text round @click="openEdit(prize)">
              <var-icon name="pencil" :size="16" color="#d97706" />
            </var-button>
            <var-button size="small" text round @click="confirmDelete(prize)">
              <var-icon name="delete" :size="16" color="#ef4444" />
            </var-button>
          </div>
        </div>
      </div>

      <a :href="baseUrl + '/admin/lucky-draw/draw'" class="start-draw-btn">
        <var-icon name="dice-5" :size="20" />
        Mulai Undian
      </a>

      <!-- Pagination -->
      <div v-if="prizes.last_page" class="pagination">
        <a v-if="prizes.prev_page_url" :href="prizes.prev_page_url" class="page-btn">Sebelumnya</a>
        <span class="page-info">{{ prizes.from }}–{{ prizes.to }} dari {{ prizes.total }}</span>
        <a v-if="prizes.next_page_url" :href="prizes.next_page_url" class="page-btn">Selanjutnya</a>
      </div>
    </main>
  </div>
</template>

<style scoped>
.android-layout { display: flex; flex-direction: column; min-height: 100vh; background: #f8fafc; font-family: Roboto, sans-serif; color: #1e293b; }
.top-app-bar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; z-index: 10; }
.back-button { display: flex; text-decoration: none; }
.app-bar-title { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; }
.android-content { flex: 1; padding: 20px; display: flex; flex-direction: column; gap: 16px; max-width: 600px; margin: 0 auto; width: 100%; }
.hero-card { background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 20px; padding: 20px; color: #fff; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 25px -5px rgba(79,70,229,0.35); }
.hero-text h3 { margin: 0 0 6px; font-size: 16px; font-weight: 700; }
.hero-text p { margin: 0; font-size: 12px; opacity: 0.9; }
.hero-icon { font-size: 46px !important; opacity: 0.25; }
.form-card { background: #fff; border-radius: 20px; padding: 20px; border: 1px solid #f1f5f9; }
.form-card h3 { margin: 0 0 12px; font-size: 14px; }
.form-inline { display: flex; flex-direction: column; gap: 12px; }
.form-actions { display: flex; justify-content: flex-end; gap: 8px; }
.empty-card { background: #fff; border-radius: 20px; padding: 48px 32px; text-align: center; color: #94a3b8; border: 1px dashed #cbd5e1; display: flex; flex-direction: column; align-items: center; gap: 12px; }
.prize-list { display: flex; flex-direction: column; gap: 10px; }
.prize-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; padding: 14px 16px; display: flex; align-items: center; gap: 14px; transition: transform 0.2s; }
.prize-card.drawn { opacity: 0.7; border-color: #bbf7d0; background: #f0fdf4; }
.prize-number { width: 32px; height: 32px; border-radius: 50%; background: #e0e7ff; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #4f46e5; flex-shrink: 0; }
.prize-info { flex: 1; }
.prize-info h3 { margin: 0; font-size: 14px; font-weight: 600; }
.prize-info p { margin: 2px 0 4px; font-size: 12px; color: #64748b; }
.prize-actions { display: flex; gap: 4px; flex-shrink: 0; }
.start-draw-btn { display: flex; align-items: center; justify-content: center; gap: 8px; background: #4f46e5; color: #fff; padding: 14px; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; }
</style>
