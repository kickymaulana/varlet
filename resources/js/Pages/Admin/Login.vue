<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { Snackbar } from '@varlet/ui'
import { onMounted } from 'vue'

const page = usePage()
const props = page.props as any
const errors = props?.errors

const appUrl = props.app_url || ''

onMounted(() => {
  if (errors?.message) {
    Snackbar.error(errors.message)
  }
})
</script>

<template>
  <Head title="Admin Login - Absensi MD" />

  <div class="login-layout">
    <div class="login-card">
      <div class="logo-area">
        <var-icon name="shield-account" :size="48" color="#4f46e5" />
        <h1>Admin Panel</h1>
        <p>Login dengan SSO Mark Dynamics</p>
      </div>

      <a :href="appUrl + '/admin/redirect'" class="sso-btn">
        <var-icon name="login" :size="20" />
        Login dengan SSO
      </a>

      <a :href="appUrl + '/absensi'" class="back-link">
        <var-icon name="arrow-left" :size="16" />
        Kembali ke Absensi
      </a>
    </div>
  </div>
</template>

<style scoped>
.login-layout {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-family: Roboto, sans-serif;
  padding: 20px;
}

.login-card {
  background: #ffffff;
  border-radius: 24px;
  padding: 40px 32px;
  width: 100%;
  max-width: 380px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.logo-area h1 {
  margin: 12px 0 4px;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.logo-area p {
  margin: 0;
  font-size: 13px;
  color: #64748b;
}

.sso-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #4f46e5;
  color: #ffffff;
  padding: 14px 24px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.2s;
}

.sso-btn:hover {
  background: #4338ca;
}

.back-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
  text-decoration: none;
}

.back-link:hover {
  color: #4f46e5;
}
</style>
