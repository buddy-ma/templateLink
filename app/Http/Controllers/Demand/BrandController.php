<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demand\StoreBrandRequest;
use App\Http\Requests\Demand\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('demands.manage_catalog'), 403);

        $brands = Brand::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'sku' => $brand->sku,
                'dosage_form' => $brand->dosage_form,
                'presentation' => $brand->presentation,
                'label' => $brand->displayLabel(),
                'is_active' => $brand->is_active,
            ]);

        return Inertia::render('demands/brands/Index', [
            'brands' => $brands,
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::query()->create([
            'name' => $request->string('name')->toString(),
            'sku' => $request->input('sku') ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', __('demands.messages.brand_created'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update([
            'name' => $request->string('name')->toString(),
            'sku' => $request->input('sku') ?: null,
            'is_active' => $request->boolean('is_active', $brand->is_active),
        ]);

        return back()->with('success', __('demands.messages.brand_updated'));
    }

    public function destroy(Request $request, Brand $brand): RedirectResponse
    {
        abort_unless($request->user()?->can('demands.manage_catalog'), 403);

        if ($brand->demands()->exists()) {
            return back()->withErrors(['brand' => __('demands.messages.brand_in_use')]);
        }

        $brand->delete();

        return back()->with('success', __('demands.messages.brand_deleted'));
    }
}
