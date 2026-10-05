@extends('errors.layout')
@section('code', '500')
@section('title', 'Something went wrong')
@section('message', 'The system could not complete your request. Your information has not been lost. Please try again in a few minutes.')
@section('extra')
    @if (! empty($reference))
        <p class="mt-4 border border-line bg-paper px-4 py-3 text-sm">If the problem continues, give this reference number to the System Administrator: <span class="font-mono font-semibold">{{ $reference }}</span></p>
    @endif
@endsection
