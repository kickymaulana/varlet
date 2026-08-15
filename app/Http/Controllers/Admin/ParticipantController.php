<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = Participant::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_induk', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }

        if ($departemen = $request->input('departemen')) {
            $query->where('departemen', $departemen);
        }

        if ($request->has('eligible_for_draw') && $request->input('eligible_for_draw') !== '') {
            $value = $request->input('eligible_for_draw');
            $query->where('eligible_for_draw', $value === 'true' || $value === true);
        }

        $participants = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Statistics for all participants (not filtered)
        $totalParticipants = Participant::count();
        $presentCount = Participant::where('is_present', true)->count();
        $absentCount = Participant::where('is_present', false)->count();
        $eligibleCount = Participant::where('eligible_for_draw', true)->count();
        $notEligibleCount = Participant::where('eligible_for_draw', false)->count();

        return Inertia::render('Admin/Participants', [
            'participants' => $participants,
            'filters' => [
                'search' => $search,
                'departemen' => $departemen,
                'eligible_for_draw' => $request->input('eligible_for_draw'),
            ],
            'stats' => [
                'total' => $totalParticipants,
                'present' => $presentCount,
                'absent' => $absentCount,
                'eligible' => $eligibleCount,
                'not_eligible' => $notEligibleCount,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_induk' => 'required|string|unique:participants,nomor_induk',
            'nama_lengkap' => 'required|string|max:255',
            'departemen' => 'required|string|max:255',
            'lokasi_kerja' => 'required|string|max:255',
            'nomor_hp' => 'nullable|string|max:255',
            'eligible_for_draw' => 'boolean',
        ]);

        Participant::create($request->all());

        return redirect()->route('admin.participants.index');
    }

    public function update(Request $request, $id)
    {
        $participant = Participant::findOrFail($id);

        $request->validate([
            'nomor_induk' => 'required|string|unique:participants,nomor_induk,' . $id,
            'nama_lengkap' => 'required|string|max:255',
            'departemen' => 'required|string|max:255',
            'lokasi_kerja' => 'required|string|max:255',
            'nomor_hp' => 'nullable|string|max:255',
            'eligible_for_draw' => 'boolean',
        ]);

        $participant->update($request->all());

        return redirect()->route('admin.participants.index');
    }

    public function destroy($id)
    {
        Participant::findOrFail($id)->delete();
        return redirect()->route('admin.participants.index');
    }

    public function resetAttendance()
    {
        Participant::query()->update([
            'is_present' => false,
            'attended_at' => null,
            'nomor_kupon' => null,
        ]);

        return redirect()->route('admin.participants.index')
            ->with('success', 'Kehadiran semua peserta berhasil direset. Eligible for draw tidak berubah.');
    }
}
