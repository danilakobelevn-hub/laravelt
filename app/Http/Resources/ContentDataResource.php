<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alias' => $this->alias,
            'defaultName' => $this->default_name,
            'guid' => $this->guid,
            'names' => $this->formatLocalizedStrings('name'),
            'descriptions' => $this->formatLocalizedStrings('description'),
            'imagesLinks' => $this->imageLinks->pluck('link')->toArray(),
            'videosLinks' => $this->videoLinks->pluck('link')->toArray(),
            'availableLocales' => $this->available_locales ?? [],
            'group' => $this->subsection->section->alias,
            'subGroup' => $this->subsection->alias,
            'accessType' => $this->access_type,
            'modules' => ModuleDataResource::collection($this->whenLoaded('modules')),
            'versions' => VersionDataResource::collection($this->whenLoaded('versions')),
        ];
    }
    protected function formatLocalizedStrings($type)
    {
        return $this->localizedStrings
            ->where('type', $type)
            ->map(function ($item) {
                return [
                    'locale' => $item->locale,
                    'value' => $item->value
                ];
            })
            ->values()
            ->toArray();
    }
}
