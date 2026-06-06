<?php

namespace App\Jobs;

use App\Events\PrintAllAssignmentEvent;
use App\Models\ST\Program;
use App\Models\ST\Teacher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Str;

class PrintAllAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $programId;
    protected $userId;

    public function __construct(int $programId, int $userId)
    {
        $this->programId = $programId;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        // --- DIAGNOSTIC LOGGING ---
        Log::info('--- PrintAllAssignmentJob Started (v.logging) ---');
        $imagePath = public_path('bse.png');
        $fileExists = file_exists($imagePath);
        Log::info("Checking for image at: " . $imagePath);
        Log::info("Does file exist? " . ($fileExists ? 'YES' : 'NO'));
        // --- END DIAGNOSTIC ---

        try {
            $program = Program::find($this->programId);
            if (!$program) {
                throw new \Exception("Program with ID {$this->programId} not found.");
            }

            $teachers = Teacher::query()
                ->whereHas('schedules', function ($query) {
                    $query->where('program_id', $this->programId);
                })
                ->with([
                    'schedules' => function ($query) {
                        $query->where('program_id', $this->programId);
                    },
                    'schedules.subject',
                ])
                ->orderBy('name')
                ->get();

            if ($teachers->isEmpty()) {
                event(new PrintAllAssignmentEvent('error', 'No schedule data to print.', null, $this->userId));
                return;
            }

            $programSlug = Str::slug($program->name);
            $timestamp = now()->format('Ymd-His');
            $fileName = "all-assignments-{$programSlug}-{$timestamp}.pdf";
            $relativePath = 'public/temp-pdf/' . $fileName;
            $fullPath = storage_path('app/' . $relativePath);

            Storage::makeDirectory('public/temp-pdf');

            Pdf::view('pdf.program.assignment.print-multiple', ['teachers' => $teachers])
                ->format('legal')
                ->margins(5, 5, 10, 5)
                ->footerView('pdf.program.assignment.footer')
                ->landscape()
                ->save($fullPath);

            $downloadUrl = Storage::url('temp-pdf/' . $fileName);

            event(new PrintAllAssignmentEvent('success', 'PDF for all assignments has been generated!', $downloadUrl, $this->userId));

        } catch (\Exception $e) {
            Log::error('PDF Generation Failed: ' . $e->getMessage()); // Log the actual error
            event(new PrintAllAssignmentEvent('error', 'Failed to generate PDF: ' . $e->getMessage(), null, $this->userId));
        }
    }
}
