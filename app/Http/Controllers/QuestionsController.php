<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionCategory;

/**
 * Class QuestionsController
 * @package App\Http\Controllers
 */
class QuestionsController extends Controller
{
    /**
     * Shows the Frequently Asked Questions
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $categories = QuestionCategory::orderBy('sort', 'asc')->get();
        return view('questions.index', compact('categories'));
    }
}