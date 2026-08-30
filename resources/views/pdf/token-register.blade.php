<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>body{font:9px DejaVu Sans,sans-serif}h1{color:#075f4c}table{border-collapse:collapse;width:100%}th,td{padding:5px;border:1px solid #ccd8d1;text-align:left}th{background:#075f4c;color:white}</style>
</head>
<body>
    <h1>Token register</h1>
    <p>Generated {{ now()->format('d M Y, H:i') }} by {{ auth()->user()->name }}</p>
    <table>
        <thead><tr><th>Token</th><th>Category</th><th>Company</th><th>Agency</th><th>Received</th><th>Demanded</th><th>Approved</th><th>BHC</th><th>BOESL</th><th>File Holder</th></tr></thead>
        <tbody>
            @foreach($tokens as $token)
                <tr><td>{{ $token->token_number }}</td><td>{{ $token->category->name }}</td><td>{{ $token->company->name }}</td><td>{{ $token->agency->name }}</td><td>{{ $token->received_on->format('d-m-Y') }}</td><td>{{ $token->demanded_workers }}</td><td>{{ $token->approved_workers }}</td><td>{{ $token->bhc_number }}</td><td>{{ $token->boesl_status }}</td><td>{{ $token->currentHolder?->name }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
