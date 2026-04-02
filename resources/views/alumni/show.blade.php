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
            {{-- Tracking Result Container --}}
            @php $latestResult = $alumni->trackingResults->isNotEmpty() ? $alumni->trackingResults->sortByDesc('created_at')->first() : null; @endphp
            
            @if (!$latestResult)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Hasil Tracking Terkini</h3>
                </div>
                <div class="p-5 text-center py-6">
                    <p class="text-gray-400 dark:text-gray-500 text-sm">Belum ada hasil tracking.</p>
                </div>
            </div>
            @else

            {{-- Tracking Result - Section 1: Verifikasi & Confidence --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-5">
                    @php
                        $score = $latestResult->confidence_score ?? 0;
                        $color = $score >= 0.85 ? 'green' : ($score >= 0.5 ? 'yellow' : 'red');
                    @endphp
                    
                    <div class="flex flex-col md:flex-row justify-between mb-4 gap-4">
                        <div>
                            @if($latestResult->is_umm_verified)
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-medium dark:bg-green-900/30 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Terverifikasi Alumni UMM
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium dark:bg-yellow-900/30 dark:text-yellow-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Belum Terverifikasi UMM
                                </div>
                            @endif
                            
                            @if($latestResult->umm_evidence)
                                <p class="text-xs text-gray-500 mt-2 ml-1 italic border-l-2 border-gray-300 pl-2">"{{ Str::limit($latestResult->umm_evidence, 100) }}"</p>
                            @endif
                        </div>
                        
                        <div class="w-full md:max-w-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600 dark:text-gray-300">Skor Kepercayaan</span>
                                <span class="text-sm font-bold text-{{ $color }}-600">{{ round($score * 100) }}%</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-200 rounded-full dark:bg-gray-700">
                                <div class="h-2.5 bg-{{ $color }}-500 rounded-full transition-all" style="width: {{ $score * 100 }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                        @if($latestResult->tipe_posisi == 'current')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded-md dark:bg-blue-900/40 dark:text-blue-300">📌 Posisi Terkini {{ $latestResult->posisi_sejak ? '(sejak '.$latestResult->posisi_sejak.')' : '' }}</span>
                        @elseif($latestResult->tipe_posisi == 'past')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-gray-200 text-gray-800 rounded-md dark:bg-gray-700 dark:text-gray-300">📋 Posisi Sebelumnya</span>
                        @elseif($latestResult->tipe_posisi == 'internship_only')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-purple-100 text-purple-800 rounded-md dark:bg-purple-900/40 dark:text-purple-300">⏳ Riwayat Magang Saja</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Section 2: Informasi Pekerjaan --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">💼 Informasi Pekerjaan</h3>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Tempat Bekerja / Instansi</span>
                        <span class="text-gray-900 dark:text-white font-semibold">{{ $latestResult->instansi ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Posisi / Jabatan</span>
                        <span class="text-gray-900 dark:text-white font-semibold">{{ $latestResult->jabatan ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Alamat Bekerja / Lokasi</span>
                        <span class="text-gray-900 dark:text-white font-semibold">{{ $latestResult->lokasi ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">Kategori Pekerjaan</span>
                        <span class="text-gray-900 dark:text-white font-semibold">
                            @if($latestResult->kategori_pekerjaan === 'PNS')
                                🏛️ PNS
                            @elseif($latestResult->kategori_pekerjaan === 'Swasta')
                                🏢 Swasta
                            @elseif($latestResult->kategori_pekerjaan === 'Wirausaha')
                                🛍️ Wirausaha
                            @else
                                -
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Section 3: Sosial Media Alumni & Kontak --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">📱 Sosial Media Alumni & Kontak</h3>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
                        @php
                            $platforms = [
                                ['name' => 'LinkedIn', 'url' => $latestResult->linkedin_url, 'icon' => '🔗'],
                                ['name' => 'Instagram', 'url' => $latestResult->ig_url, 'icon' => '📸'],
                                ['name' => 'Facebook', 'url' => $latestResult->fb_url, 'icon' => '📘'],
                                ['name' => 'TikTok', 'url' => $latestResult->tiktok_url, 'icon' => '🎵'],
                            ];
                        @endphp
                        @foreach($platforms as $platform)
                        <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-3 bg-gray-50/50 dark:bg-gray-700/50 text-center">
                            <span class="text-sm block mb-1">{{ $platform['icon'] }} <span class="text-gray-600 dark:text-gray-300 font-medium">{{ $platform['name'] }}</span></span>
                            @if($platform['url'])
                                <a href="{{ $platform['url'] }}" target="_blank" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 hover:underline">Lihat Profil</a>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">Tidak ditemukan</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-md">
                            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-xl">📧</div>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">Email</span>
                                <span class="font-medium text-gray-900 dark:text-white break-all">{{ $latestResult->email ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-md">
                            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-xl">📞</div>
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">No Handphone</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $latestResult->no_hp ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: Sosial Media Instansi --}}
            @if($latestResult->instansi)
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-2">🏢 Sosial Media Instansi ({{ Str::limit($latestResult->instansi, 30) }})</h3>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @php
                        $instansiPlatforms = [
                            ['name' => 'LinkedIn', 'url' => $latestResult->sosmed_instansi_linkedin, 'icon' => '🔗'],
                            ['name' => 'Instagram', 'url' => $latestResult->sosmed_instansi_ig, 'icon' => '📸'],
                            ['name' => 'Facebook', 'url' => $latestResult->sosmed_instansi_fb, 'icon' => '📘'],
                            ['name' => 'TikTok', 'url' => $latestResult->sosmed_instansi_tiktok, 'icon' => '🎵'],
                        ];
                    @endphp
                    @foreach($instansiPlatforms as $platform)
                    <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-3 bg-gray-50/50 dark:bg-gray-700/50 flex justify-between items-center text-sm">
                        <span class="text-gray-600 dark:text-gray-300 font-medium">{{ $platform['icon'] }} {{ $platform['name'] }}</span>
                        @if($platform['url'])
                            <a href="{{ $platform['url'] }}" target="_blank" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 hover:underline">↳ Buka</a>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Section 5: Catatan AI --}}
            @if ($latestResult->ai_notes)
            <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-5 mb-6">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">🤖</span>
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Catatan Analisis AI</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $latestResult->ai_notes }}</p>
            </div>
            @endif

            @endif

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
