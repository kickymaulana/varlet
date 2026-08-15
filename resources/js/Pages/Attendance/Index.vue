<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import { Snackbar } from '@varlet/ui'
import bubbleAnimation from '../../Assets/bubble-explosion.json'
import { useDarkTheme } from '../../composables/useDarkTheme'

useDarkTheme()

const page = usePage()

interface Participant {
    id: number;
    nomor_induk: string;
    nama_lengkap: string;
    departemen: string;
    lokasi_kerja: string;
    is_present: boolean;
    nomor_kupon: string | null;
}

// State Aplikasi
const nomorIndukInput = ref('')
const isLoadingSearch = ref(false)
const dataKaryawan = ref<Participant | null>(null)
const showPin = ref(false)

// Form Prosedur Check-in Inertia v3
const form = useForm({
    nomor_induk: '',
    pin: ''
})




const cariDataKaryawan = () => {
    if (!nomorIndukInput.value) {
        Snackbar.warning('Silakan masukkan Nomor Induk terlebih dahulu!')
        return
    }

    isLoadingSearch.value = true
    const baseUrl = page.props.app_url

    router.visit(`${baseUrl}/absensi`, {
        method: 'get',
        data: { search_nik: nomorIndukInput.value },
        only: ['searched_participant', 'search_error'],
        preserveState: true,
        onFinish: () => {
            isLoadingSearch.value = false

            // Mengambil data fresh hasil pencarian dari props terbaru Inertia v3
            const hasilCari = page.props.searched_participant as Participant | null
            const errorCari = page.props.search_error as string | null

            if (errorCari) {
                Snackbar.error(errorCari)
                dataKaryawan.value = null
                return
            }

            if (hasilCari) {
                // KUNCI UTAMA: Kita masukkan data terbaru ke reactive state Vue
                dataKaryawan.value = hasilCari
                form.nomor_induk = hasilCari.nomor_induk

                if (hasilCari.is_present) {
                    Snackbar.info('Anda sudah check-in sebelumnya! Menampilkan Kupon Anda.')
                } else {
                    Snackbar.success('Data ditemukan! Silakan masukkan PIN Kehadiran.')
                }
            }
        }
    })
}


const dataSukses = computed(() => page.props.flash?.success as any || null)

// KUNCI: Ubah dataKaryawan atau pesertaAktif agar otomatis mengambil data dari flash jika ada
const pesertaAktif = computed<Participant | null>(() => {
    // 1. Jika ada data sukses dari flash setelah check-in, prioritaskan ini
    if (dataSukses.value && dataSukses.value.participant) {
        return dataSukses.value.participant
    }
    // 2. Jika tidak ada flash, gunakan data pencarian lokal yang berstatus is_present
    if (dataKaryawan.value && dataKaryawan.value.is_present) {
        return dataKaryawan.value
    }
    return null
})


const eksekusiCheckIn = () => {
    if (form.pin.length !== 3) {
        Snackbar.warning('PIN harus terdiri dari 3 digit angka!')
        return
    }

    const baseUrl = page.props.app_url

    form.post(`${baseUrl}/absensi/checkin`, {
        onSuccess: () => {
            Snackbar.success('Selamat! Check-in berhasil dilakukan.')
            form.reset('pin')
            // Kita tidak perlu memanipulasi dataKaryawan.value secara manual lagi di sini,
            // karena computed 'pesertaAktif' di atas akan otomatis mendeteksi perubahan page.props.flash
        },
        onError: (errors) => {
            if(errors.pin) Snackbar.error(errors.pin)
            if(errors.nomor_induk) Snackbar.error(errors.nomor_induk)
        }
    })
}

