<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AssetImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_file' => [
                'required',
                File::types(['xlsx'])->max(10 * 1024),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Pilih berkas Excel yang akan diimpor.',
            'import_file.file' => 'Unggahan harus berupa sebuah berkas.',
            'import_file.mimes' => 'Berkas harus menggunakan format .xlsx.',
            'import_file.max' => 'Ukuran berkas Excel maksimal 10 MB.',
        ];
    }
}
