@extends('layouts.app')

@section('title', 'Hasil Tracking — ' . $alumni->nama_lengkap)
@section('page-title', 'Hasil Tracking')

@section('content')
    {{-- Breadcrumb --}}
    <div class="mb-6 flex items-center gap-2 text-sm">
        <a href="{{ route('tracking.index') }}" class="text-blue-600 hover:text-blue-800">← Tracking Monitor</a>
        <span class="text-gray-400">|</span>
        <a href="{{ route('alumni.show', $alumni->nim) }}" class="text-blue-600 hover:text-blue-800">Profil Alumni</a>
    </div>

    {{-- Alumni Header --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center shrink-0">
                <span class="text-lg font-bold text-blue-600">{{ strtoupper(substr($alumni->nama_lengkap, 0, 2)) }}</span>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-gray-900">{{ $alumni->nama_lengkap }}</h2>
                <p class="text-sm text-gray-500">{{ $alumni->nim }} · {{ $alumni->prodi }} · Lulus
                    {{ $alumni->tahun_lulus }}</p>
            </div>
            <div class="flex items-center gap-3">
                @include('components.status-badge', ['status' => $alumni->tracking_status])
                <form method="POST" action="{{ route('tracking.single', $alumni->nim) }}">
                    @csrf
                    <button type="submit"
                        class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Lacak Ulang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Result --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Tracking Result --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Hasil Analisis AI</h3>
                </div>
                <div class="p-5">
                    @if ($latestResult)
                        {{-- Confidence Bar --}}
                        <div class="mb-5">
                            @php
                                $score = $latestResult->confidence_score ?? 0;
                                $color = $score >= 0.8 ? 'green' : ($score >= 0.5 ? 'yellow' : 'red');
                            @endphp
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-600">Skor Kepercayaan</span>
                                @if($latestResult->confidence_score !== null)
                                    <span class="text-sm font-bold text-{{ $color }}-600">{{ round($score * 100) }}%</span>
                                @else
                                    <span class="text-sm font-bold text-gray-400">-</span>
                                @endif
                            </div>
                            <div class="w-full h-3 bg-gray-200 rounded-full">
                                <div class="h-3 bg-{{ $color }}-500 rounded-full transition-all"
                                    style="width: {{ $score * 100 }}%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 block mb-0.5">Jabatan</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->jabatan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-0.5">Instansi</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->instansi ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-0.5">Bidang Pekerjaan</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->bidang_pekerjaan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block mb-0.5">Lokasi</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->lokasi ?? '-' }}</span>
                            </div>
                            @if ($latestResult->linkedin_url)
                                <div class="sm:col-span-2">
                                    <span class="text-gray-500 block mb-0.5">LinkedIn</span>
                                    <a href="{{ $latestResult->linkedin_url }}" target="_blank"
                                        class="text-blue-600 hover:underline break-all">{{ $latestResult->linkedin_url }}</a>
                                </div>
                            @endif
                        </div>

                        @if ($latestResult->ai_notes)
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <span class="text-gray-500 text-sm block mb-1">Catatan AI</span>
                                <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">{{ $latestResult->ai_notes }}
                                </p>
                            </div>
                        @endif
                    @else
                        <p class="text-center text-gray-400 text-sm py-6">Belum ada hasil tracking untuk alumni ini.</p>
                    @endif
                </div>
            </div>

            {{-- Evidence Logs --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Evidence Log ({{ $evidenceLogs->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    @forelse($evidenceLogs as $evidence)
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between mb-1">
                                <span
                                    class="text-xs font-medium px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{{ strtoupper(str_replace('_', ' ', $evidence->source_type)) }}</span>
                                <span
                                    class="text-xs text-gray-400">{{ $evidence->searched_at ? $evidence->searched_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                            <a href="{{ $evidence->source_url }}" target="_blank"
                                class="text-xs text-blue-600 hover:underline break-all block">{{ Str::limit($evidence->source_url, 100) }}</a>
                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($evidence->raw_snippet, 200) }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 text-sm">Belum ada evidence.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar Info --}}
        <div class="space-y-6">
            {{-- Search Queries --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Query Pencarian ({{ $searchQueries->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto">
                    @forelse($searchQueries as $sq)
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-700 font-mono break-all">{{ $sq->query_text }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-400">{{ $sq->result_count }} hasil</span>
                                <span
                                    class="text-xs px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">{{ $sq->source_type }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 text-sm">Belum ada query.</div>
                    @endforelse
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Riwayat ({{ $histories->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto">
                    @forelse($histories as $history)
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-700 font-medium">{{ $history->changed_reason ?? 'Update data' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $history->created_at ? $history->created_at->format('d M Y H:i') : '-' }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-400 text-sm">Belum ada riwayat.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
