@extends('layouts.app')

@section('title', 'Data Alumni')
@section('page-title', 'Data Alumni')

@section('content')
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola data alumni yang akan dilacak</p>
        </div>
        <a href="{{ route('alumni.create') }}"
            class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Tambah Alumni
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <form method="GET" action="{{ route('alumni.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    placeholder="Cari NIM, nama, atau prodi...">
            </div>
            <select name="status"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                <option value="">Semua Status</option>
                <option value="belum_dilacak" {{ request('status') == 'belum_dilacak' ? 'selected' : '' }}>Belum Dilacak
                </option>
                <option value="auto_verified" {{ request('status') == 'auto_verified' ? 'selected' : '' }}>Terverifikasi
                </option>
                <option value="needs_audit" {{ request('status') == 'needs_audit' ? 'selected' : '' }}>Perlu Review</option>
                <option value="not_found" {{ request('status') == 'not_found' ? 'selected' : '' }}>Tidak Ditemukan</option>
                <option value="insufficient_data" {{ request('status') == 'insufficient_data' ? 'selected' : '' }}>Data
                    Kurang</option>
            </select>
            <select name="prodi"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                <option value="">Semua Prodi</option>
                @foreach ($prodiList as $prodi)
                    <option value="{{ $prodi }}" {{ request('prodi') == $prodi ? 'selected' : '' }}>
                        {{ $prodi }}</option>
                @endforeach
            </select>
            <select name="tahun_lulus"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                <option value="">Semua Tahun</option>
                @foreach ($tahunList as $tahun)
                    <option value="{{ $tahun }}" {{ request('tahun_lulus') == $tahun ? 'selected' : '' }}>
                        {{ $tahun }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'status', 'prodi', 'tahun_lulus']))
                    <a href="{{ route('alumni.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama
                            Lengkap</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prodi
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun
                            Lulus</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($alumni as $alum)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-mono text-gray-600 whitespace-nowrap">{{ $alum->nim }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('alumni.show', $alum->nim) }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $alum->nama_lengkap }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $alum->prodi }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $alum->tahun_lulus }}</td>
                            <td class="px-5 py-3">
                                @include('components.status-badge', ['status' => $alum->tracking_status])
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('alumni.show', $alum->nim) }}"
                                        class="text-gray-400 hover:text-blue-600 transition-colors" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('alumni.edit', $alum->nim) }}"
                                        class="text-gray-400 hover:text-yellow-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('alumni.destroy', $alum->nim) }}"
                                        onsubmit="return confirm('Yakin ingin menghapus data alumni ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors"
                                            title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-gray-400 text-sm">Belum ada data alumni.</p>
                                    <a href="{{ route('alumni.create') }}"
                                        class="text-blue-600 text-sm hover:underline">Tambah alumni pertama →</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($alumni->hasPages())
            <div class="px-5 py-3 border-t border-gray-200">
                {{ $alumni->links() }}
            </div>
        @endif
    </div>
@endsection
