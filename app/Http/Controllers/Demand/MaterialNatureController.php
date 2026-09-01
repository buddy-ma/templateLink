<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demand\StoreMaterialNatureRequest;
use App\Models\MaterialNature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialNatureController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('demands.manage_catalog'), 403);

        $items = MaterialNature::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('demands/material-natures/Index', [
            'materialNatures' => $items,
        ]);
    }

    public function store(StoreMaterialNatureRequest $request): RedirectResponse|JsonResponse
    {
        $nature = MaterialNature::query()->firstOrCreate([
            'name' => trim($request->string('name')->toString()),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $nature->id,
                'name' => $nature->name,
            ]);
        }

        return back()->with('success', __('demands.messages.nature_created'));
    }

    public function destroy(Request $request, MaterialNature $materialNature): RedirectResponse
    {
        abort_unless($request->user()?->can('demands.manage_catalog'), 403);

        if ($materialNature->demands()->exists()) {
            return back()->withErrors(['material_nature' => __('demands.messages.nature_in_use')]);
        }

        $materialNature->delete();

        return back()->with('success', __('demands.messages.nature_deleted'));
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('demands.access'), 403);

        $q = trim($request->string('q')->toString());

        $items = MaterialNature::query()
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($items);
    }
}
