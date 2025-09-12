<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionDataResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'idContent' => $this->content_id,
            'platform' => $this->platform,
            'major' => $this->major,
            'minor' => $this->minor,
            'micro' => $this->micro,
            'tested' => $this->tested,
            'releaseNote' => $this->release_note,
            'localizations' => VersionLocalizationResource::collection($this->whenLoaded('localizations')),
        ];
    }
}
