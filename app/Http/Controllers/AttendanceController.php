<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{

    public function index(Request $request)
    {
        $nomorInduk = $request->input('search_nik');
        $participant = null;

        if ($nomorInduk) {
            $participant = Participant::where('nomor_induk', $nomorInduk)->first();
        }

        return Inertia::render('Attendance/Index', [
            // Menggunakan closure agar data ini hanya dikirim jika diminta lewat properti 'only'
            'searched_participant' => function () use ($participant) {
                return $participant;
            },
            // Pesan error jika NIK dicari tapi tidak ada di DB
            'search_error' => function () use ($nomorInduk, $participant) {
                if ($nomorInduk && !$participant) {
                    return 'Data tidak ditemukan. Silakan periksa kembali Nomor Induk Anda.';
                }
                return null;
            }
        ]);
    }

    // Mencari data karyawan berdasarkan Nomor Induk (NIK)
    public function search(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string'
        ]);

        $participant = Participant::where('nomor_induk', $request->nomor_induk)->first();

        if (!$participant) {
            return response()->json(['message' => 'Data tidak ditemukan. Silakan periksa kembali Nomor Induk Anda.'], 404);
        }

        return response()->json($participant);
    }

    // Proses Check-in Mandiri dengan Validasi PIN
    public function checkIn(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string',
            'pin' => 'required|string|size:3',
        ]);

        // 1. Validasi PIN Hari Ini (Bisa disimpan di .env dengan key ATTENDANCE_PIN)
        $pinHariIni = env('ATTENDANCE_PIN', '782');
        if ($request->pin !== $pinHariIni) {
            return back()->withErrors(['pin' => 'Kode PIN yang Anda masukkan salah! Silakan lihat papan pengumuman panitia.']);
        }

        $participant = Participant::where('nomor_induk', $request->nomor_induk)->firstOrFail();

        // 2. Cek apakah sudah pernah check-in
        if ($participant->is_present) {
            return back()->withErrors(['nomor_induk' => 'Anda sudah melakukan check-in sebelumnya!']);
        }

        // 3. Generate Nomor Kupon Urut otomatis (Contoh: MD-0001, MD-0002)
        $totalHadir = Participant::where('is_present', true)->count();
        $nomorUrut = str_pad($totalHadir + 1, 4, '0', STR_PAD_LEFT);
        $nomorKupon = 'MD-' . $nomorUrut;

        // 4. Update data kehadiran
        $participant->update([
            'is_present' => true,
            'nomor_kupon' => $nomorKupon,
            'attended_at' => now(),
        ]);

        // Kembalikan data terbaru ke halaman Vue bawaan Inertia v3
        return back()->with('success', [
            'message' => 'Check-in Berhasil!',
            'participant' => $participant
        ]);
    }
}
