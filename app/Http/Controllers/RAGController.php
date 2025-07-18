<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RAGController extends Controller
{

// public function uploadToFastAPI(Request $request)
// {
//     set_time_limit(0);

//     $validator = Validator::make($request->all(), [
//         'subject_name' => 'required|string',
//         'grade_level' => 'required|string',
//         'description' => 'required|string',
//         'pdf_file' => 'required|file|mimes:pdf|max:20480',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
//     }

//     try {
//         $file = $request->file('pdf_file');
//         $filename = $file->getClientOriginalName();

//         // Convert "Grade 7" → "grade_7"
//         $gradeDir = Str::slug($request->input('grade_level'), '_');
//         $path = $file->storeAs("books/{$gradeDir}", $filename); // stored in storage/app/books/grade_x

//         // Get absolute path (real disk path)
//         $absolutePath = storage_path("app/{$path}");

//         // Post to FastAPI
//         $response = Http::timeout(0)
//             ->attach('file', file_get_contents($absolutePath), $filename)
//             ->asMultipart()
//             ->post('http://127.0.0.1:5001/chunk-and-embed/', [
//                 'title' => $request->input('subject_name'),
//                 'desc' => $request->input('description'),
//                 'grade_lvl' => $request->input('grade_level'),
//                 'source' => $absolutePath, // Pass full file path as 'source'
//             ]);

//         if (!$response->successful()) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'FastAPI Error',
//                 'details' => $response->body()
//             ], 500);
//         }

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Book uploaded and chunked successfully!',
//             'path' => $absolutePath
//         ]);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => 'error',
//             'message' => 'Upload failed',
//             'details' => $e->getMessage()
//         ], 500);
//     }
// }

public function addBook(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'subject_name' => 'required|string|max:255',
        'grade_level' => 'required|string|max:50',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $bookId = DB::table('book')->insertGetId([
        'title' => $request->title,
        'subject_name' => $request->subject_name,
        'grade_level' => $request->grade_level,
        'description' => $request->description,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Book added successfully',
        'book_id' => $bookId
    ]);
}

public function getBooks()
{
    $books = DB::table('book')->orderBy('id', 'desc')->get();

    return response()->json([
        'status' => 'success',
        'books' => $books
    ]);
}

 // 📘 Add Unit
    public function addUnit(Request $request)
{
    $validated = $request->validate([
        'book_id' => 'required|exists:book,id',
        'title' => 'required|string|max:255',
        'unit_number' => 'required|integer'
    ]);

    DB::table('units')->insert([
        'book_id' => $validated['book_id'],
        'title' => $validated['title'],
        'unit_number' => $validated['unit_number'],
        'created_at' => now(),
        'updated_at' => now()
    ]);

    return response()->json(['status' => 'success']);
}

    // 📚 Get Units by Book
    public function getUnits(Request $request)
    {
        $bookId = $request->query('book_id');

        $units = DB::table('units')
            ->where('book_id', $bookId)
            ->orderBy('unit_number')
            ->get();

        return response()->json(['units' => $units]);
    }

    // 📗 Add Chapter
    public function addChapter(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'chapter_title' => 'required|string|max:255',
            'chapter_number' => 'required|integer'
        ]);

        DB::table('chapter')->insert([
            'unit_id' => $validated['unit_id'],
            'chapter_title' => $validated['chapter_title'],
            'chapter_number' => $validated['chapter_number'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['status' => 'success']);
    }

    // 📗 Get Chapters
    public function getChapters(Request $request)
    {
        $unitId = $request->query('unit_id');

        $chapters = DB::table('chapter')
            ->where('unit_id', $unitId)
            ->orderBy('chapter_number')
            ->get();

        return response()->json(['chapters' => $chapters]);
    }

    // 📘 Add Lesson
    public function addLesson(Request $request)
    {
        $validated = $request->validate([
        'chapter_id' => 'required|exists:chapter,id',
        'lesson_title' => 'required|string|max:255',
        'lesson_number' => 'required|integer',
        'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10MB
    ]);

        // Store the uploaded PDF in storage/app/public/lessons
        $pdfPath = $request->file('pdf_file')->store('lessons', 'public');

        DB::table('lesson')->insert([
            'chapter_id' => $validated['chapter_id'],
            'lesson_title' => $validated['lesson_title'],
            'lesson_number' => $validated['lesson_number'],
            'pdf_path' => $pdfPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => 'success']);
    }

    // 📘 Get Lessons
    public function getLessons(Request $request)
    {
        $chapterId = $request->query('chapter_id');

        $lessons = DB::table('lesson')
            ->where('chapter_id', $chapterId)
            ->orderBy('lesson_number')
            ->get();

        return response()->json(['lessons' => $lessons]);
    }

    public function getFirstLesson(Request $request)
{
    $bookId = $request->book_id;

    $unit = DB::table('units')->where('book_id', $bookId)->orderBy('unit_number')->first();
    if (!$unit) return response()->json(['status' => 'error', 'message' => 'No unit found']);

    $chapter = DB::table('chapter')->where('unit_id', $unit->id)->orderBy('chapter_number')->first();
    if (!$chapter) return response()->json(['status' => 'error', 'message' => 'No chapter found']);

    $lesson = DB::table('lesson')->where('chapter_id', $chapter->id)->orderBy('lesson_number')->first();
    if (!$lesson) return response()->json(['status' => 'error', 'message' => 'No lesson found']);

    return response()->json([
        'status' => 'success',
        'book_id' => $bookId,
        'unit_id' => $unit->id,
        'chapter_id' => $chapter->id,
        'lesson_id' => $lesson->id
    ]);
}

public function showVirtualTutorChat(Request $request)
{
    $lessonId = $request->query('lesson_id');

    $lesson = DB::table('lesson')->find($lessonId);

    return view('virtualtutorchat', [
    'lesson' => $lesson,
    'book_id' => $request->query('book_id'),
    'unit_id' => $request->query('unit_id'),
    'chapter_id' => $request->query('chapter_id'),
    'lesson_id' => $lessonId
]);
}
}