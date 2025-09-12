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
            'names' => ServerLocalizedStringResource::collection(
                $this->localizedStrings->where('type', 'name')
            ),
            'descriptions' => ServerLocalizedStringResource::collection(
                $this->localizedStrings->where('type', 'description')
            ),
            'imagesLinks' => $this->imageLinks->pluck('link')->toArray(),
            'videosLinks' => $this->videoLinks->pluck('link')->toArray(),
            'availableLocales' => $this->availableLocales->pluck('locale')->toArray(),
            'group' => $this->subsection->section->alias,
            'subGroup' => $this->subsection->alias,
            'accessType' => $this->access_type,
            'modules' => ModuleDataResource::collection($this->modules),
            'versions' => VersionDataResource::collection($this->versions),
        ];
    }
}
