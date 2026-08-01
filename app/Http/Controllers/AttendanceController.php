<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Setting;
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


    public function checkIn(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string',
            'pin' => 'required|string|size:3',
        ]);

        $pinHariIni = Setting::getValue('attendance_pin', '782');
        if ($request->pin !== $pinHariIni) {
            return back()->withErrors(['pin' => 'Kode PIN yang Anda masukkan salah!']);
        }

        $participant = Participant::where('nomor_induk', $request->nomor_induk)->firstOrFail();

        if ($participant->is_present) {
            return back()->withErrors(['nomor_induk' => 'Anda sudah melakukan check-in sebelumnya!']);
        }

        $updateData = [
            'is_present' => true,
            'attended_at' => now(),
        ];

        if ($participant->eligible_for_draw) {
            // Ambil nomor kupon terbesar yang pernah dibuat, lalu +1 (anti dobel walau ada reset)
            $lastNumber = Participant::where('nomor_kupon', 'like', 'MD-%')
                ->pluck('nomor_kupon')
                ->map(fn($k) => (int) str_replace('MD-', '', $k))
                ->max() ?? 0;
            $nomorUrut = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $updateData['nomor_kupon'] = 'MD-' . $nomorUrut;
        } else {
            $updateData['nomor_kupon'] = null;
        }

        $participant->update($updateData);

        // KUNCI: Redirect ke halaman kupon membawa parameter nomor_induk
        return redirect()->route('attendance.kupon', ['nomor_induk' => $participant->nomor_induk]);
    }

    // Method baru untuk menampilkan halaman khusus Kupon
    public function kupon($nomorInduk)
    {
        $participant = Participant::where('nomor_induk', $nomorInduk)
            ->where('is_present', true)
            ->firstOrFail(); // Amankan agar yang belum check-in tidak bisa nembak route ini

        return Inertia::render('Attendance/Kupon', [
            'participant' => $participant
        ]);
    }


}
