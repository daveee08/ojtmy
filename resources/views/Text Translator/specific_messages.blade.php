@extends('layouts.app')

@section('content')
<div class="container my-5">
    <h4>Conversation Details</h4>
    <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm mb-3">← Back</a>
    <ul class="list-group">
        @forelse ($messages as $msg)
            <li class="list-group-item">
                <strong>{{ ucfirst($msg['sender'] ?? 'N/A') }}:</strong>
                <div>{{ $msg['topic'] ?? '[No message]' }}</div>
                <small class="text-muted">
                    {{ $msg['created_at'] ? \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() : 'just now' }}
                </small>
            </li>
        @empty
            <li class="list-group-item text-muted">No messages found for this conversation.</li>
        @endforelse
    </ul>
</div>
@endsection