{{--
    RSREC-001 — the formal resolution document Registro emits when
    publishing its decision on a request. Rendered to PDF through the
    same Browsershot pipeline as the table exports (see
    BrowsershotResolutionDocumentGenerator); fully self-contained on
    purpose — no external fonts or assets — so rendering never makes a
    network call.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a2340;
            padding: 48px 56px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1f3c88;
            padding-bottom: 14px;
            margin-bottom: 28px;
        }
        .header h1 { font-size: 17px; color: #1f3c88; }
        .header .sub { font-size: 11.5px; color: #66708c; margin-top: 2px; }
        .header .meta { text-align: right; font-size: 11.5px; color: #66708c; }
        .title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .subtitle { text-align: center; font-size: 12px; color: #66708c; margin-bottom: 26px; }
        .session-box {
            display: flex;
            gap: 24px;
            background: #f0f3fa;
            border: 1px solid #d6ddef;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 26px;
            font-size: 12px;
        }
        .session-box div span { display: block; color: #66708c; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.4px; }
        .session-box div strong { font-size: 12.5px; }
        table.details { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        table.details td { padding: 7px 10px; border-bottom: 1px solid #e3e8f2; vertical-align: top; }
        table.details td:first-child { width: 38%; color: #66708c; }
        .decision {
            border-radius: 8px;
            padding: 16px 18px;
            margin-bottom: 26px;
            font-size: 13.5px;
        }
        .decision.approved { background: #e8f6ec; border: 1px solid #bfe3c9; color: #1c6b31; }
        .decision.denied { background: #fbeaea; border: 1px solid #efc4c4; color: #953030; }
        .footer { margin-top: 42px; font-size: 11px; color: #66708c; border-top: 1px solid #e3e8f2; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Universidad Técnica Nacional</h1>
            <div class="sub">Dirección de Registro Universitario</div>
        </div>
        <div class="meta">
            RSREC-001<br>
            {{ __('Issued on') }}: {{ $issuedAt }}
        </div>
    </div>

    <div class="title">{{ __('RESOLUTION') }} {{ $resolution->resolutionNumber }}</div>
    <div class="subtitle">
        {{ $isWaiver ? __('Requirement waiver request') : __('Course validation request') }}
    </div>

    <div class="session-box">
        <div>
            <span>{{ __('Session No.') }}</span>
            <strong>{{ $resolution->sessionNumber }}</strong>
        </div>
        <div>
            <span>{{ __('Act No.') }}</span>
            <strong>{{ $resolution->actNumber }}</strong>
        </div>
        <div>
            <span>{{ __('Session date') }}</span>
            <strong>{{ $resolution->sessionDate }}</strong>
        </div>
    </div>

    <table class="details">
        <tr>
            <td>{{ __('Student') }}</td>
            <td>{{ $studentLabel }}</td>
        </tr>
        <tr>
            <td>{{ __('Request type') }}</td>
            <td>{{ $isWaiver ? __('Requirement Waiver') : __('Course Validation') }}</td>
        </tr>
        <tr>
            <td>{{ __('Course') }}</td>
            <td>{{ $courseLabel }}</td>
        </tr>
        @if ($isWaiver && $requiredCourseLabel !== null)
        <tr>
            <td>{{ __('Unmet requirement') }}</td>
            <td>{{ $requiredCourseLabel }}</td>
        </tr>
        @endif
        @if (! $isWaiver && $externalCourse !== null)
        <tr>
            <td>{{ __('External course name') }}</td>
            <td>{{ $externalCourse }}</td>
        </tr>
        @endif
        @if (! $isWaiver && $originInstitution !== null)
        <tr>
            <td>{{ __('Origin institution') }}</td>
            <td>{{ $originInstitution }}</td>
        </tr>
        @endif
        <tr>
            <td>{{ __('Received date') }}</td>
            <td>{{ $receivedAt ?? '—' }}</td>
        </tr>
    </table>

    <div class="decision {{ $approved ? 'approved' : 'denied' }}">
        @if ($approved)
        {{ $isWaiver
            ? __('The Universidad Técnica Nacional resolves to APPROVE the requirement waiver request described above.')
            : __('The Universidad Técnica Nacional resolves to APPROVE the course validation request described above.') }}
        @else
        {{ $isWaiver
            ? __('The Universidad Técnica Nacional resolves to DENY the requirement waiver request described above.')
            : __('The Universidad Técnica Nacional resolves to DENY the course validation request described above.') }}
        @endif
    </div>

    <div class="footer">
        {{ __('This resolution was issued by the Dirección de Registro Universitario and is archived with the request record.') }}
    </div>
</body>
</html>
