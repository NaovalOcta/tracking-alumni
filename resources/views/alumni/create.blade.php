@extends('layouts.app')

@section('title', 'Tambah Alumni')
@section('page-title', 'Tambah Alumni')

@section('content')
    <div class="max-w-2xl">
        {{-- Breadcrumb --}}
        <div class="mb-6">
            <a href="{{ route('alumni.index') }}" class="text-sm text-blue-600 hover:text-blue-800">← Kembali ke Data
                Alumni</a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Form Tambah Alumni</h2>

            <form method="POST" action="{{ route('alumni.store') }}" class="space-y-5">
                @csrf

                {{-- NIM --}}
                <div>
                    <label for="nim" class="block text-sm font-medium text-gray-700 mb-1">NIM <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nim" id="nim" value="{{ old('nim') }}" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none @error('nim') border-red-500 @enderror"
                        placeholder="Contoh: 202010370311170">
                    @error('nim')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Lengkap --}}
                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="{{ old('nama_lengkap') }}" required
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none @error('nama_lengkap') border-red-500 @enderror"
                        placeholder="Contoh: Muhammad Rizky Pratama">
                    @error('nama_lengkap')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Variasi --}}
                <div>
                    <label for="nama_variasi" class="block text-sm font-medium text-gray-700 mb-1">Variasi Nama</label>
                    <input type="text" name="nama_variasi" id="nama_variasi" value="{{ old('nama_variasi') }}"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        placeholder="Pisahkan dengan koma: M. Rizky, Muh. Rizky">
                    <p class="mt-1 text-xs text-gray-400">Variasi nama yang mungkin digunakan alumni (opsional)</p>
                </div>

                {{-- Email & Telepon --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none @error('email') border-red-500 @enderror"
                            placeholder="email@example.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                        <input type="text" name="no_telepon" id="no_telepon" value="{{ old('no_telepon') }}"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="081234567890">
                    </div>
                </div>

                {{-- Prodi & Fakultas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="prodi" class="block text-sm font-medium text-gray-700 mb-1">Program Studi <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="prodi" id="prodi" value="{{ old('prodi') }}" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none @error('prodi') border-red-500 @enderror"
                            placeholder="Contoh: Informatika">
                        @error('prodi')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="fakultas" class="block text-sm font-medium text-gray-700 mb-1">Fakultas</label>
                        <input type="text" name="fakultas" id="fakultas" value="{{ old('fakultas') }}"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            placeholder="Contoh: Fakultas Teknik">
                    </div>
                </div>

                {{-- Tahun Masuk & Lulus --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="tahun_masuk" class="block text-sm font-medium text-gray-700 mb-1">Tahun Masuk</label>
                        <input type="number" name="tahun_masuk" id="tahun_masuk" value="{{ old('tahun_masuk') }}"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            min="1900" max="{{ date('Y') + 1 }}" placeholder="Contoh: 2020">
                    </div>
                    <div>
                        <label for="tahun_lulus" class="block text-sm font-medium text-gray-700 mb-1">Tahun Lulus <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="tahun_lulus" id="tahun_lulus" value="{{ old('tahun_lulus') }}" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none @error('tahun_lulus') border-red-500 @enderror"
                            min="1900" max="{{ date('Y') + 5 }}" placeholder="Contoh: 2024">
                        @error('tahun_lulus')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                    <button type="submit"
                        class="bg-blue-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        Simpan Alumni
                    </button>
                    <a href="{{ route('alumni.index') }}"
                        class="text-gray-600 px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
