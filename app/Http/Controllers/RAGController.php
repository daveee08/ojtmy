<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

}
