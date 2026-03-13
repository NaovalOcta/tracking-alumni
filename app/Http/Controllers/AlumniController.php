<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlumniRequest;
use App\Http\Requests\UpdateAlumniRequest;
use App\Models\Alumni;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumni::query();

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // Filter by prodi
        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        // Filter by tahun lulus
        if ($request->filled('tahun_lulus')) {
            $query->where('tahun_lulus', $request->tahun_lulus);
        }

        $alumni = $query->orderBy('nama_lengkap')->paginate(15)->withQueryString();

        // Get unique values for filter dropdowns
        $prodiList = Alumni::select('prodi')->distinct()->orderBy('prodi')->pluck('prodi');
        $tahunList = Alumni::select('tahun_lulus')->distinct()->orderByDesc('tahun_lulus')->pluck('tahun_lulus');

        return view('alumni.index', compact('alumni', 'prodiList', 'tahunList'));
    }

    public function create()
    {
        return view('alumni.create');
    }

    public function store(StoreAlumniRequest $request)
    {
        $data = $request->validated();

        // Convert comma-separated nama_variasi string to array
        if (!empty($data['nama_variasi'])) {
            $data['nama_variasi'] = array_map('trim', explode(',', $data['nama_variasi']));
        } else {
            $data['nama_variasi'] = null;
        }

        Alumni::create($data);

        return redirect()->route('alumni.index')
            ->with('success', 'Data alumni berhasil ditambahkan.');
    }

    public function show(string $nim)
    {
        $alumni = Alumni::with(['trackingResults', 'evidenceLogs', 'trackingHistories'])->findOrFail($nim);

        return view('alumni.show', compact('alumni'));
    }

    public function edit(string $nim)
    {
        $alumni = Alumni::findOrFail($nim);

        return view('alumni.edit', compact('alumni'));
    }

    public function update(UpdateAlumniRequest $request, string $nim)
    {
        $alumni = Alumni::findOrFail($nim);
        $data = $request->validated();

        // Convert comma-separated nama_variasi string to array
        if (!empty($data['nama_variasi'])) {
            $data['nama_variasi'] = array_map('trim', explode(',', $data['nama_variasi']));
        } else {
            $data['nama_variasi'] = null;
        }

        // If NIM changed, we need to handle it specially
        $newNim = $data['nim'];
        unset($data['nim']);

        if ($newNim !== $nim) {
            // Create new record with new NIM, then delete old
            $newAlumni = $alumni->replicate();
            $newAlumni->nim = $newNim;
            $newAlumni->fill($data);
            $newAlumni->save();
            $alumni->delete();
        } else {
            $alumni->update($data);
        }

        return redirect()->route('alumni.show', $newNim)
            ->with('success', 'Data alumni berhasil diperbarui.');
    }

    public function destroy(string $nim)
    {
        $alumni = Alumni::findOrFail($nim);
        $alumni->delete();

        return redirect()->route('alumni.index')
            ->with('success', 'Data alumni berhasil dihapus.');
    }
}
