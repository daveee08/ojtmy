<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Lesson PDF</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <h1>Lesson: {{ $lesson->lesson_title }}</h1>
    <p>Lesson ID: {{ $lesson->id }}</p>
    <p>Chapter ID: {{ $chapter_id }}</p>
    <p>Unit ID: {{ $unit_id }}</p>
    <p>Book ID: {{ $book_id }}</p>
    <p>PDF Path: {{ $lesson->pdf_path }}</p>
</body>

</html>
