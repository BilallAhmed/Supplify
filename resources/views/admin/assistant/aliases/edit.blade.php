@extends('layouts.admin')

@section('title', 'Edit alias')
@section('breadcrumb', 'Assistant / Aliases / Edit')

@section('page_header')
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Edit alias</h1>
    </div>
@endsection

@section('content')
    @include('admin.assistant.aliases._form')
@endsection