@extends('layouts.app')

@section('content')
<div class="container my-5">
    <h2 class="text-highlight">Conversation #{{ $message_id ?? 'Unknown' }}</h2>

    @if (!empty($messages))
        <ul class="list-group mb-4">
            @foreach ($messages as $msg)
                <li class="list-group-item">
                    <strong>{{ ucfirst($msg['sender']) }}:</strong>
                    <div>{{ $msg['topic'] }}</div>
                    <small class="text-muted">
                        {{ $msg['created_at'] ? \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() : 'just now' }}
                    </small>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-muted">No messages found for this conversation.</p>
    @endif

    <a href="{{ route('translator.form') }}" class="btn btn-secondary">← Back to All Conversations</a>
</div>
@endsection
