<?php

namespace App\Http\Controllers;

use App\Models\WinnerLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicWinnerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('per_page', 20);

        $query = WinnerLog::with('prize')->latest('drawn_at');

        if ($search) {
            $query->searchByName($search);
        }

        $winners = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Public/Winners', [
            'winners' => $winners,
            'search' => $search,
            'totalWinners' => WinnerLog::count(),
        ]);
    }
}