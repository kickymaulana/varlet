<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { Snackbar, Dialog } from '@varlet/ui'
import { ref } from 'vue'

interface AdminUser {
  id: number
  name: string
  email: string
}

const props = defineProps<{
  admin: AdminUser
  pin: string
}>()

const pageProps = usePage().props as any
const appUrl = pageProps.app_url || ''
const csrfToken = pageProps.csrf_token || ''

const currentPin = ref(props.pin)
const newPin = ref('')
const loading = ref(false)

const updatePin = async () => {
  if (newPin.value.length !== 3) {
    Snackbar.warning('PIN harus 3 digit angka')
    return
  }

  loading.value = true
  try {
    const res = await fetch(appUrl + '/admin/pin', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ pin: newPin.value }),
    })
    const data = await res.json()
    if (res.ok) {
      currentPin.value = newPin.value
      newPin.value = ''
      Snackbar.success(data.message || 'PIN berhasil diperbarui')
    } else {
      Snackbar.error(data.message || 'Gagal memperbarui PIN')
    }
  } catch {
    Snackbar.error('Terjadi kesalahan')
  } finally {
    loading.value = false
  }
}

const logout = () => {
  Dialog({
    title: 'Logout?',
    message: 'Anda akan keluar dari admin panel.',
    onConfirm: async () => {
      const res = await fetch(appUrl + '/admin/logout', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
      })
      if (res.ok) {
        window.location.href = appUrl + '/admin/login'
      }
    },
  })
}
</script>

<template>
  <Head title="Admin Dashboard - Absensi MD" />

  <div class="dashboard-layout">
    <header class="top-bar">
      <div class="top-bar-left">
        <var-icon name="shield-account" :size="24" color="#4f46e5" />
        <h1>Admin Panel</h1>
      </div>
      <div class="top-bar-right">
        <span class="admin-name">{{ admin.name }}</span>
        <var-button size="small" text @click="logout">
          <var-icon name="logout" :size="18" color="#ef4444" />
        </var-button>
      </div>
    </header>

    <main class="dashboard-content">
      <div class="stats-row">
        <div class="stat-card">
          <var-icon name="account" :size="32" color="#4f46e5" />
          <div class="stat-info">
            <span class="stat-label">Admin</span>
            <span class="stat-value">{{ admin.name }}</span>
          </div>
        </div>
        <div class="stat-card">
          <var-icon name="lock" :size="32" color="#f59e0b" />
          <div class="stat-info">
            <span class="stat-label">PIN Saat Ini</span>
            <span class="stat-value pin-value">{{ currentPin }}</span>
          </div>
        </div>
      </div>

      <div class="pin-card">
        <div class="pin-card-header">
          <var-icon name="lock-reset" :size="28" color="#4f46e5" />
          <h2>Ganti PIN Absensi</h2>
        </div>
        <p class="pin-desc">PIN digunakan untuk verifikasi check-in di halaman absensi. Ganti secara berkala untuk keamanan.</p>

        <div class="pin-form">
          <var-input
            v-model="newPin"
            label="PIN Baru (3 digit)"
            placeholder="Contoh: 123"
            maxlength="3"
            type="password"
          />
          <var-button type="primary" :loading="loading" @click="updatePin" class="update-btn">
            <var-icon name="content-save" :size="16" />
            Simpan PIN Baru
          </var-button>
        </div>
      </div>

      <div class="nav-card">
        <a :href="appUrl + '/admin/lucky-draw/prizes'" class="nav-link">
          <var-icon name="gift" :size="20" color="#f59e0b" />
          <span>Atur Hadiah Lucky Draw</span>
          <var-icon name="chevron-right" :size="20" color="#94a3b8" />
        </a>
      </div>

      <div class="nav-card">
        <a :href="appUrl + '/admin/lucky-draw/draw'" class="nav-link">
          <var-icon name="dice-5" :size="20" color="#4f46e5" />
          <span>Lucky Draw (Undi Hadiah)</span>
          <var-icon name="chevron-right" :size="20" color="#94a3b8" />
        </a>
      </div>

      <div class="nav-card">
        <a :href="appUrl + '/undian/show'" target="_blank" class="nav-link">
          <var-icon name="television" :size="20" color="#22c55e" />
          <span>Tampilan Layar Undian</span>
          <var-icon name="chevron-right" :size="20" color="#94a3b8" />
        </a>
      </div>

      <div class="nav-card">
        <a :href="appUrl + '/absensi'" class="nav-link">
          <var-icon name="clipboard-check" :size="20" color="#4f46e5" />
          <span>Ke Halaman Absensi</span>
          <var-icon name="chevron-right" :size="20" color="#94a3b8" />
        </a>
      </div>

      <div class="nav-card logout-card">
        <a :href="appUrl + '/admin/logout'" class="nav-link logout-link">
          <var-icon name="logout" :size="20" color="#ef4444" />
          <span>Logout</span>
          <var-icon name="chevron-right" :size="20" color="#fca5a5" />
        </a>
      </div>
    </main>
  </div>
</template>

<style scoped>
.dashboard-layout {
  min-height: 100vh;
  background-color: #f8fafc;
  font-family: Roboto, sans-serif;
  color: #1e293b;
}

.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  z-index: 10;
}

.top-bar-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.top-bar-left h1 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-name {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.dashboard-content {
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.stat-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-label {
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

.stat-value {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.pin-value {
  font-family: monospace;
  letter-spacing: 3px;
  color: #f59e0b;
}

.pin-card {
  background: #ffffff;
  border-radius: 20px;
  padding: 24px 20px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pin-card-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pin-card-header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.pin-desc {
  margin: 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.4;
}

.pin-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.update-btn {
  font-weight: 600 !important;
  gap: 6px !important;
}

.nav-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #f1f5f9;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  text-decoration: none;
  color: inherit;
  font-size: 14px;
  font-weight: 500;
}

.nav-link :last-child {
  margin-left: auto;
}

.logout-card {
  border-color: #fecaca;
}

.logout-link {
  color: #dc2626;
  font-weight: 600;
}
</style>
