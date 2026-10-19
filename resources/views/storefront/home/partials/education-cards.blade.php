@foreach ($posts as $post)
    @include('storefront.home.partials.education-card', ['post' => $post])
@endforeach
