@extends('layouts.app')

@section('title', $alumni->nama_lengkap)
@section('page-title', 'Detail Alumni')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('alumni.index') }}" class="text-sm text-blue-600 hover:text-blue-800">← Kembali ke Data Alumni</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Profile Card --}}
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                {{-- Avatar --}}
                <div class="flex flex-col items-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                        <span class="text-2xl font-bold text-blue-600">
                            {{ strtoupper(substr($alumni->nama_lengkap, 0, 2)) }}
                        </span>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white text-center">{{ $alumni->nama_lengkap }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 font-mono">{{ $alumni->nim }}</p>
                    <div class="mt-2">
                        @include('components.status-badge', ['status' => $alumni->tracking_status])
                    </div>
                </div>

                {{-- Info --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Prodi</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $alumni->prodi }}</span>
                    </div>
                    @if ($alumni->fakultas)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Fakultas</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $alumni->fakultas }}</span>
                        </div>
                    @endif
                    @if ($alumni->tahun_masuk)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Tahun Masuk</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $alumni->tahun_masuk }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Tahun Lulus</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $alumni->tahun_lulus }}</span>
                    </div>
                    @if ($alumni->email)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Email</span>
                            <span class="text-gray-900 dark:text-white font-medium text-right break-all">{{ $alumni->email }}</span>
                        </div>
                    @endif
                    @if ($alumni->no_telepon)
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500">Telepon</span>
                            <span class="text-gray-900 dark:text-white font-medium">{{ $alumni->no_telepon }}</span>
                        </div>
                    @endif
                    @if ($alumni->nama_variasi)
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block mb-1">Variasi Nama</span>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($alumni->nama_variasi as $variasi)
                                    <span
                                        class="inline-block bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs px-2 py-1 rounded">{{ $variasi }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                    <a href="{{ route('alumni.edit', $alumni->nim) }}"
                        class="flex-1 text-center bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('alumni.destroy', $alumni->nim) }}" class="flex-1"
                        onsubmit="return confirm('Yakin ingin menghapus data alumni ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full bg-red-50 text-red-600 px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Detail Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tracking Result --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Hasil Tracking Terkini</h3>
                </div>
                <div class="p-5">
                    @if ($alumni->trackingResults->isNotEmpty())
                        @php $latestResult = $alumni->trackingResults->sortByDesc('created_at')->first(); @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">Jabatan</span>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $latestResult->jabatan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">Instansi</span>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $latestResult->instansi ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">Bidang</span>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $latestResult->bidang_pekerjaan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">Lokasi</span>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $latestResult->lokasi ?? '-' }}</span>
                            </div>
                            @if ($latestResult->confidence_score)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">Skor Kepercayaan</span>
                                    <span
                                        class="text-gray-900 dark:text-white font-medium">{{ number_format($latestResult->confidence_score * 100, 0) }}%</span>
                                </div>
                            @endif
                            @if ($latestResult->linkedin_url)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 block">LinkedIn</span>
                                    <a href="{{ $latestResult->linkedin_url }}" target="_blank"
                                        class="text-blue-600 hover:underline break-all">{{ $latestResult->linkedin_url }}</a>
                                </div>
                            @endif
                        </div>
                        @if ($latestResult->ai_notes)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-500 text-sm block mb-1">Catatan AI</span>
                                <p class="text-sm text-gray-700 dark:text-gray-200 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg">{{ $latestResult->ai_notes }}
                                </p>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-6">
                            <p class="text-gray-400 dark:text-gray-500 text-sm">Belum ada hasil tracking.</p>
                            <p class="text-gray-300 text-xs mt-1">Fitur tracking akan tersedia di Fase 2.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Evidence Logs --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Jejak Bukti (Evidence)</h3>
                </div>
                <div class="p-5">
                    @if ($alumni->evidenceLogs->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($alumni->evidenceLogs->sortByDesc('searched_at') as $evidence)
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-xs font-medium px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded">{{ strtoupper(str_replace('_', ' ', $evidence->source_type)) }}</span>
                                        <span
                                            class="text-xs text-gray-400 dark:text-gray-500">{{ $evidence->searched_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <a href="{{ $evidence->source_url }}" target="_blank"
                                        class="text-xs text-blue-600 hover:underline break-all">{{ Str::limit($evidence->source_url, 80) }}</a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">{{ Str::limit($evidence->raw_snippet, 150) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 dark:text-gray-500 text-sm py-6">Belum ada evidence log.</p>
                    @endif
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Riwayat Perubahan</h3>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $alumni->trackingHistories->count() }} Versi</span>
                </div>
                <div class="p-0">
                    @if ($alumni->trackingHistories->isNotEmpty())
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($alumni->trackingHistories->sortByDesc('created_at') as $history)
                                <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-700 dark:bg-gray-900/50 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $history->changed_reason ?? 'Pembaruan Data' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $history->created_at ? $history->created_at->format('d M Y, H:i') : '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-3 gap-x-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-xs">
                                        @foreach(['jabatan', 'instansi', 'lokasi', 'bidang_pekerjaan'] as $field)
                                            @if(isset($history->snapshot_data[$field]))
                                                <div>
                                                    <span class="text-gray-400 dark:text-gray-500 capitalize block">{{ str_replace('_', ' ', $field) }}</span>
                                                    <span class="text-gray-700 dark:text-gray-200 font-medium">{{ $history->snapshot_data[$field] ?: '-' }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                        @if(isset($history->snapshot_data['confidence']))
                                            <div>
                                                <span class="text-gray-400 dark:text-gray-500 block">Confidence</span>
                                                <span class="text-blue-600 font-bold">{{ round($history->snapshot_data['confidence'] * 100) }}%</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-5 text-center">
                            <p class="text-gray-400 dark:text-gray-500 text-sm">Belum ada riwayat perubahan.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
