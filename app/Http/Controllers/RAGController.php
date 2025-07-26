<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

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
    Log::info('📘 addBook() called', $request->all());

    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255',
        'subject_name' => 'required|string|max:255',
        'grade_level' => 'required|string|max:50',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        Log::warning('❌ addBook() validation failed', $validator->errors()->toArray());

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

    Log::info('✅ Book inserted', ['book_id' => $bookId]);

    return response()->json([
        'status' => 'success',
        'message' => 'Book added successfully',
        'book_id' => $bookId
    ]);
}

public function getBooks(Request $request)
{
    $query = DB::table('book')->orderBy('id', 'desc');
    if ($request->has('grade_level')) {
        $query->where('grade_level', $request->input('grade_level'));
    }
    $books = $query->get();

    return response()->json([
        'status' => 'success',
        'books' => $books
    ]);
}

 // 📘 Add Unit
    public function addUnit(Request $request)
{
    Log::info('📘 addUnit() called', $request->all());

    $validated = $request->validate([
        'book_id' => 'required|exists:book,id',
        'title' => 'required|string|max:255',
        'unit_number' => 'required|integer'
    ]);
    // if ($validator->fails()) {
    //     Log::warning('❌ addUnit() validation failed', $validator->errors()->toArray());

    //     return response()->json([
    //         'status' => 'error',
    //         'errors' => $validator->errors()
    //     ], 422);
    // }

    Log::info('📘 validated() called', $request->all());


    DB::table('units')->insert([
        'book_id' => $validated['book_id'],
        'title' => $validated['title'],
        'unit_number' => $validated['unit_number'],
        'created_at' => now(),
        'updated_at' => now()
    ]);

    Log::info('✅ Unit inserted', ['book_id' => $validated['book_id'], 'unit_number' => $validated['unit_number']]);

    return response()->json(['status' => 'success']);
}

    // 📚 Get Units by Book
    public function getUnits(Request $request)
{
    $bookId = $request->query('book_id');

    Log::info('📗 getUnits() called', ['book_id' => $bookId]);

    $units = DB::table('units')
        ->where('book_id', $bookId)
        ->orderBy('unit_number')
        ->get();

    Log::info('📗 Units retrieved', ['count' => $units->count()]);

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

    public function addLesson(Request $request)
    {
        set_time_limit(0);

        $validated = $request->validate([
            'chapter_id' => 'required|exists:chapter,id',
            'lesson_title' => 'required|string|max:255',
            'lesson_number' => 'required|integer',
            'pdf_file' => 'required|file|mimes:pdf', // max 10MB
        ]);

        $pdfPath = $request->file('pdf_file')->store('lessons', 'public');
        $fullPath = storage_path('app/public/' . $pdfPath);

    try {
        // STEP 1: Create and commit the lesson FIRST
        $lessonId = DB::table('lesson')->insertGetId([
            'chapter_id' => $validated['chapter_id'],
            'lesson_title' => $validated['lesson_title'],
            'lesson_number' => $validated['lesson_number'],
            'pdf_path' => $pdfPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // STEP 2: Now call FastAPI AFTER the insert is committed
        $unitId = DB::table('chapter')->where('id', $validated['chapter_id'])->value('unit_id');
        $bookId = DB::table('units')->where('id', $unitId)->value('book_id');
        $chapterId =  $validated['chapter_id'];

        $response = Http::timeout(0)
            ->attach('file', file_get_contents($fullPath), basename($pdfPath))
            ->post('http://192.168.50.20:8001/upload-and-embed', [
                'book_id' => $bookId,
                'unit_id' => $unitId,
                'chapter_id' => $chapterId,
                'lesson_id' => $lessonId,
            ]);

        if ($response->failed()) {
            DB::table('lesson')->where('id', $lessonId)->delete();
            Storage::disk('public')->delete($pdfPath);

            return response()->json([
                'status' => 'fail',
                'message' => 'Server is not up or an error occurred.',
                'fastapi_error' => $response->body(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'lesson_id' => $lessonId,
        ]);

    } catch (\Exception $e) {
        if (isset($lessonId)) {
            DB::table('lesson')->where('id', $lessonId)->delete();
        }
        Storage::disk('public')->delete($pdfPath);

        return response()->json([
            'status' => 'error',
            'message' => 'Unexpected failure.',
            'exception' => $e->getMessage(),
        ], 500);
    }
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
        $bookId = $request->query('book_id');
        
        $lesson = DB::table('lesson')->find($lessonId);
        $book = DB::table('book')->find($bookId);
        $gradeLevel = $book ? $book->grade_level : null;
        $books = DB::table('book')->where('grade_level', $gradeLevel)->orderBy('id')->get();

        return view('virtualtutorchat', [
            'lesson' => $lesson,
            'books' => $books,
            'book_id' => $bookId,
            'unit_id' => $request->query('unit_id'),
            'chapter_id' => $request->query('chapter_id'),
            'lesson_id' => $lessonId,
            'grade_level' => $gradeLevel
        ]);
    }

    public function sendRagMessage(Request $request)
    {

    set_time_limit(0);
    $request->validate([
        'prompt' => 'required|string',
    ]);

    $sessionId = $request->input('session_id');

        // ✅ Get from query string
        $bookId = $request->query('book_id');
        $unitId = $request->query('unit_id');
        $chapterId = $request->query('chapter_id');
        $lessonId = $request->query('lesson_id');

        if (!$bookId || !$unitId || !$chapterId || !$lessonId) {
            return response()->json([
                'status' => 'fail',
                'error' => 'Missing one or more required query parameters: book_id, unit_id, chapter_id, lesson_id.'
            ], 422);
        }

    try {
        // Create session record
        if (!$sessionId) {
        $sessionId = DB::table('sessions')->insertGetId([
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
            // $baseUrl = env('API_BASE_URL');
            $baseUrl = config('app.api_base_url');
            Log::debug('Using FastAPI base URL:', ['baseUrl' => $baseUrl]);

            // Call FastAPI
            $response = Http::timeout(0)->post("{$baseUrl}/ragchat", [
                'session_id' => $sessionId,
                'prompt' => $request->input('prompt'),
                'book_id' => $bookId,
                'unit_id' => $unitId,
                'chapter_id' => $chapterId,
                'lesson_id' => $lessonId,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 'fail',
                    'error' => $response->body()
                ], 500);
            }

            $responseData = $response->json();
            Log::debug('FastAPI raw response:', $responseData);

            return response()->json([
                'status' => 'success',
                'response' => $responseData['response'] ?? '[No response returned]',
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while sending the chat.',
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    public function generateQuiz(Request $request)
    {
        set_time_limit(0);

        try {
            $bookId = $request->input('book_id');
            $unitId = $request->input('unit_id');
            $chapterId = $request->input('chapter_id');
            $quizType = $request->input('quiz_type');
            $numQuestions = $request->input('number_of_questions');
            $difficulty = $request->input('difficulty_level');
            $gradeLevel = $request->input('grade_level');
            $includeAnswers = $request->input('answer_key');

            if (!$bookId || !$unitId || !$chapterId) {
                return response()->json([
                    'status' => 'fail',
                    'error' => 'Missing required body parameters: book_id, unit_id, chapter_id.'
                ], 422);
            }

            $payload = [
                'book_id' => $bookId,
                'unit_id' => $unitId,
                'chapter_number' => $chapterId,
                'quiz_type' => $quizType,
                'number_of_questions' => (int) $numQuestions,
                'difficulty_level' => $difficulty,
                'grade_level' => $gradeLevel,
                'answer_key' => filter_var($includeAnswers, FILTER_VALIDATE_BOOLEAN)
            ];

            $response = Http::timeout(0)->post('http://192.168.50.20:8001/make-quiz', $payload);

            if ($response->failed()) {
                return response()->json([
                    'status' => 'fail',
                    'error' => 'FastAPI error',
                    'details' => $response->body()
                ], 500);
            }

            $responseData = $response->json();

            return response()->json([
                'status' => 'success',
                'quiz' => $responseData['quiz'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate quiz',
                'exception' => $e->getMessage()
            ], 500);
        }
    }

}