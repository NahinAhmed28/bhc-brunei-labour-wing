<table>
    <tr><th>Token</th><th>Category</th><th>Company</th><th>Agency</th><th>Received</th><th>Demanded</th><th>Approved</th><th>BHC No.</th><th>BOESL</th><th>File Holder</th></tr>
    @foreach($tokens as $token)
        <tr><td>{{ $token->token_number }}</td><td>{{ $token->category->name }}</td><td>{{ $token->company->name }}</td><td>{{ $token->agency->name }}</td><td>{{ $token->received_on->format('Y-m-d') }}</td><td>{{ $token->demanded_workers }}</td><td>{{ $token->approved_workers }}</td><td>{{ $token->bhc_number }}</td><td>{{ $token->boesl_status }}</td><td>{{ $token->currentHolder?->name }}</td></tr>
    @endforeach
</table>
