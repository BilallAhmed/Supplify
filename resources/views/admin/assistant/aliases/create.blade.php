@extends('layouts.admin')

@section('title', 'Add alias')
@section('breadcrumb', 'Assistant / Aliases / New')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Add alias</h1>
    </div>
@endsection

@section('content')
    @include('admin.assistant.aliases._form')
@endsection
