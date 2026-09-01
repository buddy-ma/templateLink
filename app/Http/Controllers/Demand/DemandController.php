<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demand\StoreDemandRequest;
use App\Http\Requests\Demand\UpdateDemandRequest;
use App\Http\Resources\DemandResource;
use App\Models\Brand;
use App\Models\Demand;
use App\Models\DemandAttachment;
use App\Models\MaterialNature;
use App\Models\User;
use App\Models\ValidationPipeline;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DemandController extends Controller
{
    public function __construct(
        private readonly DemandWorkflowService $workflow,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Demand::class);

        $user = $request->user();
        assert($user instanceof User);

        $query = Demand::query()
            ->with(['creator', 'brand', 'materialNature'])
            ->visibleTo($user)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->integer('brand_id'));
        }

        if ($request->string('scope')->toString() === 'mine') {
            $query->where('created_by', $user->id);
        }

        if ($request->string('scope')->toString() === 'team') {
            $reportIds = $user->reports()->pluck('id');
            $query->where(function ($q) use ($user, $reportIds): void {
                $q->where('created_by', $user->id);
                if ($reportIds->isNotEmpty()) {
                    $q->orWhereIn('created_by', $reportIds);
                }
            });
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q')->toString().'%';
            $query->where(function ($builder) use ($q): void {
                $builder->where('reference', 'like', $q)
                    ->orWhere('description', 'like', $q);
            });
        }

        $demands = $query->paginate(12)->withQueryString()->through(
            fn (Demand $demand) => (new DemandResource($demand))->resolve($request),
        );

        return Inertia::render('demands/Index', [
            'demands' => $demands,
            'filters' => [
                'status' => $request->string('status')->toString() ?: null,
                'brand_id' => $request->filled('brand_id') ? $request->integer('brand_id') : null,
                'scope' => $request->string('scope')->toString() ?: null,
                'q' => $request->string('q')->toString() ?: null,
            ],
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canCreate' => $user->can('create', Demand::class),
            'canManageCatalog' => $user->can('demands.manage_catalog'),
            'canManagePipeline' => $user->can('demands.manage_pipeline'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Demand::class);

        return Inertia::render('demands/Create', $this->formOptions($request));
    }

    public function store(StoreDemandRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $demand = $this->workflow->create(
            $user,
            [
                ...$request->safe()->except(['nature_materiel_files', 'referentiel_produit_files']),
                'submit' => $request->boolean('submit'),
            ],
            $request->file('nature_materiel_files', []) ?: [],
            $request->file('referentiel_produit_files', []) ?: [],
        );

        return redirect()
            ->route('demands.show', $demand)
            ->with('success', __('demands.messages.saved'));
    }

    public function show(Request $request, Demand $demand): Response
    {
        $this->authorize('view', $demand);

        $demand->load([
            'creator',
            'brand',
            'materialNature',
            'closedBy',
            'validators.user',
            'validators.actor',
            'attachments',
            'events.actor',
            'events.attachments',
        ]);

        return Inertia::render('demands/Show', [
            'demand' => (new DemandResource($demand))->resolve($request),
        ]);
    }

    public function edit(Request $request, Demand $demand): Response
    {
        $this->authorize('update', $demand);

        $demand->load(['validators.user', 'attachments', 'brand', 'materialNature']);

        return Inertia::render('demands/Edit', [
            ...$this->formOptions($request),
            'demand' => (new DemandResource($demand))->resolve($request),
        ]);
    }

    public function update(UpdateDemandRequest $request, Demand $demand): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->workflow->update(
            $demand,
            $user,
            [
                ...$request->safe()->except([
                    'nature_materiel_files',
                    'referentiel_produit_files',
                    'remove_attachment_ids',
                ]),
                'submit' => $request->boolean('submit'),
            ],
            $request->file('nature_materiel_files', []) ?: [],
            $request->file('referentiel_produit_files', []) ?: [],
            $request->input('remove_attachment_ids', []) ?: [],
        );

        return redirect()
            ->route('demands.show', $demand)
            ->with('success', __('demands.messages.updated'));
    }

    public function download(Request $request, Demand $demand, DemandAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $demand);

        if ($attachment->demand_id !== $demand->id) {
            abort(404);
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404);
        }

        $mime = $attachment->mime ?: 'application/octet-stream';
        $canInline = $request->boolean('inline')
            && (str_contains($mime, 'pdf') || str_starts_with($mime, 'image/'));

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $mime],
            $canInline ? 'inline' : 'attachment',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $pipeline = ValidationPipeline::defaultPipeline();

        return [
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'dosage_form', 'presentation'])
                ->map(fn (Brand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'sku' => $brand->sku,
                    'dosage_form' => $brand->dosage_form,
                    'presentation' => $brand->presentation,
                    'label' => $brand->displayLabel(),
                ]),
            'materialNatures' => MaterialNature::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'validators' => User::role('validator')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'defaultValidatorIds' => $pipeline
                ? $pipeline->steps
                    ->filter(fn ($step) => $step->user_id !== null)
                    ->pluck('user_id')
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all()
                : [],
            'pipeline' => $pipeline ? [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
            ] : null,
        ];
    }
}
