<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Demand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Demand */
class DemandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Demand $demand */
        $demand = $this->resource;
        $user = $request->user();

        $brand = $demand->relationLoaded('brand') && $demand->brand ? [
            'id' => $demand->brand->id,
            'name' => $demand->brand->name,
            'sku' => $demand->brand->sku,
        ] : null;

        return [
            'id' => $demand->id,
            'reference' => $demand->reference,
            'description' => $demand->description,
            'status' => $demand->status->value,
            'current_step' => $demand->current_step,
            'refused_reason' => $demand->refused_reason,
            'blocked_reason' => $demand->blocked_reason,
            'closed_at' => $demand->closed_at?->toIso8601String(),
            'created_at' => $demand->created_at?->toIso8601String(),
            'updated_at' => $demand->updated_at?->toIso8601String(),
            'creator' => $demand->relationLoaded('creator') ? [
                'id' => $demand->creator->id,
                'name' => $demand->creator->name,
                'email' => $demand->creator->email,
            ] : null,
            'brand' => $brand,
            'product' => $brand,
            'material_nature' => $demand->relationLoaded('materialNature') ? [
                'id' => $demand->materialNature->id,
                'name' => $demand->materialNature->name,
            ] : null,
            'closed_by' => $demand->relationLoaded('closedBy') && $demand->closedBy ? [
                'id' => $demand->closedBy->id,
                'name' => $demand->closedBy->name,
            ] : null,
            'validators' => $demand->relationLoaded('validators')
                ? $demand->validators->map(fn ($v) => [
                    'id' => $v->id,
                    'user_id' => $v->user_id,
                    'role_name' => $v->role_name,
                    'is_group' => $v->isGroupStep(),
                    'position' => $v->position,
                    'status' => $v->status->value,
                    'acted_at' => $v->acted_at?->toIso8601String(),
                    'comment' => $v->comment,
                    'acted_by' => $v->relationLoaded('actor') && $v->actor ? [
                        'id' => $v->actor->id,
                        'name' => $v->actor->name,
                    ] : null,
                    'user' => $v->relationLoaded('user') && $v->user ? [
                        'id' => $v->user->id,
                        'name' => $v->user->name,
                        'email' => $v->user->email,
                    ] : null,
                ])->values()->all()
                : [],
            'attachments' => $demand->relationLoaded('attachments')
                ? $demand->attachments
                    ->filter(fn ($a) => $a->collection->value !== 'decision')
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'collection' => $a->collection->value,
                        'original_name' => $a->original_name,
                        'mime' => $a->mime,
                        'size' => $a->size,
                        'demand_event_id' => $a->demand_event_id,
                        'created_at' => $a->created_at?->toIso8601String(),
                    ])->values()->all()
                : [],
            'events' => $demand->relationLoaded('events')
                ? $demand->events->map(fn ($e) => [
                    'id' => $e->id,
                    'type' => $e->type,
                    'from_status' => $e->from_status,
                    'to_status' => $e->to_status,
                    'step' => $e->step,
                    'comment' => $e->comment,
                    'created_at' => $e->created_at?->toIso8601String(),
                    'actor' => $e->relationLoaded('actor') && $e->actor ? [
                        'id' => $e->actor->id,
                        'name' => $e->actor->name,
                    ] : null,
                    'attachments' => $e->relationLoaded('attachments')
                        ? $e->attachments->map(fn ($a) => [
                            'id' => $a->id,
                            'collection' => $a->collection->value,
                            'original_name' => $a->original_name,
                            'mime' => $a->mime,
                            'size' => $a->size,
                            'demand_event_id' => $a->demand_event_id,
                            'created_at' => $a->created_at?->toIso8601String(),
                        ])->values()->all()
                        : [],
                ])->values()->all()
                : [],
            'permissions' => $user ? [
                'update' => $user->can('update', $demand),
                'validate' => $user->can('validate', $demand) || $user->can('managerValidate', $demand),
                'business_validate' => $user->can('businessValidate', $demand),
                'refuse_or_block' => $user->can('refuseOrBlock', $demand),
                'unblock' => $user->can('unblock', $demand),
                'close' => $user->can('close', $demand),
            ] : null,
        ];
    }
}
