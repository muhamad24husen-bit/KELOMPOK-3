<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Imports\TeacherImport;
use App\Events\TeacherImportEvent;

class TeacherImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $programId;

    public function __construct($path, $programId)
    {
        $this->path = $path;
        $this->programId = $programId;
    }

    public function handle()
    {
        // Debug: Jika masih error, pesan ini akan memberitahu path mana yang dicari worker
        if (!\Illuminate\Support\Facades\File::exists($this->path)) {
            event(new TeacherImportEvent('error', 'Worker mencari di: ' . $this->path));
            return;
        }

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new TeacherImport($this->programId), $this->path);

            \Illuminate\Support\Facades\File::delete($this->path);

            event(new TeacherImportEvent('success', 'Import data dosen berhasil!'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            \Illuminate\Support\Facades\File::delete($this->path);
            event(new TeacherImportEvent('error', "Format salah: " . $failures[0]->errors()[0]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\File::delete($this->path);
            event(new TeacherImportEvent('error', 'Gagal: ' . $e->getMessage()));
        }
    }
}
