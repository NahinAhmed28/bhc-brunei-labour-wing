<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #153028; font-size: 12px; }
        .top { border-top: 9px solid #075f4c; border-bottom: 3px solid #d6a62e; padding: 22px 0; text-align: center; }
        .top h1 { font-family: DejaVu Serif, serif; margin: 0; font-size: 22px; }
        .top p { margin: 5px 0; }
        .token { font-size: 34px; font-weight: bold; letter-spacing: 2px; color: #075f4c; margin: 34px 0 8px; }
        .label { font-size: 9px; text-transform: uppercase; color: #66766e; }
        .value { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { width: 50%; border-bottom: 1px solid #dbe2de; padding: 15px 8px; }
        .footer { margin-top: 40px; border-top: 1px solid #ccd8d1; padding-top: 12px; color: #69776f; font-size: 9px; }
    </style>
</head>
<body>
    <div class="top"><h1>Bangladesh High Commission</h1><p>Brunei Darussalam &middot; Visa &amp; Worker Management System</p></div>
    <div style="text-align:center"><div class="token">{{ $token->token_number }}</div><div class="label">Official submission token</div></div>
    <table class="grid">
        <tr><td><div class="label">Company</div><div class="value">{{ $token->company->name }}</div></td><td><div class="label">Recruiting agency</div><div class="value">{{ $token->agency->name }}</div></td></tr>
        <tr><td><div class="label">Token category</div><div class="value">{{ $token->category->name }}</div></td><td><div class="label">Received date</div><div class="value">{{ $token->received_on->format('d F Y') }}</div></td></tr>
        <tr><td><div class="label">Demanded workers</div><div class="value">{{ $token->demanded_workers }}</div></td><td><div class="label">Approved workers</div><div class="value">{{ $token->approved_workers }}</div></td></tr>
        <tr><td><div class="label">BHC number</div><div class="value">{{ $token->bhc_number ?: 'Pending' }}</div></td><td><div class="label">File holder</div><div class="value">{{ $token->currentHolder?->name ?: 'Unassigned' }}</div></td></tr>
    </table>
    <div class="footer">Generated {{ now()->format('d F Y, H:i') }} &middot; Verify this token against the central registry. Cancelled tokens remain in the audit record and are not valid for new processing.</div>
</body>
</html>
