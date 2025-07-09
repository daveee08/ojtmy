@extends('layouts.app')

@section('content')
<div class="container my-5">
    <h4>Conversation Details</h4>
    <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm mb-3">← Back</a>
    <ul class="list-group">
        @forelse ($messages as $msg)
            <!-- <p class="text-muted small">DEBUG message_id: {{ $message_id ?? 'NULL' }}</p> -->

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

    {{-- ✅ Follow-Up Form (not nested!) --}}
            @if (!empty($messages) && isset($message_id))
                <div class="mt-4">
                    <label class="form-label fw-semibold">Send a message:</label>
                    <form action="{{ route('translator.followup') }}" method="POST">
                        @csrf
                        <p class="text-muted small">DEBUG hah message_id: {{ $message_id ?? 'NULL' }}</p>
                        <input type="hidden" name="message_id" value="{{ $message_id ?? '' }}">
                        <textarea name="followup" rows="3" class="form-control mb-2" placeholder="Ask a follow-up..."></textarea>
                        <button type="submit" class="btn btn-outline-primary">Send Message</button>
                    </form>
                </div>
            @endif
</div>
@endsection