<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\ExternalServices;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Src\Requests\Request\Domain\Contracts\ResolutionDocumentGeneratorInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;
use Src\Requests\Request\Domain\ValueObjects\ResolutionData;
use Symfony\Component\Process\Process;

/**
 * Produces the constancia archived on the request when Registro issues a
 * resolution. Written to the same disk and directory as student uploads,
 * so the resulting RequestAttachment flows through the existing
 * persistence and download pipeline with no special-casing.
 *
 * Two strategies, one per request type:
 *  - Requirement Waiver: fills the real institutional SLR-002 AcroForm
 *    through scripts/fill_slr002.py, since PHP has no form-filling
 *    library available here.
 *  - Validation: renders a Blade view to PDF, because the official
 *    RSREC-001 is a binary .doc that cannot be filled without Word or
 *    LibreOffice on the host.
 *
 * Uses the same Browsershot configuration as SpatiePdfExporter, but not
 * PdfExporterInterface itself: that port returns a StreamedResponse,
 * while this document has to land on disk.
 */
final class BrowsershotResolutionDocumentGenerator implements ResolutionDocumentGeneratorInterface
{
    /**
     * WaiverRequestForm::JUSTIFICATIONS, in SLR-002's own a..e order,
     * mapped to the template's checkbox field names.
     *
     * @var array<string, string>
     */
    private const JUSTIFICATION_CHECKBOXES = [
        'Only Pending Requirement' => 'Button15',
        'Final Level Parallel Enrollment' => 'Button16',
        'Delayed Course Offering' => 'Button17',
        'Minimum Academic Load' => 'Button18',
        'Prior Knowledge Sufficiency' => 'Button19',
    ];

    public function generate(Request $request, ResolutionData $resolution): RequestAttachment
    {
        if ($request->type() === 'Requirement Waiver') {
            return $this->fillOfficialWaiverForm($request, $resolution);
        }

        return $this->renderValidationResolution($request, $resolution);
    }

    /**
     * Fills the blank SLR-002 with the student's identification, career,
     * requested course and chosen justification.
     *
     * "Sede" is left blank on purpose: the module does not track a
     * student's campus, and this document must not carry invented data.
     */
    private function fillOfficialWaiverForm(Request $request, ResolutionData $resolution): RequestAttachment
    {
        $student = StudentModel::query()->with('studyPlans.career')->find($request->studentId());
        $course = CourseModel::query()->find($request->courseId());

        // students.last_name holds both apellidos ("Guillén Mora") — the
        // form wants them split; a single-word last name just leaves the
        // second box empty.
        $lastNameParts = explode(' ', trim((string) $student?->last_name), 2);

        $spec = [
            'template' => resource_path('documents/slr-002-levantamiento.pdf'),
            'output' => Storage::disk('local')->path($path = 'requests/'.Str::random(40).'.pdf'),
            'fields' => [
                'Text1' => $lastNameParts[0] ?? '',
                'Text2' => $lastNameParts[1] ?? '',
                'Text3' => (string) $student?->name,
                'Text4' => (string) $student?->national_id,
                'Text6' => (string) $student?->studyPlans?->first()?->career?->name,
                'Text7' => (string) $course?->code,
                'Text12' => (string) $course?->name,
            ],
            'checkbox' => self::JUSTIFICATION_CHECKBOXES[$request->waiverJustification()] ?? null,
        ];

        $specPath = Storage::disk('local')->path('requests/'.Str::random(40).'.json');
        file_put_contents($specPath, json_encode($spec, JSON_UNESCAPED_UNICODE));

        try {
            $process = new Process([env('SLR002_PYTHON', 'python'), base_path('scripts/fill_slr002.py'), $specPath]);
            $process->setTimeout(30);
            $process->mustRun();
        } finally {
            @unlink($specPath);
        }

        $pdfBytes = Storage::disk('local')->get($path);

        return new RequestAttachment(
            documentType: 'resolution',
            originalName: 'SLR-002 Resolución '.$resolution->resolutionNumber.'.pdf',
            disk: 'local',
            path: $path,
            mimeType: 'application/pdf',
            sizeBytes: strlen($pdfBytes),
            hashSha256: hash('sha256', $pdfBytes),
        );
    }

    private function renderValidationResolution(Request $request, ResolutionData $resolution): RequestAttachment
    {
        $student = StudentModel::query()->find($request->studentId());
        $course = CourseModel::query()->find($request->courseId());
        $requiredCourse = $request->requiredCourseId() !== null
            ? CourseModel::query()->find($request->requiredCourseId())
            : null;

        $html = view('requests.request.pdf.resolution', [
            'resolution' => $resolution,
            'issuedAt' => date('Y-m-d'),
            'isWaiver' => $request->type() === 'Requirement Waiver',
            'approved' => $request->status() === 'Approved by Registro',
            'studentLabel' => $student
                ? "{$student->name} {$student->last_name} ({$student->national_id})"
                : (string) $request->studentId(),
            'courseLabel' => $course ? "{$course->code} — {$course->name}" : (string) $request->courseId(),
            'requiredCourseLabel' => $requiredCourse ? "{$requiredCourse->code} — {$requiredCourse->name}" : null,
            'externalCourse' => $request->externalCourse(),
            'originInstitution' => $request->originInstitution(),
            'receivedAt' => $request->createdAt() !== null ? date('Y-m-d', strtotime($request->createdAt())) : null,
        ])->render();

        $pdfBytes = Pdf::html($html)
            ->format('letter')
            ->withBrowsershot(function (Browsershot $browsershot): void {
                $browsershot
                    ->setNodeModulePath(base_path('node_modules'))
                    ->timeout(15)
                    ->showBackground()
                    ->addChromiumArguments([
                        'disable-extensions',
                        'disable-background-networking',
                        'disable-default-apps',
                        'disable-sync',
                        'disable-translate',
                        'metrics-recording-only',
                        'mute-audio',
                        'no-first-run',
                        'safebrowsing-disable-auto-update',
                        'disable-gpu',
                        'disable-dev-shm-usage',
                        'disable-software-rasterizer',
                    ]);
            })
            ->generatePdfContent();

        $path = 'requests/'.Str::random(40).'.pdf';
        Storage::disk('local')->put($path, $pdfBytes);

        return new RequestAttachment(
            documentType: 'resolution',
            originalName: 'Resolución '.$resolution->resolutionNumber.'.pdf',
            disk: 'local',
            path: $path,
            mimeType: 'application/pdf',
            sizeBytes: strlen($pdfBytes),
            hashSha256: hash('sha256', $pdfBytes),
        );
    }
}
