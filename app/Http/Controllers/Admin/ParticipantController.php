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

        $participants = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Participants', [
            'participants' => $participants,
            'filters' => ['search' => $search],
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
}
