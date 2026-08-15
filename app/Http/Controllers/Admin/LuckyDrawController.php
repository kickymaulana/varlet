<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\Setting;
use App\Models\WinnerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LuckyDrawController extends Controller
{
    // ============ ADMIN: CRUD PRIZES ============

    public function prizes(Request $request)
    {
        $query = Prize::orderBy('urutan');
        if ($search = $request->input('search')) {
            $query->where('nama', 'like', "%{$search}%");
        }
        $prizes = $query->paginate(10)->withQueryString();
        return Inertia::render('Admin/Prizes', [
            'prizes' => $prizes,
            'filters' => ['search' => $search],
        ]);
    }

    public function storePrize(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:255', 'deskripsi' => 'nullable|string']);
        $maxUrutan = Prize::max('urutan') ?? 0;
        Prize::create(['nama' => $request->nama, 'deskripsi' => $request->deskripsi, 'urutan' => $maxUrutan + 1]);
        return redirect()->route('admin.lucky-draw.prizes');
    }

    public function updatePrize(Request $request, $id)
    {
        $request->validate(['nama' => 'required|string|max:255', 'deskripsi' => 'nullable|string']);
        Prize::findOrFail($id)->update(['nama' => $request->nama, 'deskripsi' => $request->deskripsi]);
        return redirect()->route('admin.lucky-draw.prizes');
    }

    public function deletePrize($id)
    {
        Prize::findOrFail($id)->delete();
        return redirect()->route('admin.lucky-draw.prizes');
    }

    // ============ ADMIN: DRAW CONTROL ============

    public function drawPage(Request $request)
    {
        $query = Prize::orderBy('urutan');
        if ($search = $request->input('search')) {
            $query->where('nama', 'like', "%{$search}%");
        }
        $prizes = $query->paginate(10)->withQueryString();
        $winners = WinnerLog::with('prize')->latest()->get();
        $totalEligible = Participant::where('is_present', true)->where('eligible_for_draw', true)->count();
        $remaining = Participant::where('is_present', true)->where('eligible_for_draw', true)
            ->whereNotIn('id', WinnerLog::pluck('participant_id'))->count();
        $currentDrawPrizeId = Setting::getValue('current_draw_prize_id');

        return Inertia::render('Admin/Draw', [
            'prizes' => $prizes,
            'winners' => $winners,
            'total_participants' => $totalEligible,
            'remaining_count' => $remaining,
            'current_draw_prize_id' => $currentDrawPrizeId,
            'filters' => ['search' => $request->input('search')],
        ]);
    }

    // Admin klik "Mulai" → set current draw di settings
    public function startDraw(Request $request)
    {
        $request->validate(['prize_id' => 'required|exists:prizes,id']);
        $prize = Prize::findOrFail($request->prize_id);
        if ($prize->is_drawn) {
            return response()->json(['message' => 'Hadiah ini sudah diundi'], 400);
        }
        Setting::setValue('current_draw_prize_id', $prize->id);
        
        // Debug: cek apakah benar tersimpan
        $saved = Setting::getValue('current_draw_prize_id');
        Log::info('startDraw: set current_draw_prize_id', [
            'request_prize_id' => $prize->id,
            'saved_value' => $saved,
            'match' => $saved == $prize->id,
        ]);
        
        return response()->json(['prize_id' => $prize->id, 'prize_name' => $prize->nama]);
    }

    // Display/MC klik "Mulai Undi" → ambil pemenang, simpan, clear current_draw
    public function executeDraw(Request $request)
{
        Log::info('executeDraw: ENTRY', ['method' => $request->method(), 'all' => $request->all(), 'json' => $request->json()->all(), 'content' => $request->getContent()]);
        
        // Manual JSON parsing fallback
        $data = $request->json()->all();
        if (empty($data) && $request->getContent()) {
            $data = json_decode($request->getContent(), true) ?? [];
        }
        $requestPrizeId = $data['prize_id'] ?? $request->input('prize_id');
        
        if (!$requestPrizeId) {
            Log::warning('executeDraw: prize_id missing', ['all' => $request->all(), 'json' => $request->json()->all()]);
            return response()->json(['message' => 'prize_id diperlukan'], 400);
        }
        
        $request->validate(['prize_id' => 'required|exists:prizes,id']);
        
        $storedPrizeId = Setting::getValue('current_draw_prize_id');
        
        // Debug: log nilai yang dibaca
        Log::info('executeDraw: cek current_draw_prize_id', [
            'request_prize_id' => $requestPrizeId,
            'stored_prize_id' => $storedPrizeId,
            'match' => $storedPrizeId == $requestPrizeId,
            'session_id' => session()->getId(),
        ]);
        
        if ($storedPrizeId != $requestPrizeId) {
            Log::warning('executeDraw: MISMATCH!', [
                'stored' => $storedPrizeId,
                'request' => $requestPrizeId,
            ]);
            return response()->json(['message' => 'Tidak ada sesi undian untuk hadiah ini'], 400);
        }
        
        $prize = Prize::findOrFail($requestPrizeId);
        $available = Participant::where('is_present', true)->where('eligible_for_draw', true)
            ->whereNotIn('id', WinnerLog::pluck('participant_id'))->get();

        if ($available->isEmpty()) {
            Setting::setValue('current_draw_prize_id', '');
            return response()->json([
                'all_winners_done' => true,
                'message' => 'Semua peserta sudah mendapat hadiah'
            ]);
        }

        $winner = $available->random();

        if (!$winner->nomor_kupon) {
            $sudahDapatKupon = Participant::whereNotNull('nomor_kupon')->count();
            $nomorUrut = str_pad($sudahDapatKupon + 1, 4, '0', STR_PAD_LEFT);
            $winner->update(['nomor_kupon' => 'MD-' . $nomorUrut]);
        }

        WinnerLog::create([
            'prize_id' => $prize->id,
            'participant_id' => $winner->id,
            'nomor_kupon' => $winner->nomor_kupon,
            'nama_pemenang' => $winner->nama_lengkap,
            'departemen' => $winner->departemen,
            'lokasi_kerja' => $winner->lokasi_kerja,
            'drawn_at' => now(),
        ]);

        $prize->update(['is_drawn' => true]);
        Setting::setValue('current_draw_prize_id', '');

        return response()->json([
            'winner' => [
                'nama' => $winner->nama_lengkap,
                'nomor_kupon' => $winner->nomor_kupon,
                'departemen' => $winner->departemen,
                'lokasi_kerja' => $winner->lokasi_kerja,
            ],
            'prize' => ['nama' => $prize->nama, 'deskripsi' => $prize->deskripsi],
        ]);
    }

    public function resetDraw()
    {
        WinnerLog::truncate();
        Prize::query()->update(['is_drawn' => false]);
        Setting::setValue('current_draw_prize_id', '');
        return redirect()->route('admin.lucky-draw.draw');
    }

    // ============ DISPLAY / PROJECTOR ============

    public function display()
    {
        $currentWinner = WinnerLog::with('prize')->latest('drawn_at')->first();
        $winners = WinnerLog::with('prize')->orderBy('drawn_at', 'desc')->get();

        return Inertia::render('LuckyDraw/Display', [
            'current_winner' => $currentWinner,
            'winners' => $winners,
        ]);
    }

    public function displayData()
    {
        $currentWinner = WinnerLog::with('prize')->latest('drawn_at')->first();
        $winners = WinnerLog::with('prize')->orderBy('drawn_at', 'desc')->get();
        $currentDrawPrizeId = Setting::getValue('current_draw_prize_id');

        $pendingPrize = null;
        if ($currentDrawPrizeId) {
            $pendingPrize = Prize::find($currentDrawPrizeId);
        }

        Log::info('displayData: polling', [
            'current_draw_prize_id' => $currentDrawPrizeId,
            'pending_prize' => $pendingPrize ? $pendingPrize->id : null,
            'winners_count' => $winners->count(),
        ]);

        return response()->json([
            'current_winner' => $currentWinner,
            'winners' => $winners,
            'pending_draw' => $pendingPrize ? [
                'id' => $pendingPrize->id,
                'nama' => $pendingPrize->nama,
                'deskripsi' => $pendingPrize->deskripsi,
            ] : null,
        ]);
    }
}
