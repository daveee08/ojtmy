<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 bg-white border-end" style="height: 100vh; overflow-y: auto;">
            {{-- This partial displays the conversation history as a side navigation --}}
            <div class="list-group list-group-flush">
                @php $seenSessions = []; @endphp

                @if (!empty($messages))
                    <div class="mt-4">
                        <h5 class="fw-bold px-3">Conversation History</h5>
                        <ul class="list-group list-group-flush">
                            @foreach ($messages as $msg)
                                @if (!in_array($msg['message_id'], $seenSessions))
                                    @php $seenSessions[] = $msg['message_id']; @endphp
                                    <li class="list-group-item list-group-item-action bg-light">
                                        <a href="{{ route('translator.specific', ['message_id' => $msg['message_id']]) }}" class="text-decoration-none">
                                            <div>{{ $msg['topic'] ?? '[No topic]' }}</div>
                                            <small class="text-muted">
                                                {{ $msg['created_at'] ? \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() : 'just now' }}
                                            </small>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="px-3 mt-3 text-muted">No conversation history yet.</div>
                @endif
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 p-4">
            {{-- Your main content goes here --}}
            @yield('main-content')
        </div>
    </div>
</div>
