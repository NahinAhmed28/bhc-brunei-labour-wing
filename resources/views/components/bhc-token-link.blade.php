@props(['number', 'tokens', 'fallback' => 'Not assigned'])

@if($tokens->isNotEmpty())
    <a {{ $attributes->merge(['class' => 'text-decoration-none']) }}
       href="{{ route('tokens.show', $tokens->first()) }}"
       target="_blank"
       rel="noopener noreferrer"
       data-bhc-token-urls="{{ $tokens->map(fn ($token) => route('tokens.show', $token))->toJson() }}">
        {{ $number }}<i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
        <span class="visually-hidden">Open {{ $tokens->count() === 1 ? 'token in a new tab' : 'all '.$tokens->count().' matching tokens in new tabs' }}</span>
    </a>
@else
    {{ $number ?: $fallback }}
@endif
