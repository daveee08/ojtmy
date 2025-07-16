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
    
public function getBooksByGrade(Request $request)
{
    $gradeLevel = $request->query('grade_level');

    $books = DB::table('books')
        ->select('id as book_id', 'title', 'description', 'grade_level')
        ->where('grade_level', $gradeLevel)
        ->get();

    return response()->json($books);
}

// public function uploadToFastAPI(Request $request)
// {

//     set_time_limit(0);
//     $validator = Validator::make($request->all(), [
//         'subject_name' => 'required|string',
//         'grade_level' => 'required|string',
//         'description' => 'required|string',
//         'pdf_file' => 'required|file|mimes:pdf', // 20MB max
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
//     }

//     try {
//         $file = $request->file('pdf_file');
//         $tempPath = $file->getPathname();
//         $filename = $file->getClientOriginalName();

//         $response = Http::timeout(0) // ⏱️ timeout in seconds
//         ->attach('file', file_get_contents($tempPath), $filename)
//         ->asMultipart()
//         ->post('http://127.0.0.1:5001/chunk-and-embed/', [
//             'title' => $request->input('subject_name'),
//             'desc' => $request->input('description'),
//             'grade_lvl' => $request->input('grade_level'),
//         ]);

//         if (!$response->successful()) {
//             return response()->json(['status' => 'error', 'message' => 'FastAPI Error', 'details' => $response->body()], 500);
//         }

//         return response()->json(['status' => 'success', 'message' => 'Book uploaded and chunked successfully!']);

//     } catch (\Exception $e) {
//         return response()->json(['status' => 'error', 'message' => 'Upload failed', 'details' => $e->getMessage()], 500);
//     }
// }

public function uploadToFastAPI(Request $request)
{
    set_time_limit(0);

    $validator = Validator::make($request->all(), [
        'subject_name' => 'required|string',
        'grade_level' => 'required|string',
        'description' => 'required|string',
        'pdf_file' => 'required|file|mimes:pdf|max:20480',
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
    }

    try {
        $file = $request->file('pdf_file');
        $filename = $file->getClientOriginalName();

        // Convert "Grade 7" → "grade_7"
        $gradeDir = Str::slug($request->input('grade_level'), '_');
        $path = $file->storeAs("books/{$gradeDir}", $filename); // stored in storage/app/books/grade_x

        // Get absolute path (real disk path)
        $absolutePath = storage_path("app/{$path}");

        // Post to FastAPI
        $response = Http::timeout(0)
            ->attach('file', file_get_contents($absolutePath), $filename)
            ->asMultipart()
            ->post('http://127.0.0.1:5001/chunk-and-embed/', [
                'title' => $request->input('subject_name'),
                'desc' => $request->input('description'),
                'grade_lvl' => $request->input('grade_level'),
                'source' => $absolutePath, // Pass full file path as 'source'
            ]);

        if (!$response->successful()) {
            return response()->json([
                'status' => 'error',
                'message' => 'FastAPI Error',
                'details' => $response->body()
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Book uploaded and chunked successfully!',
            'path' => $absolutePath
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Upload failed',
            'details' => $e->getMessage()
        ], 500);
    }
}

}
