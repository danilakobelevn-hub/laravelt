<?php

namespace App\Repositories;

use App\Models\Section;
use Illuminate\Support\Collection;

class TreeRepository
{
    public function __construct(private Section $section) {}

    public function getSectionsTree(): Collection
    {
        return $this->section->with('subsections')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    public function getSubsectionsBySection(int $sectionId): Collection
    {
        $section = $this->section->find($sectionId);

        if (!$section) {
            return collect();
        }

        return $section->subsections;
    }

    public function formatSectionsForJsTree(Collection $sections): array
    {
        return $sections->map(function ($section) {
            return [
                'id' => $section->id,
                'text' => $section->default_name,
                'children' => $section->subsections->map(function ($subsection) {
                    return [
                        'id' => 'sub_' . $subsection->id,
                        'text' => $subsection->default_name
                    ];
                })->toArray()
            ];
        })->toArray();
    }
}
