<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(
        private readonly TagService $tags,
    ) {}

    /**
     * Display a listing of all tags.
     */
    public function index(Request $request): Response
    {
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created tag.
     *
     * Non-Inertia requests (e.g. the task modal's inline tag creator,
     * which POSTs via fetch) receive the new tag as JSON so the UI can
     * immediately select it; Inertia requests keep the original redirect.
     */
    public function store(StoreTagRequest $request): RedirectResponse|JsonResponse
    {
        $tag = $this->tags->create($request->validated());

        if (! $request->inertia()) {
            return response()->json(['tag' => $tag->toArray()], 201);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag created.')]);

        return to_route('tags.index');
    }
}
