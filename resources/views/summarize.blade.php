<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summarizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Text Summarizer</h2>

    <form method="POST" action="/summarize" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">Summary Instructions</label>
            <input type="text" class="form-control" name="conditions" placeholder="E.g. 1 paragraph" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Text to Summarize</label>
            <textarea class="form-control" name="input_text" rows="6"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Or Upload a PDF</label>
            <input type="file" class="form-control" name="pdf" accept=".pdf">
        </div>

        <button class="btn btn-primary">Generate Summary</button>
    </form>

    @if(isset($summary))
    <pre class="alert alert-secondary mt-4" style="white-space: pre-wrap">
    <h5>Summary:</h5>
    {{ preg_replace('/^\s*-\s*/m', '- ', $summary) }}
    </pre>

    @endif
</div>
</body>
</html>
