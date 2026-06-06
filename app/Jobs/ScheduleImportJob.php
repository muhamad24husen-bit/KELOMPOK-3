<?php

namespace App\Jobs;

use App\Imports\ScheduleImport;
use App\Events\ScheduleImportEvent;
use App\Imports\TeacherImport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ScheduleImportJob implements ShouldQueue
{
    use Queueable;

    protected $path;
    protected $programId;

    public function __construct($path, $programId)
    {
        $this->path = $path;
        $this->programId = $programId;
    }


    public function handle()
    {
        if (!File::exists($this->path)) {
            event(new ScheduleImportEvent('error', 'File tidak ditemukan oleh worker.'));
            return;
        }

        try {
            Excel::import(new ScheduleImport($this->programId), $this->path);
            File::delete($this->path);

            // Mengirim sinyal sukses ke Reverb
            event(new ScheduleImportEvent('success', 'Import jadwal kuliah berhasil!'));

        } catch (ValidationException $e) {
            if (File::exists($this->path)) File::delete($this->path);

            $failures = $e->failures();
            $errorDetail = "Baris " . $failures[0]->row() . ": " . $failures[0]->errors()[0];
            event(new ScheduleImportEvent('error', 'Validasi Gagal: ' . $errorDetail));

        } catch (\Exception $e) {
            if (File::exists($this->path)) File::delete($this->path);
            event(new ScheduleImportEvent('error', 'Gagal: ' . $e->getMessage()));
        }
    }
}
