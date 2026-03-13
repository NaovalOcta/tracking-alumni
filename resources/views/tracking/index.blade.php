@extends('layouts.app')

@section('title', 'Tracking Monitor')
@section('page-title', 'Tracking Monitor')

@section('content')
    {{-- API Status Banner --}}
    @unless ($apiStatus['ready'])
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm mb-6">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <strong>API belum dikonfigurasi</strong>
            </div>
            <p class="ml-7 mb-1">Tracking membutuhkan API key berikut di file <code>.env</code>:</p>
            <ul class="ml-7 list-disc list-inside space-y-1">
                @unless ($apiStatus['serper'])
                    <li><code>SERPER_API_KEY</code> — Serper.dev Search API</li>
                @endunless
                @unless ($apiStatus['gemini'])
                    <li><code>GEMINI_API_KEY</code> — Google Gemini AI API</li>
                @endunless
            </ul>
        </div>
    @endunless

    {{-- Tracking Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-500">{{ $stats['belum_dilacak'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Belum Dilacak</p>
        </div>
        <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['sedang_dilacak'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Sedang Proses</p>
        </div>
        <div class="bg-white rounded-xl border border-green-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['auto_verified'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Terverifikasi</p>
        </div>
        <div class="bg-white rounded-xl border border-yellow-200 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['needs_audit'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Perlu Review</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['not_found'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Tidak Ditemukan</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Batch Tracking Controls --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-medium text-gray-900">Mulai Tracking</h3>
            </div>
            <div class="p-5 space-y-4">
                {{-- Batch Tracking --}}
                <form method="POST" action="{{ route('tracking.batch') }}">
                    @csrf
                    <p class="text-sm text-gray-600 mb-3">Lacak alumni secara batch (berjalan di background via queue).</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Filter Status</label>
                            <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="belum_dilacak">Belum Dilacak ({{ $stats['belum_dilacak'] }})</option>
                                <option value="not_found">Tidak Ditemukan ({{ $stats['not_found'] }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Batas Alumni</label>
                            <input type="number" name="limit" value="10" min="1" max="100"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-2.5 px-4 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$apiStatus['ready'] ? 'disabled' : '' }}>
                            🚀 Mulai Tracking Batch
                        </button>
                    </div>
                </form>

                <hr class="border-gray-100">

                {{-- Single Tracking --}}
                <div>
                    <p class="text-sm text-gray-600 mb-3">Lacak alumni tertentu secara langsung (sinkron).</p>
                    <form method="POST" id="single-tracking-form" class="space-y-3">
                        @csrf
                        <select id="single-nim"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Pilih Alumni...</option>
                            @foreach ($readyForTracking as $alum)
                                <option value="{{ $alum->nim }}">{{ $alum->nim }} — {{ $alum->nama_lengkap }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="submitSingleTracking()"
                            class="w-full bg-gray-800 text-white py-2.5 px-4 rounded-lg text-sm font-medium hover:bg-gray-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$apiStatus['ready'] ? 'disabled' : '' }}>
                            🔍 Lacak Alumni
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Recent Results --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-medium text-gray-900">Hasil Tracking Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alumni</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instansi</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentResults as $result)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $result->alumni->nama_lengkap ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $result->alumni_nim }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ $result->jabatan ?? '-' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $result->instansi ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    @if ($result->confidence_score !== null)
                                        @php
                                            $score = $result->confidence_score;
                                            $color = $score >= 0.8 ? 'green' : ($score >= 0.5 ? 'yellow' : 'red');
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 h-2 bg-gray-200 rounded-full">
                                                <div class="h-2 bg-{{ $color }}-500 rounded-full"
                                                    style="width: {{ $score * 100 }}%"></div>
                                            </div>
                                            <span
                                                class="text-xs font-medium text-{{ $color }}-600">{{ round($score * 100) }}%</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('tracking.result', $result->alumni_nim) }}"
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Detail →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-gray-400 text-sm">
                                    Belum ada hasil tracking. Mulai tracking di panel sebelah kiri.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function submitSingleTracking() {
            const nim = document.getElementById('single-nim').value;
            if (!nim) {
                alert('Pilih alumni terlebih dahulu.');
                return;
            }
            const form = document.getElementById('single-tracking-form');
            form.action = '/tracking/' + nim;
            form.method = 'POST';
            form.submit();
        }
    </script>
@endpush
