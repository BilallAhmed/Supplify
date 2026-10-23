@extends('layouts.admin')

@section('title', 'Edit FAQ')
@section('breadcrumb', 'Assistant / FAQs / Edit')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Edit FAQ</h1>
    </div>
@endsection

@section('content')
    @include('admin.assistant.faqs._form')
@endsection
