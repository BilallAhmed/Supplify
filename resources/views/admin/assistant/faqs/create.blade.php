@extends('layouts.admin')

@section('title', 'Add FAQ')
@section('breadcrumb', 'Assistant / FAQs / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Add FAQ</h1>
    </div>
@endsection

@section('content')
    @include('admin.assistant.faqs._form')
@endsection
