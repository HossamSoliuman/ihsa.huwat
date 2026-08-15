<?php

namespace App\Http\Controllers;

use App\Models\Species;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SpeciesController extends Controller
{
    public function index(Request $request): View
    {
        $species = Species::orderBy('code')->get();

        $filtered = $species
            ->when($request->filled('search'), function (Collection $c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($s) => str_contains(mb_strtolower(implode(' ', [$s->name_ar, $s->name_sci, $s->name_en, $s->name_local_gulf, $s->name_local_red_sea])), $q));
            })
            ->when($request->filled('category'), fn (Collection $c) => $c->where('category', $request->query('category')))
            ->when($request->filled('status'), fn (Collection $c) => $c->where('status', $request->query('status')))
            ->when($request->filled('review'), fn (Collection $c) => $c->where('review_status', $request->query('review')))
            ->values();

        $seaRegion = $request->query('sea_region');
        if ($seaRegion === 'الخليج العربي') {
            $filtered = $filtered->filter(fn ($s) => $this->inGulf($s))->values();
        } elseif ($seaRegion === 'البحر الأحمر') {
            $filtered = $filtered->filter(fn ($s) => $this->inRedSea($s))->values();
        } elseif ($seaRegion === 'كلاهما') {
            $filtered = $filtered->filter(fn ($s) => $this->inGulf($s) && $this->inRedSea($s))->values();
        }

        $groups = $seaRegion
            ? [['title' => $seaRegion === 'كلاهما' ? 'متواجد في كلا البحرين' : $seaRegion, 'tone' => $seaRegion === 'البحر الأحمر' ? 'red' : 'gulf', 'items' => $filtered]]
            : array_values(array_filter([
                ['title' => 'الخليج العربي', 'tone' => 'gulf', 'items' => $filtered->filter(fn ($s) => $this->inGulf($s) && ! $this->inRedSea($s))->values()],
                ['title' => 'البحر الأحمر', 'tone' => 'red', 'items' => $filtered->filter(fn ($s) => $this->inRedSea($s) && ! $this->inGulf($s))->values()],
                ['title' => 'كلا البحرين', 'tone' => 'both', 'items' => $filtered->filter(fn ($s) => $this->inGulf($s) && $this->inRedSea($s))->values()],
                ['title' => 'لم يُحدد البحر', 'tone' => 'none', 'items' => $filtered->filter(fn ($s) => ! $this->inGulf($s) && ! $this->inRedSea($s))->values()],
            ], fn ($g) => $g['items']->isNotEmpty() || in_array($g['title'], ['الخليج العربي', 'البحر الأحمر'])));

        $stats = [
            'total' => $species->count(),
            'documented' => $species->where('review_status', 'مصحح وموثق')->count(),
            'auto' => $species->where('review_status', 'منسق آليًا')->count(),
            'pending' => $species->where('review_status', 'مقبول مبدئيًا')->count(),
            'declined' => $species->where('status', 'انخفاض حاد')->count(),
        ];

        $selected = $request->filled('selected') ? $species->firstWhere('id', (int) $request->query('selected')) : null;

        return view('species.index', compact('groups', 'stats', 'selected') + ['filteredCount' => $filtered->count()]);
    }

    public function update(Request $request, Species $species): RedirectResponse
    {
        $species->update($request->validate([
            'name_local_gulf' => ['nullable', 'string', 'max:255'],
            'name_local_red_sea' => ['nullable', 'string', 'max:255'],
            'corrected_name_sci' => ['nullable', 'string', 'max:255'],
            'review_status' => ['nullable', 'in:مصحح وموثق,منسق آليًا,مقبول مبدئيًا'],
            'problem_type' => ['nullable', 'string', 'max:255'],
            'source_1' => ['nullable', 'string', 'max:500'],
            'source_2' => ['nullable', 'string', 'max:500'],
            'review_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]));

        return redirect()->route('species', ['selected' => $species->id])->with('status', 'تم حفظ مراجعة النوع بنجاح');
    }

    private function inGulf(Species $s): bool
    {
        return filled($s->name_local_gulf) && ! preg_match('/^[\s_—-]+$/u', $s->name_local_gulf);
    }

    private function inRedSea(Species $s): bool
    {
        return filled($s->name_local_red_sea) && ! preg_match('/^[\s_—-]+$/u', $s->name_local_red_sea);
    }
}