// Fungsi Share ke WhatsApp Instan
const bagikanKeWhatsApp = () => {
    if (!pesertaAktif.value) return

    const nama = pesertaAktif.value.nama_lengkap
    const kupon = pesertaAktif.value.nomor_kupon
    const dept = pesertaAktif.value.departemen

    // Membuat template pesan text dengan format cetak tebal (*) khas WA
    const teksPesan = encodeURIComponent(
        `*KUPON LUCKY DRAW - HUT MARK DYNAMICS*\n\n` +
        `Halo rekan-rekan, saya telah sukses melakukan E-Absensi!\n\n` +
        `👤 *Nama:* ${nama}\n` +
        `🏭 *Departemen:* ${dept}\n` +
        `🎟️ *NOMOR UNDIAN:* ${kupon}\n\n` +
        `Semoga beruntung di acara Door Prize nanti! 🎉`
    )

    // Membuka tautan API WhatsApp
    window.open(`https://api.whatsapp.com/send?text=${teksPesan}`, '_blank')
}
</script>

<template>
    <div class="container-absensi">

        <div class="lottie-bg-global">
            <Vue3Lottie
                :animationData="bubbleAnimation"
                :loop="true"
                :autoPlay="true"
            />
        </div>


        <div class="header-banner">
            <h2>HUT PT Mark Dynamics Indonesia Tbk</h2>
            <p>Sistem E-Absensi &amp; Kupon Undian Mandiri</p>
        </div>


        <div class="step-wrapper">
            <var-paper :elevation="2" class="card-box">
                <h3 class="step-title">Langkah 1: Cari Data Anda</h3>
                <var-input
                    v-model="nomorIndukInput"
                    placeholder="Masukkan Nomor Induk Karyawan (NIK)"
                    clearable
                />
                <var-button
                    type="primary"
                    block
                    style="margin-top: 15px;"
                    :loading="isLoadingSearch"
                    @click="cariDataKaryawan"
                >
                    Cari Nama Saya
                </var-button>
            </var-paper>

            <var-paper v-if="dataKaryawan && !dataKaryawan.is_present" :elevation="2" class="card-box animasi-muncul">
                <h3 class="step-title">Langkah 2: Konfirmasi Kehadiran</h3>

                <div class="info-karyawan">
                    <p><strong>Nama:</strong> {{ dataKaryawan.nama_lengkap }}</p>
                    <p><strong>Departemen:</strong> {{ dataKaryawan.departemen }} ({{ dataKaryawan.lokasi_kerja }})</p>
                </div>

                <div class="pin-section">
                    <p class="pin-instruction">Masukkan 3 Digit PIN yang tertera pada Papan Pengumuman Gerbang Masuk:</p>
                    <var-input
                        v-model="form.pin"
                        maxlength="3"
                        placeholder="Contoh: 000"
                        :type="showPin ? 'text' : 'password'"
                        center
                    >
                        <template #suffix>
                            <var-icon
                                :name="showPin ? 'visibility-off' : 'visibility'"
                                @click="showPin = !showPin"
                                style="cursor: pointer; color: var(--var-input-placeholder-color, #999);"
                            />
                        </template>
                    </var-input>
                </div>

                <var-button
                    type="success"
                    block
                    style="margin-top: 20px;"
                    :disabled="form.processing"
                    @click="eksekusiCheckIn"
                >
                    Konfirmasi &amp; Ambil Nomor Undian
                </var-button>
            </var-paper>
        </div>
    </div>
</template>

