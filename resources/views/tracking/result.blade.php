@extends('layouts.app')

@section('title', 'Hasil Tracking — ' . $alumni->nama_lengkap)
@section('page-title', 'Hasil Tracking')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex items-center gap-2 text-sm">
        <a href="{{ route('tracking.index') }}" class="text-blue-600 hover:text-blue-800">← Tracking Monitor</a>
        <span class="text-gray-400 dark:text-gray-500">|</span>
        <a href="{{ route('alumni.show', $alumni->nim) }}" class="text-blue-600 hover:text-blue-800">Profil Alumni</a>
    </div>

    {{-- Alumni Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                <span class="text-lg font-bold text-blue-600">{{ strtoupper(substr($alumni->nama_lengkap, 0, 2)) }}</span>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $alumni->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $alumni->nim }} · {{ $alumni->prodi }} · Lulus
                    {{ $alumni->tahun_lulus }}</p>
            </div>
            <div class="flex items-center gap-3">
                @include('components.status-badge', ['status' => $alumni->tracking_status])
                <form id="retrack-form" method="POST" action="{{ route('tracking.single', $alumni->nim) }}" onsubmit="handleRetrack(event)">
                    @csrf
                    <button type="submit"
                        class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                        <span>🔄</span> Lacak Ulang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Result --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tracking Result --}}
            {{-- Tracking Result - Section 1: Verifikasi & Confidence --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-5">
                    @if ($latestResult)
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
                    @else
                        <p class="text-center text-gray-400 dark:text-gray-500 text-sm py-6">Belum ada hasil tracking untuk alumni ini.</p>
                    @endif
                </div>
            </div>

            @if($latestResult)
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
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Evidence Log ({{ $evidenceLogs->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    @forelse($evidenceLogs as $evidence)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="text-xs font-medium px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded">{{ strtoupper(str_replace('_', ' ', $evidence->source_type)) }}</span>
                                <span
                                    class="text-xs text-gray-400 dark:text-gray-500">{{ $evidence->searched_at ? $evidence->searched_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                            <a href="{{ $evidence->source_url }}" target="_blank"
                                class="text-xs text-blue-600 hover:underline break-all block">{{ Str::limit($evidence->source_url, 100) }}</a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 mt-1">{{ Str::limit($evidence->raw_snippet, 200) }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 dark:text-gray-500 text-sm">Belum ada evidence.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Search Queries --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Query Pencarian ({{ $searchQueries->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-72 overflow-y-auto">
                    @forelse($searchQueries as $sq)
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-700 dark:text-gray-200 font-mono break-all">{{ $sq->query_text }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $sq->result_count }} hasil</span>
                                <span
                                    class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 dark:text-gray-500 rounded">{{ $sq->source_type }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 dark:text-gray-500 text-sm">Belum ada query.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Riwayat ({{ $histories->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-72 overflow-y-auto">
                    @forelse($histories as $history)
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-700 dark:text-gray-200 font-medium">{{ $history->changed_reason ?? 'Update data' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $history->created_at ? $history->created_at->format('d M Y H:i') : '-' }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 dark:text-gray-500 text-sm">Belum ada riwayat.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleRetrack(event) {
            event.preventDefault();
            const form = event.target;
            const btn = form.querySelector('button');
            const nim = '{{ $alumni->nim }}';

            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block animate-spin">⏳</span> Memproses...`;

            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                startPolling(nim);
            })
            .catch(error => {
                form.submit(); // Fallback
            });
        }

        function startPolling(nim) {
            let container = document.getElementById('tracking-progress-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'tracking-progress-container';
                container.className = 'mb-6 bg-blue-50 border border-blue-100 rounded-xl p-5';
                document.querySelector('main').prepend(container);
            }

            container.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center animate-pulse">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900" id="poll-message">Menghubungkan ke sistem pelacakan...</h4>
                            <p class="text-[11px] text-blue-600">Proses ini memakan waktu sekitar 30-60 detik</p>
                        </div>
                    </div>
                    <span class="text-sm font-black text-blue-600" id="poll-percent">0%</span>
                </div>
                <div class="w-full h-3 bg-blue-200 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 shadow-[0_0_10px_rgba(37,99,235,0.5)] transition-all duration-700 ease-out" id="poll-bar" style="width: 0%"></div>
                </div>
            `;

            const interval = setInterval(() => {
                fetch(`/tracking/${nim}/progress`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'not_found' || !data.progress) return;

                        document.getElementById('poll-message').innerText = data.message;
                        document.getElementById('poll-percent').innerText = data.progress + '%';
                        document.getElementById('poll-bar').style.width = data.progress + '%';

                        if (data.progress >= 100) {
                            clearInterval(interval);
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        }
                    });
            }, 1000);
        }
    </script>
@endpush
