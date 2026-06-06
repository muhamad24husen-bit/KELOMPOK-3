<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\SubjectImport;
use App\Events\SubjectImportEvent;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class SubjectImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $programId;

    // Terima path file DAN programId
    public function __construct($path, $programId)
    {
        $this->path = $path;
        $this->programId = $programId;
    }

    public function handle()
    {
        if (!File::exists($this->path)) {
            event(new SubjectImportEvent('error', 'File tidak ditemukan di server.'));
            return;
        }

        try {
            // Import Data dengan menyuntikkan programId
            Excel::import(new SubjectImport($this->programId), $this->path);

            // Hapus file temp setelah sukses
            File::delete($this->path);

            event(new SubjectImportEvent('success', 'Import Mata Kuliah berhasil!'));

        } catch (ValidationException $e) {
            $failures = $e->failures();
            File::delete($this->path);

            // Ambil pesan error validasi pertama
            $errorMsg = isset($failures[0]) ? $failures[0]->errors()[0] : 'Kesalahan validasi data Excel.';
            event(new SubjectImportEvent('error', "Format salah: " . $errorMsg));

        } catch (\Exception $e) {
            File::delete($this->path);
            event(new SubjectImportEvent('error', 'Gagal sistem: ' . $e->getMessage()));
        }
    }
}