<style scoped>
/* 1. CONTAINER UTAMA (Dark Theme) */
.container-absensi {
    position: relative;
    max-width: 480px;
    margin: 0 auto;
    padding: 20px 15px;
    font-family: sans-serif;
    background: linear-gradient(180deg, #0f0f23 0%, #1a1a3e 50%, #16213e 100%);
    min-height: 100vh;
    box-sizing: border-box;
    overflow: hidden;
    color: #e4e4e7;
}

/* 2. LAYER ANIMASI LOTTIE GLOBAL */
.lottie-bg-global {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
    opacity: 0.1;
}

.lottie-bg-global :deep(svg) {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
    filter: invert(1) hue-rotate(180deg);
}

/* 3. HEADER BANNER */
.header-banner {
    position: relative;
    z-index: 2;
    text-align: center;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #1e1e3f 0%, #2a2a5e 100%);
    color: #ffffff;
    padding: 25px 20px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.header-banner h2 {
    margin: 0;
    font-size: 1.35rem;
    letter-spacing: 0.5px;
    font-weight: 700;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.header-banner p {
    margin: 8px 0 0;
    font-size: 0.85rem;
    opacity: 0.85;
    color: #d1d5db;
    font-weight: 500;
}

/* 4. DESAIN KARTU TIKET (Dark) */
.ticket-card {
    position: relative;
    z-index: 2;
    background: linear-gradient(145deg, #1e1e3f 0%, #16213e 100%);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.ticket-header {
    padding: 25px 20px 10px;
    text-align: center;
    background: linear-gradient(145deg, #1e1e3f 0%, #25254a 100%);
}

.ticket-subtitle {
    font-size: 0.85rem;
    color: #9ca3af;
    margin: 5px 0 0;
}

/* Efek Potongan Tiket (Notch) */
.ticket-divider {
    position: relative;
    height: 2px;
    border-top: 2px dashed rgba(255, 255, 255, 0.1);
    margin: 10px 0;
}

.notch {
    position: absolute;
    width: 20px;
    height: 20px;
    background: #0f0f23;
    border-radius: 50%;
    top: -11px;
}

.notch-left { left: -11px; }
.notch-right { right: -11px; }

.ticket-body {
    padding: 15px 25px 25px;
}

/* Badge Nomor Kupon */
.kupon-badge {
    text-align: center;
    background: linear-gradient(135deg, #1e3a5f 0%, #1e4d7a 100%);
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
}

.kupon-label {
    font-size: 0.8rem;
    color: #bfdbfe;
    font-weight: bold;
    letter-spacing: 1.5px;
}

.kupon-number {
    font-size: 3.2rem;
    font-weight: 900;
    color: #60a5fa;
    margin: 5px 0 0;
    letter-spacing: 4px;
    text-shadow: 0 0 20px rgba(96, 165, 250, 0.5);
}

/* Detail Metadata Karyawan */
.meta-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: rgba(30, 30, 63, 0.8);
    padding: 15px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
    padding-bottom: 6px;
}

.meta-label { color: #9ca3af; }
.meta-value { color: #e4e4e7; text-align: right; }
.text-bold { font-weight: bold; color: #fbbf24; }

.ticket-note {
    font-size: 0.78rem;
    color: #6b7280;
    text-align: center;
    margin-top: 15px;
    font-style: italic;
}

.action-wrapper {
    position: relative;
    z-index: 2;
    padding: 0 5px;
}

/* 5. FORM CARI & INPUT PIN (Dark) */
.step-wrapper {
    position: relative;
    z-index: 2;
}

.card-box {
    padding: 25px 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: linear-gradient(145deg, #1e1e3f 0%, #16213e 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.step-title {
    margin-top: 0;
    margin-bottom: 18px;
    color: #fbbf24;
    font-size: 1.1rem;
    font-weight: 700;
    border-left: 4px solid #f59e0b;
    padding-left: 10px;
}

.info-karyawan {
    background: rgba(30, 58, 95, 0.6);
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 0.95rem;
    border-left: 3px solid #3b82f6;
}

.info-karyawan p {
    margin: 6px 0;
    color: #bfdbfe;
}

.pin-instruction {
    font-size: 0.85rem;
    color: #9ca3af;
    margin-bottom: 10px;
    line-height: 1.4;
}

/* 6. UTILITY KUSTOMISASI VARLET & ANIMASI */
:deep(.var-input) {
    --input-focus-color: #f59e0b;
    --input-hover-color: rgba(255, 255, 255, 0.08);
    --input-border-color: rgba(255, 255, 255, 0.12);
    --input-bg-color: rgba(30, 30, 63, 0.8);
    --input-color: #e4e4e7;
    --input-placeholder-color: #6b7280;
}

.animasi-muncul {
    animation: fadeIn 0.45s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

