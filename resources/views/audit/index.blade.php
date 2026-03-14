@extends('layouts.app')

@section('title', 'Audit Trail - AlumniFinder')

@section('header', 'Audit Trail')

@section('content')
<div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-yellow-100 dark:border-yellow-900/40 transition-colors">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Perlu Audit</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $alumni->total() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit List -->
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700/50 flex justify-between items-center sm:flex-row flex-col gap-4">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Daftar Tunggu Audit</h3>
            <form action="{{ route('audit.index') }}" method="GET" class="relative w-full sm:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIM..." 
                    class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 w-full sm:w-64 text-sm text-gray-900 dark:text-white transition-colors">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700/50">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Alumni</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hasil Tracking AI</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Skor Kepercayaan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700/50 transition-colors">
                    @forelse($alumni as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="shrink-0 h-10 w-10">
                                        @if($item->foto)
                                            <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $item->foto) }}" alt="">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                                                {{ substr($item->nama_lengkap, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $item->nama_lengkap }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->nim }} • {{ $item->prodi }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->latestTrackingResult)
                                    <div class="text-sm text-gray-900 dark:text-gray-200 font-medium">{{ $item->latestTrackingResult->jabatan }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->latestTrackingResult->instansi }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                        <a href="{{ $item->latestTrackingResult->linkedin_url }}" target="_blank" class="hover:underline flex items-center">
                                            <span>LinkedIn Profil</span>
                                            <svg class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Data tidak tersedia</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($item->latestTrackingResult)
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mr-2">
                                            <div class="bg-yellow-400 h-1.5 rounded-full" style="width: {{ $item->latestTrackingResult->confidence_score * 100 }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ round($item->latestTrackingResult->confidence_score * 100) }}%</span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 italic">Ambigu / Perlu Konfirmasi</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <div class="flex justify-end space-x-2">
                                    <form action="{{ route('audit.verify', $item->nim) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center shadow-sm">
                                            <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Verifikasi
                                        </button>
                                    </form>
                                    <form action="{{ route('audit.reject', $item->nim) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center shadow-sm">
                                            <svg class="h-3.5 w-3.5 mr-1 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Tolak
                                        </button>
                                    </form>
                                    <a href="{{ route('tracking.result', $item->nim) }}" class="bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center shadow-sm">
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-200 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada data yang memerlukan audit saat ini.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($alumni->hasPages())
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/50">
                {{ $alumni->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
