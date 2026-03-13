@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Alumni --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                    <p class="text-sm text-gray-500">Total Alumni</p>
                </div>
            </div>
        </div>

        {{-- Auto Verified --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['auto_verified'] }}</p>
                    <p class="text-sm text-gray-500">Terverifikasi</p>
                </div>
            </div>
        </div>

        {{-- Needs Audit --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['needs_audit'] }}</p>
                    <p class="text-sm text-gray-500">Perlu Review</p>
                </div>
            </div>
        </div>

        {{-- Belum Dilacak --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['belum_dilacak'] }}</p>
                    <p class="text-sm text-gray-500">Belum Dilacak</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-3">Ringkasan Status</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Terverifikasi</span>
                    <span class="text-sm font-semibold text-green-600">{{ $stats['auto_verified'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Perlu Review</span>
                    <span class="text-sm font-semibold text-yellow-600">{{ $stats['needs_audit'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Belum Dilacak</span>
                    <span class="text-sm font-semibold text-gray-600">{{ $stats['belum_dilacak'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Tidak Ditemukan</span>
                    <span class="text-sm font-semibold text-red-600">{{ $stats['not_found'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Data Kurang</span>
                    <span class="text-sm font-semibold text-orange-600">{{ $stats['insufficient_data'] }}</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-medium text-gray-500 mb-3">Aksi Cepat</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('alumni.create') }}"
                    class="flex items-center gap-3 px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span class="text-sm font-medium">Tambah Alumni</span>
                </a>
                <a href="{{ route('alumni.index') }}"
                    class="flex items-center gap-3 px-4 py-3 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span class="text-sm font-medium">Lihat Semua Alumni</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Alumni --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-medium text-gray-900">Alumni Terakhir Diperbarui</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIM</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prodi
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lulus
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentAlumni as $alum)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-mono text-gray-600">{{ $alum->nim }}</td>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400">Belum ada data alumni.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
