<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ST\Teacher;
use App\Models\ST\Program;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Events\PrintAssignmentEvent;
use Illuminate\Support\Str;

class PrintAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $teacherIds;
    protected $programId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $teacherIds, int $programId, int $userId)
    {
        $this->teacherIds = $teacherIds;
        $this->programId = $programId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $program = Program::find($this->programId);
            if (!$program) {
                throw new \Exception("Program with ID {$this->programId} not found.");
            }

            $teachers = Teacher::query()
                ->with(['schedules' => function ($query) {
                    $query->whereHas('program', function ($q) {
                        $q->where('id', $this->programId);
                    });
                }])
                ->whereIn('id', $this->teacherIds)
                ->get();

            if ($teachers->isEmpty()) {
                event(new PrintAssignmentEvent('error', 'Teacher data not found.', null, $this->userId));
                return;
            }

            $teacherName = Str::slug($teachers->first()->name);
            $programSlug = Str::slug($program->name);
            $fileName = "Jadwal-{$teacherName}-{$programSlug}.pdf";
            $relativePath = 'public/temp-pdf/' . $fileName;
            $fullPath = storage_path('app/' . $relativePath);

            Storage::makeDirectory('public/temp-pdf');

            Pdf::view('pdf.program.assignment.print-single', ['teachers' => $teachers])
                ->paperSize(330, 210, 'mm')
                ->margins(20, 10, 20, 10)
                ->footerView('pdf.footer')
                ->save($fullPath);

            $downloadUrl = Storage::url('temp-pdf/' . $fileName);

            event(new PrintAssignmentEvent('success', 'PDF successfully generated!', $downloadUrl, $this->userId));

        } catch (\Exception $e) {
            event(new PrintAssignmentEvent('error', 'Failed to generate PDF: ' . $e->getMessage(), null, $this->userId));
        }
    }
}
