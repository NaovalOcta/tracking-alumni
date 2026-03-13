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
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                {{-- Avatar --}}
                <div class="flex flex-col items-center mb-6">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                        <span class="text-2xl font-bold text-blue-600">
                            {{ strtoupper(substr($alumni->nama_lengkap, 0, 2)) }}
                        </span>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 text-center">{{ $alumni->nama_lengkap }}</h2>
                    <p class="text-sm text-gray-500 font-mono">{{ $alumni->nim }}</p>
                    <div class="mt-2">
                        @include('components.status-badge', ['status' => $alumni->tracking_status])
                    </div>
                </div>

                {{-- Info --}}
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prodi</span>
                        <span class="text-gray-900 font-medium">{{ $alumni->prodi }}</span>
                    </div>
                    @if ($alumni->fakultas)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Fakultas</span>
                            <span class="text-gray-900 font-medium">{{ $alumni->fakultas }}</span>
                        </div>
                    @endif
                    @if ($alumni->tahun_masuk)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tahun Masuk</span>
                            <span class="text-gray-900 font-medium">{{ $alumni->tahun_masuk }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tahun Lulus</span>
                        <span class="text-gray-900 font-medium">{{ $alumni->tahun_lulus }}</span>
                    </div>
                    @if ($alumni->email)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Email</span>
                            <span class="text-gray-900 font-medium text-right break-all">{{ $alumni->email }}</span>
                        </div>
                    @endif
                    @if ($alumni->no_telepon)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Telepon</span>
                            <span class="text-gray-900 font-medium">{{ $alumni->no_telepon }}</span>
                        </div>
                    @endif
                    @if ($alumni->nama_variasi)
                        <div>
                            <span class="text-gray-500 block mb-1">Variasi Nama</span>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($alumni->nama_variasi as $variasi)
                                    <span
                                        class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">{{ $variasi }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="mt-6 pt-4 border-t border-gray-200 flex gap-2">
                    <a href="{{ route('alumni.edit', $alumni->nim) }}"
                        class="flex-1 text-center bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
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
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Hasil Tracking Terkini</h3>
                </div>
                <div class="p-5">
                    @if ($alumni->trackingResults->isNotEmpty())
                        @php $latestResult = $alumni->trackingResults->sortByDesc('created_at')->first(); @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 block">Jabatan</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->jabatan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Instansi</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->instansi ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Bidang</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->bidang_pekerjaan ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 block">Lokasi</span>
                                <span class="text-gray-900 font-medium">{{ $latestResult->lokasi ?? '-' }}</span>
                            </div>
                            @if ($latestResult->confidence_score)
                                <div>
                                    <span class="text-gray-500 block">Skor Kepercayaan</span>
                                    <span
                                        class="text-gray-900 font-medium">{{ number_format($latestResult->confidence_score * 100, 0) }}%</span>
                                </div>
                            @endif
                            @if ($latestResult->linkedin_url)
                                <div>
                                    <span class="text-gray-500 block">LinkedIn</span>
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
                        <div class="text-center py-6">
                            <p class="text-gray-400 text-sm">Belum ada hasil tracking.</p>
                            <p class="text-gray-300 text-xs mt-1">Fitur tracking akan tersedia di Fase 2.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Evidence Logs --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Jejak Bukti (Evidence)</h3>
                </div>
                <div class="p-5">
                    @if ($alumni->evidenceLogs->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($alumni->evidenceLogs->sortByDesc('searched_at') as $evidence)
                                <div class="border border-gray-100 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-xs font-medium px-2 py-0.5 bg-gray-100 text-gray-600 rounded">{{ strtoupper(str_replace('_', ' ', $evidence->source_type)) }}</span>
                                        <span
                                            class="text-xs text-gray-400">{{ $evidence->searched_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <a href="{{ $evidence->source_url }}" target="_blank"
                                        class="text-xs text-blue-600 hover:underline break-all">{{ Str::limit($evidence->source_url, 80) }}</a>
                                    <p class="text-xs text-gray-500 mt-1">{{ Str::limit($evidence->raw_snippet, 150) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 text-sm py-6">Belum ada evidence log.</p>
                    @endif
                </div>
            </div>

            {{-- Tracking History --}}
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-sm font-medium text-gray-900">Riwayat Perubahan</h3>
                </div>
                <div class="p-5">
                    @if ($alumni->trackingHistories->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($alumni->trackingHistories->sortByDesc('created_at') as $history)
                                <div class="border border-gray-100 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span
                                            class="text-sm text-gray-700 font-medium">{{ $history->changed_reason ?? 'Update data' }}</span>
                                        <span
                                            class="text-xs text-gray-400">{{ $history->created_at ? $history->created_at->format('d M Y H:i') : '-' }}</span>
                                    </div>
                                    <pre class="text-xs bg-gray-50 p-2 rounded overflow-x-auto">{{ json_encode($history->snapshot_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-400 text-sm py-6">Belum ada riwayat perubahan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
