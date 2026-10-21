@if (! empty($tracking['body_start'] ?? []))
    @foreach ($tracking['body_start'] as $snippet)
        {!! $snippet !!}
    @endforeach
@endif
