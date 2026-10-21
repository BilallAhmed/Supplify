@if (! empty($tracking['head'] ?? []))
    @foreach ($tracking['head'] as $snippet)
        {!! $snippet !!}
    @endforeach
@endif
