<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #153028; font-size: 11px; }
        .top { border-top: 9px solid #075f4c; border-bottom: 3px solid #d6a62e; padding: 24px 0 20px; text-align: center; }
        .top h1 { font-family: DejaVu Serif, serif; margin: 0; font-size: 26px; }
        .top p { margin: 6px 0 0; color: #52665d; }
        .token-block { padding: 22px 0 18px; text-align: center; }
        .token { font-size: 18px; font-weight: bold; letter-spacing: 1.5px; color: #075f4c; margin: 0 0 5px; }
        .label { font-size: 9px; text-transform: uppercase; color: #66766e; }
        .value { font-size: 13px; font-weight: bold; margin-top: 4px; }
        .details-box { border: 2px solid #075f4c; padding: 0; }
        .details-heading { background: #075f4c; color: #ffffff; font-size: 10px; font-weight: bold; letter-spacing: 1px; padding: 9px 12px; text-transform: uppercase; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 50%; border-bottom: 1px solid #cad8d1; padding: 13px 12px; vertical-align: top; }
        .grid td + td { border-left: 1px solid #cad8d1; }
        .grid tr:last-child td { border-bottom: 0; }
        .requirement td { background: #f4f7f5; }
        .requirement .value { color: #075f4c; font-size: 16px; }
        .notes { border-top: 1px solid #cad8d1; padding: 13px 12px; }
        .notes .value { font-size: 11px; font-weight: normal; line-height: 1.55; white-space: pre-wrap; }
        .footer { margin-top: 30px; border-top: 1px solid #ccd8d1; padding-top: 12px; color: #69776f; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $requirementLabel = match (true) {
            $token->isVA() => 'Required visa attestations',
            $token->isChangePreWorker() => 'Workers requiring change',
            default => 'Demanded workers',
        };
        $requirementValue = $token->required_visa_attestation
            ?? $token->required_worker_changes
            ?? $token->demanded_workers;
    @endphp

    <div class="top">
        <h1>Bangladesh High Commission</h1>
        <p>Brunei Darussalam &middot; Visa &amp; Worker Management System</p>
    </div>
    <div class="token-block">
        <div class="token">{{ $token->token_number }}</div>
        <div class="label">Official submission token</div>
    </div>
    <div class="details-box">
        <div class="details-heading">Submission details</div>
        <table class="grid">
            <tr><td><div class="label">Company</div><div class="value">{{ $token->company->name }}</div></td><td><div class="label">Recruiting agency</div><div class="value">{{ $token->agency->name }}</div></td></tr>
            <tr><td><div class="label">Token category</div><div class="value">{{ $token->category->name }}</div></td><td><div class="label">Received date</div><div class="value">{{ $token->received_on?->format('d F Y') ?: 'Not recorded' }}</div></td></tr>
            <tr><td><div class="label">Token generated date</div><div class="value">{{ $token->created_at?->format('d F Y, H:i') ?: 'Not recorded' }}</div></td><td><div class="label">Token created by</div><div class="value">{{ $token->creator?->name ?: 'Not recorded' }}</div></td></tr>
            <tr class="requirement"><td colspan="2"><div class="label">{{ $requirementLabel }}</div><div class="value">{{ $requirementValue !== null ? number_format($requirementValue) : 'Not recorded' }}</div></td></tr>
        </table>
        <div class="notes"><div class="label">Notes</div><div class="value">{{ filled($token->remarks) ? $token->remarks : 'No notes recorded.' }}</div></div>
    </div>
    <div class="footer">Verify this token against the central registry. Cancelled tokens remain in the audit record and are not valid for new processing.</div>
</body>
</html>
