<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Services\SkillLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for custom skills (F-14). Temporary auth guard until roles (F-40) exist.
 */
class AdminSkillController extends Controller
{
    public function __construct(
        private readonly SkillLibrary $skillLibrary,
    ) {}

    public function index(): View
    {
        return view('admin.skills', [
            'customSkills' => Skill::orderBy('name')->get(),
            'builtinCount' => count($this->skillLibrary->synonyms()) + count(SkillLibrary::TECH_SKILLS),
            'skillLibrary' => $this->skillLibrary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:skills,name'],
            'category' => ['nullable', 'string', 'max:60'],
            'synonyms' => ['nullable', 'string', 'max:500'],
        ], [], [
            'name' => 'nom de la compétence',
        ]);

        Skill::create([
            'name' => mb_strtolower(trim($validated['name'])),
            'category' => $validated['category'] !== null && $validated['category'] !== '' ? mb_strtolower(trim($validated['category'])) : null,
            'synonyms' => $this->parseSynonyms((string) ($validated['synonyms'] ?? '')),
            'source' => 'custom',
        ]);

        $this->skillLibrary->refreshFromDatabase(true);

        return redirect()->route('admin.skills.index')->with('status', __('skills.created'));
    }

    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:skills,name,'.$skill->id],
            'category' => ['nullable', 'string', 'max:60'],
            'synonyms' => ['nullable', 'string', 'max:500'],
        ], [], [
            'name' => 'nom de la compétence',
        ]);

        $skill->update([
            'name' => mb_strtolower(trim($validated['name'])),
            'category' => $validated['category'] !== null && $validated['category'] !== '' ? mb_strtolower(trim($validated['category'])) : null,
            'synonyms' => $this->parseSynonyms((string) ($validated['synonyms'] ?? '')),
        ]);

        $this->skillLibrary->refreshFromDatabase(true);

        return redirect()->route('admin.skills.index')->with('status', __('skills.updated'));
    }

    public function destroy(Skill $skill): RedirectResponse
    {
        $skill->delete();
        $this->skillLibrary->refreshFromDatabase(true);

        return redirect()->route('admin.skills.index')->with('status', __('skills.deleted'));
    }

    /**
     * @return list<string>
     */
    private function parseSynonyms(string $raw): array
    {
        $parts = preg_split('/[,;\n]+/', $raw) ?: [];
        $synonyms = [];

        foreach ($parts as $part) {
            $trimmed = mb_strtolower(trim($part));

            if ($trimmed !== '') {
                $synonyms[] = $trimmed;
            }
        }

        return array_values(array_unique($synonyms));
    }
}
