@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan Sistem')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/50">
                <h3 class="text-base font-semibold text-gray-900">Penjadwalan Otomatis (Automated Tracking)</h3>
                <p class="text-xs text-gray-500 mt-0.5">Atur kapan sistem harus mulai melacak data alumni secara otomatis di latar belakang.</p>
            </div>
            
            <form action="{{ route('settings.update') }}" method="POST" class="p-6 space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Frequency --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Frekuensi Pelacakan</label>
                        <select name="tracking_frequency" id="tracking_frequency" onchange="toggleFrequencyOptions()"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="daily" {{ $config['tracking_frequency'] == 'daily' ? 'selected' : '' }}>Setiap Hari</option>
                            <option value="weekly" {{ $config['tracking_frequency'] == 'weekly' ? 'selected' : '' }}>Setiap Minggu</option>
                            <option value="monthly" {{ $config['tracking_frequency'] == 'monthly' ? 'selected' : '' }}>Setiap Bulan</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-2 italic">*Sistem akan melacak data alumni yang belum terverifikasi secara berkala.</p>
                    </div>

                    {{-- Time --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Waktu Eksekusi (Jam:Menit)</label>
                        <input type="time" name="tracking_time" value="{{ $config['tracking_time'] }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        <p class="text-[11px] text-gray-400 mt-2 italic">*Disarankan pada waktu minim trafik (misal: 02:00 pagi).</p>
                    </div>

                    {{-- Weekly Option --}}
                    <div id="weekly_option" class="{{ $config['tracking_frequency'] == 'weekly' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hari dalam Seminggu</label>
                        <select name="tracking_day_of_week"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <option value="1" {{ $config['tracking_day_of_week'] == '1' ? 'selected' : '' }}>Senin</option>
                            <option value="2" {{ $config['tracking_day_of_week'] == '2' ? 'selected' : '' }}>Selasa</option>
                            <option value="3" {{ $config['tracking_day_of_week'] == '3' ? 'selected' : '' }}>Rabu</option>
                            <option value="4" {{ $config['tracking_day_of_week'] == '4' ? 'selected' : '' }}>Kamis</option>
                            <option value="5" {{ $config['tracking_day_of_week'] == '5' ? 'selected' : '' }}>Jumat</option>
                            <option value="6" {{ $config['tracking_day_of_week'] == '6' ? 'selected' : '' }}>Sabtu</option>
                            <option value="0" {{ $config['tracking_day_of_week'] == '0' ? 'selected' : '' }}>Minggu</option>
                        </select>
                    </div>

                    {{-- Monthly Option --}}
                    <div id="monthly_option" class="{{ $config['tracking_frequency'] == 'monthly' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal dalam Bulan</label>
                        <select name="tracking_day_of_month"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            @for ($i = 1; $i <= 31; $i++)
                                <option value="{{ $i }}" {{ $config['tracking_day_of_month'] == $i ? 'selected' : '' }}>Tanggal {{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900">Status Otomatisasi</h4>
                                <p class="text-[11px] text-gray-500">Aktifkan atau matikan seluruh jadwal pelacakan otomatis secara global.</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="tracking_enabled" value="1" class="sr-only peer" {{ $config['tracking_enabled'] == '1' ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2 px-1">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Kriteria Filter Otomatis
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tujuan Program Studi</label>
                            <select name="tracking_filter_prodi" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                <option value="">Semua Program Studi</option>
                                @foreach($prodiList as $prodi)
                                    <option value="{{ $prodi }}" {{ $config['tracking_filter_prodi'] == $prodi ? 'selected' : '' }}>{{ $prodi }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-2 italic">*Hanya lacak alumni dari prodi terpilih saat jadwal tercapai.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tahun Lulus Specific</label>
                            <input type="number" name="tracking_filter_year" value="{{ $config['tracking_filter_year'] }}" placeholder="Contoh: 2023"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            <p class="text-[11px] text-gray-400 mt-2 italic">*Kosongkan untuk melacak semua tahun lulus.</p>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 text-white py-2.5 px-6 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-all shadow-sm shadow-blue-200">
                        Simpan Konfigurasi
                    </button>
                </div>
            </form>
        </div>

        {{-- Info Card --}}
        <div class="mt-6 bg-blue-50 rounded-xl border border-blue-100 p-5 flex gap-4">
            <div class="shrink-0 w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-blue-900 mb-1">Cara Kerja Penjadwalan</h4>
                <p class="text-xs text-blue-700 leading-relaxed">
                    Sistem menggunakan <strong>Laravel Scheduler</strong> yang terintegrasi dengan <strong>Cron Job</strong> server Anda. 
                    Sistem akan secara otomatis membaca pengaturan di atas setiap menit dan mengeksekusi proses pelacakan massal hanya pada waktu yang telah Anda tentukan.
                    Pastikan antrean (queue worker) Anda dalam keadaan aktif.
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleFrequencyOptions() {
            const freq = document.getElementById('tracking_frequency').value;
            const weekly = document.getElementById('weekly_option');
            const monthly = document.getElementById('monthly_option');
            
            weekly.classList.add('hidden');
            monthly.classList.add('hidden');
            
            if (freq === 'weekly') {
                weekly.classList.remove('hidden');
            } else if (freq === 'monthly') {
                monthly.classList.remove('hidden');
            }
        }
    </script>
@endsection
