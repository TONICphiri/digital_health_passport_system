@extends('errors.layout')
@section('code', '403')
@section('title', 'You do not have access to this page')
@section('message')
    @php $reason = isset($exception) ? $exception->getMessage() : ''; @endphp
    @php
        // Messages from the permission package are technical, so a plain one is shown instead.
        $showReason = $reason !== ''
            && $reason !== 'This action is unauthorized.'
            && ! ($exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException);
    @endphp
    {{ $showReason ? $reason : 'Your role does not allow you to open this page.' }}
    If you need access, ask your Facility Administrator or the System Administrator.
@endsection
