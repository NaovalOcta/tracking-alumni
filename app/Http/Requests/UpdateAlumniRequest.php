<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Laravel singularizes resource names: 'alumni' → 'alumnus'
        // So the route parameter is {alumnus}, not {alumni}
        $currentNim = (string) $this->route('alumnus');

        return [
            'nim' => [
                'required',
                'string',
                'max:15',
                'regex:/^\d+$/',
                'unique:alumni,nim,' . $currentNim . ',nim',
            ],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nama_variasi' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'prodi' => ['required', 'string', 'max:100'],
            'fakultas' => ['nullable', 'string', 'max:100'],
            'tahun_masuk' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'tahun_lulus' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 5)],
        ];
    }

    public function messages(): array
    {
        return [
            'nim.required' => 'NIM wajib diisi.',
            'nim.regex' => 'NIM harus berupa angka.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'prodi.required' => 'Program studi wajib diisi.',
            'tahun_lulus.required' => 'Tahun lulus wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ];
    }
}
