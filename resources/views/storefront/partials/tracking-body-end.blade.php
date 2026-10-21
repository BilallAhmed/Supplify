@if (! empty($tracking['body_end'] ?? []))
    @foreach ($tracking['body_end'] as $snippet)
        {!! $snippet !!}
    @endforeach
@endif

@include('storefront.partials.consent-banner')
