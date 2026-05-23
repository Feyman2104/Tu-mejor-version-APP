<?php

namespace App\Http\Controllers;

use App\Models\ProgressEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = $request->user()
            ->progressEntries()
            ->orderByDesc('recorded_at')
            ->get();

        return Inertia::render('Progress/Index', [
            'entries' => $entries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weight_kg'     => ['nullable', 'numeric', 'min:20', 'max:300'],
            'body_fat_pct'  => ['nullable', 'numeric', 'min:1', 'max:70'],
            'muscle_mass_kg'=> ['nullable', 'numeric', 'min:5', 'max:150'],
            'notes'         => ['nullable', 'string', 'max:500'],
            'recorded_at'   => ['nullable', 'date'],
        ]);

        $request->user()->progressEntries()->create([
            ...$validated,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return back()->with('success', 'Medida registrada correctamente.');
    }
}
