<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentDataResource;
use App\Http\Resources\VersionDataResource;
use App\Models\Content;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Get all contents
     * GET /api/v1/contents/all
     */
    public function all()
    {
        $contents = Content::with([
            'localizedStrings',
            'imageLinks',
            'videoLinks',
            'availableLocales',
            'subsection.section',
            'modules.localizedStrings',
            'versions'
        ])->get();

        return ContentDataResource::collection($contents);
    }

    /**
     * Get content by alias
     * GET /api/v1/contents/by_alias?alias=test&platform=android
     */
    public function byAlias(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $content = Content::with([
            'localizedStrings',
            'imageLinks',
            'videoLinks',
            'availableLocales',
            'subsection.section',
            'modules.localizedStrings',
            'versions' => function($query) use ($request) {
                $query->where('platform', $request->platform);
            }
        ])->where('alias', $request->alias)->first();

        if (!$content) {
            return response()->json(['error' => 'Content not found'], 404);
        }

        return new ContentDataResource($content);
    }

    /**
     * Upload version
     * POST /api/v1/contents/uploadVersion
     */
    public function uploadVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string',
            'major' => 'boolean',
            'minor' => 'boolean',
            'micro' => 'boolean',
            'releaseNote' => 'nullable|string',
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $content = Content::where('alias', $request->alias)->first();
        if (!$content) {
            return response()->json(['error' => 'Content not found'], 404);
        }

        // Determine version numbers
        $latestVersion = Version::where('content_id', $content->id)
            ->where('platform', $request->platform)
            ->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('micro', 'desc')
            ->first();

        $major = $latestVersion ? $latestVersion->major : 0;
        $minor = $latestVersion ? $latestVersion->minor : 0;
        $micro = $latestVersion ? $latestVersion->micro : 0;

        if ($request->major) $major++;
        if ($request->minor) $minor++;
        if ($request->micro) $micro++;

        // Store file
        $file = $request->file('file');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('versions', $fileName, 'private');

        // Create version
        $version = Version::create([
            'content_id' => $content->id,
            'platform' => $request->platform,
            'major' => $major,
            'minor' => $minor,
            'micro' => $micro,
            'tested' => false,
            'release_note' => $request->releaseNote,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
        ]);

        return new VersionDataResource($version);
    }

    /**
     * Download content
     * GET /api/v1/contents/download?fileName=test.zip&platform=android
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'platform' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $version = Version::where('file_name', $request->fileName)
            ->where('platform', $request->platform)
            ->where('tested', true)
            ->first();

        if (!$version) {
            return response()->json(['error' => 'File not found'], 404);
        }

        if (!Storage::disk('private')->exists($version->file_path)) {
            return response()->json(['error' => 'File not found on server'], 404);
        }

        return Storage::disk('private')->download($version->file_path, $version->file_name);
    }
    /**
     * Upload localization version
     * POST /api/v1/contents/uploadLocalizationVersion
     */
    public function uploadLocalizationVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string',
            'lang' => 'required|string|size:2',
            'versionData' => 'required|string', // format "major.minor.micro"
            'file' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $content = Content::where('alias', $request->alias)->first();
        if (!$content) {
            return response()->json(['error' => 'Content not found'], 404);
        }

        // Parse version data
        $versionParts = explode('.', $request->versionData);
        if (count($versionParts) !== 3) {
            return response()->json(['error' => 'Invalid version format'], 400);
        }

        [$major, $minor, $micro] = array_map('intval', $versionParts);

        // Find version
        $version = Version::where('content_id', $content->id)
            ->where('platform', $request->platform)
            ->where('major', $major)
            ->where('minor', $minor)
            ->where('micro', $micro)
            ->first();

        if (!$version) {
            return response()->json(['error' => 'Version not found'], 404);
        }

        // Check if localization already exists
        $existingLocalization = VersionLocalization::where('version_id', $version->id)
            ->where('locale', $request->lang)
            ->first();

        if ($existingLocalization) {
            // Delete old file
            Storage::disk('private')->delete($existingLocalization->file_path);
            $existingLocalization->delete();
        }

        // Store localization file
        $file = $request->file('file');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('localizations', $fileName, 'private');

        // Create localization
        $localization = VersionLocalization::create([
            'version_id' => $version->id,
            'locale' => $request->lang,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
        ]);

        return new VersionDataResource($version);
    }

    /**
     * Download localization
     * GET /api/v1/contents/downloadLocalization?fileName=test.zip&platform=android
     */
    public function downloadLocalization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'platform' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $localization = VersionLocalization::where('file_name', $request->fileName)
            ->whereHas('version', function($query) use ($request) {
                $query->where('platform', $request->platform)
                    ->where('tested', true);
            })
            ->first();

        if (!$localization) {
            return response()->json(['error' => 'Localization file not found'], 404);
        }

        if (!Storage::disk('private')->exists($localization->file_path)) {
            return response()->json(['error' => 'File not found on server'], 404);
        }

        return Storage::disk('private')->download($localization->file_path, $localization->file_name);
    }

    /**
     * Get content for QA (includes untested versions)
     * GET /api/v1/contents/qa
     */
    public function qaIndex()
    {
        $contents = Content::with([
            'localizedStrings',
            'imageLinks',
            'videoLinks',
            'availableLocales',
            'subsection.section',
            'modules.localizedStrings',
            'versions' => function($query) {
                $query->where('tested', false); // Only untested versions for QA
            }
        ])->get();

        return ContentDataResource::collection($contents);
    }

    /**
     * Mark version as tested
     * PATCH /api/v1/contents/versions/{version}/test
     */
    public function markAsTested(Version $version)
    {
        $version->update(['tested' => true]);

        return new VersionDataResource($version);
    }

    /**
     * Get untested versions for QA
     * GET /api/v1/contents/untested
     */
    public function untestedVersions()
    {
        $versions = Version::with(['content', 'localizations'])
            ->where('tested', false)
            ->get();

        return VersionDataResource::collection($versions);
    }
}
