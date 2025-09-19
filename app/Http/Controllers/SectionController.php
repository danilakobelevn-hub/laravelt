<?php
//
//namespace App\Http\Controllers;
//
//use App\Http\Controllers\Controller;
//use App\Http\Requests\StoreContentRequest;
//use App\Http\Requests\UpdateContentRequest;
//use App\Models\Content;
//use App\Models\Section;
//use App\Models\Subsection;
//use App\Models\Module;
//use App\Models\Version;
//use App\Models\ContentLocalizedString;
//use App\Models\ContentImageLink;
//use App\Models\ContentVideoLink;
//use App\Models\ContentAvailableLocale;
//use App\Models\ContentVersionFile;
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Storage;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Str;
//
//class SectionController extends Controller
//{
//    public function getSectionsTree()
//    {
//        $sections = Section::with('subsections')
//            ->where('parent_id', null)
//            ->where('is_active', true)
//            ->orderBy('order')
//            ->get()
//            ->map(function ($section) {
//                return [
//                    'id' => $section->id,
//                    'text' => $section->default_name,
//                    'children' => $section->subsections->map(function ($subsection) {
//                        return [
//                            'id' => 'sub_' . $subsection->id,
//                            'text' => $subsection->default_name,
//                        ];
//                    })
//                ];
//            });
//
//        return response()->json($sections);
//    }
//}
