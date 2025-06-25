@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="ck-card">
        <h2 class="mb-3">Email Writer</h2>
        <p class="mb-4 text-muted">Generate a draft professional email to teachers, peers, or others.</p>

        <form method="POST" action="{{ route('email.writer.generate') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Content to include in email:</label>
                <textarea class="form-control" name="email_input" rows="6" placeholder="Include specific details to include in your message." required>{{ old('email_input') }}</textarea>
            </div>

            <button type="submit" class="btn ck-btn w-100">Generate</button>
        </form>

        @if(session('generated_email'))
            <hr class="my-4">
            <h5>Generated Email:</h5>
            <div class="border rounded p-3 bg-light">
                {!! nl2br(e(session('generated_email'))) !!}
            </div>
        @endif
    </div>
</div>

<style>
    .ck-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        padding: 40px;
        border: 1px solid #e4e8f0;
    }

    .ck-btn {
        background-color: #4a42f4;
        color: #fff;
        border: none;
        padding: 12px 28px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 16px;
    }
</style>
@endsection
