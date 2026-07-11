<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, usePage, router } from '@inertiajs/vue3'
import { Snackbar } from '@varlet/ui'

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

// Form Prosedur Check-in Inertia v3
const form = useForm({
    nomor_induk: '',
    pin: ''
})

// Membaca data sukses setelah redirect dari backend
const dataSukses = computed(() => page.props.flash?.success as any || null)



const cariDataKaryawan = () => {
    if (!nomorIndukInput.value) {
        Snackbar.warning('Silakan masukkan Nomor Induk terlebih dahulu!')
        return
    }

    isLoadingSearch.value = true
    const baseUrl = page.props.app_url || '/varlet/public'

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


// 1. Logika pesertaAktif diubah agar mendeteksi jika dataKaryawan statusnya sudah 'is_present'
const pesertaAktif = computed<Participant | null>(() => {
    if (dataKaryawan.value && dataKaryawan.value.is_present) {
        return dataKaryawan.value
    }
    return null
})

// 2. Perbarui fungsi eksekusiCheckIn agar memanipulasi dataKaryawan secara manual setelah sukses
const eksekusiCheckIn = () => {
    if (form.pin.length !== 3) {
        Snackbar.warning('PIN harus terdiri dari 3 digit angka!')
        return
    }

    const baseUrl = page.props.app_url || '/varlet/public'

    form.post(`${baseUrl}/absensi/checkin`, {
        onSuccess: () => {
            Snackbar.success('Selamat! Check-in berhasil dilakukan.')

            // Trik Jitu: Ambil data fresh dari session flash yang dikirim balik oleh Laravel
            const flash = page.props.flash as any

            if (flash && flash.success && flash.success.participant) {
                // Kita timpa dataKaryawan dengan data terbaru dari server (yang sudah berisi nomor_kupon dan is_present = true)
                dataKaryawan.value = flash.success.participant
            } else {
                // Fallback aman: jika objek flash tidak terbaca, kita paksa ubah status lokalnya
                if (dataKaryawan.value) {
                    dataKaryawan.value.is_present = true;
                    // Mengira-ngira nomor kupon sementara sebelum sinkronisasi halaman penuh
                    const totalHadir = page.props.searched_participant ? 1 : 0; // opsional
                }
            }

            form.reset('pin')
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
        <div class="header-banner">
            <h2>HUT PT Mark Dynamics Indonesia Tbk</h2>
            <p>Sistem E-Absensi &amp; Kupon Undian Mandiri</p>
        </div>

        <div v-if="pesertaAktif" class="animasi-muncul">
            <var-paper :elevation="4" class="ticket-card">
                <div class="ticket-header">
                    <var-result type="success" title="CHECK-IN BERHASIL" />
                    <p class="ticket-subtitle">Bukti Kehadiran &amp; Kupon Door Prize Resmi</p>
                </div>

                <div class="ticket-divider">
                    <div class="notch notch-left"></div>
                    <div class="notch notch-right"></div>
                </div>

                <div class="ticket-body">
                    <div class="kupon-badge">
                        <span class="kupon-label">NOMOR KUPON ANDA</span>
                        <h1 class="kupon-number">{{ pesertaAktif.nomor_kupon }}</h1>
                    </div>

                    <div class="meta-details">
                        <div class="meta-row">
                            <span class="meta-label">Nama Karyawan</span>
                            <span class="meta-value text-bold">{{ pesertaAktif.nama_lengkap }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Nomor Induk</span>
                            <span class="meta-value">{{ pesertaAktif.nomor_induk }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Departemen</span>
                            <span class="meta-value">{{ pesertaAktif.departemen }}</span>
                        </div>
                        <div class="meta-row">
                            <span class="meta-label">Lokasi Kerja</span>
                            <span class="meta-value">{{ pesertaAktif.lokasi_kerja }}</span>
                        </div>
                    </div>

                    <p class="ticket-note">*Silakan screenshot halaman ini sebagai cadangan bukti fisik saat penukaran hadiah.</p>
                </div>
            </var-paper>

            <div class="action-wrapper">
                <var-button
                    type="success"
                    block
                    size="large"
                    @click="bagikanKeWhatsApp"
                >
                    <template #left-icon>
                        <var-icon name="whatsapp" />
                    </template>
                    Bagikan Nomor Kupon ke WhatsApp
                </var-button>


                <var-button
                    type="warning"
                    block
                    style="margin-top: 10px;"
                    @click="dataKaryawan = null; nomorIndukInput = ''; if(page.props.flash) page.props.flash.success = null"
                >
                    Kembali ke Halaman Utama
                </var-button>
            </div>
        </div>

        <div v-else class="step-wrapper">
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
                        type="number"
                        center
                    />
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
.container-absensi {
    max-width: 480px;
    margin: 0 auto;
    padding: 20px 15px;
    font-family: sans-serif;
    /* BACKGROUND WARNA CAFE GRADIENT - Perpaduan hangat moka ke krem gading yang estetik */
    background: linear-gradient(180deg, #e8dfd8 0%, #f5efe9 100%);
    min-height: 100vh;
    box-sizing: border-box;
}

/* HEADER BANNER - Tetap Merah & Oranye Menyala khas Logo dengan Gradasi Tajam */
.header-banner {
    text-align: center;
    margin-bottom: 25px;
    background: linear-gradient(135deg, #e63946 0%, #f35b04 100%);
    color: #ffffff;
    padding: 25px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(243, 91, 4, 0.25);
}
.header-banner h2 {
    margin: 0;
    font-size: 1.35rem;
    letter-spacing: 0.5px;
    font-weight: 700;
}
.header-banner p {
    margin: 8px 0 0;
    font-size: 0.85rem;
    opacity: 0.95;
    color: #ffffff;
    font-weight: 500;
}

/* DESAIN KARTU TIKET - Putih Bersih dengan Shadow Lembut di atas Background Hangat */
.ticket-card {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 25px;
    border: 1px solid rgba(218, 204, 193, 0.4);
    box-shadow: 0 10px 25px rgba(168, 150, 134, 0.15);
}
.ticket-header {
    padding: 25px 20px 10px;
    text-align: center;
    background: #fdfcfb;
}
.ticket-subtitle {
    font-size: 0.85rem;
    color: #666;
    margin: 5px 0 0;
}

/* Efek Potongan Tiket */
.ticket-divider {
    position: relative;
    height: 2px;
    border-top: 2px dashed #e4ded9;
    margin: 10px 0;
}
.notch {
    position: absolute;
    width: 20px;
    height: 20px;
    /* KUNCI: Notch mengikuti warna gradasi atas agar menyatu sempurna */
    background: #e8dfd8;
    border-radius: 50%;
    top: -11px;
}
.notch-left { left: -11px; }
.notch-right { right: -11px; }

.ticket-body {
    padding: 15px 25px 25px;
}

/* KUPON BADGE - Gradasi Oranye Lembut yang Segar */
.kupon-badge {
    text-align: center;
    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    border: 2px solid #f35b04;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 20px;
}
.kupon-label {
    font-size: 0.8rem;
    color: #e65100;
    font-weight: bold;
    letter-spacing: 1.5px;
}
.kupon-number {
    font-size: 3.2rem;
    font-weight: 900;
    color: #d84315;
    margin: 5px 0 0;
    letter-spacing: 4px;
}

.meta-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: #fafafa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
}
.meta-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
    border-bottom: 1px dashed #eaeaea;
    padding-bottom: 6px;
}
.meta-label { color: #7f8c8d; }
.meta-value { color: #2c3e50; text-align: right; }
.text-bold { font-weight: bold; color: #111; }

.ticket-note {
    font-size: 0.78rem;
    color: #95a5a6;
    text-align: center;
    margin-top: 15px;
    font-style: italic;
}
.action-wrapper {
    padding: 0 5px;
}

/* BOX FORM CARI & INPUT PIN */
.card-box {
    padding: 25px 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid rgba(218, 204, 193, 0.4);
    box-shadow: 0 10px 25px rgba(168, 150, 134, 0.15);
}
.step-title {
    margin-top: 0;
    margin-bottom: 18px;
    color: #e63946;
    font-size: 1.1rem;
    font-weight: 700;
    border-left: 4px solid #f35b04;
    padding-left: 10px;
}

/* Kustomisasi Input Varlet */
:deep(.var-input) {
    --input-focus-color: #f35b04;
}

.info-karyawan {
    background: #fff3e0;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 0.95rem;
    border-left: 3px solid #f35b04;
}
.info-karyawan p { margin: 6px 0; color: #e65100; }
.pin-instruction { font-size: 0.85rem; color: #555; margin-bottom: 10px; line-height: 1.4; }

.animasi-muncul { animation: fadeIn 0.45s ease-in-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
</style>

