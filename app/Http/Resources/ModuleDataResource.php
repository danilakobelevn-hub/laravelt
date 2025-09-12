<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleDataResource extends JsonResource
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
            'type' => $this->type,
        ];
    }
}
