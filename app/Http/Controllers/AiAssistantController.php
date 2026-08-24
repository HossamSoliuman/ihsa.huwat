<?php

namespace App\Http\Controllers;

use App\Support\HawatAnalyst;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request, HawatAnalyst $analyst): View
    {
        $question = trim((string) $request->query('q'));

        return view('ai-assistant.index', [
            'question' => $question,
            'suggestions' => HawatAnalyst::SUGGESTIONS,
            'insight' => $question === '' ? null : $analyst->answer($question),
        ]);
    }
}
