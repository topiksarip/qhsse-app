<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * IncidentReport -> JSON (Flutter contract).
 * Mirrors docs-qhsse/flutter/03_API_ENDPOINTS.md example.
 */
class IncidentReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'incident_number' => $this->incident_number,
            'title' => $this->title,
            'category' => $this->category,
            'status' => $this->status,
            'description' => $this->description,
            'immediate_action' => $this->immediate_action,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'site_id' => $this->site_id,
            'area_id' => $this->area_id,
            'department_id' => $this->department_id,
            'reporter_id' => $this->reporter_id,
            'severity_id' => $this->severity_id,
            'priority_id' => $this->priority_id,
            'ppe_involved' => (bool) $this->ppe_involved,
            'apd_item_id' => $this->apd_item_id,
            'ppe_failure' => (bool) $this->ppe_failure,
            'ppe_notes' => $this->ppe_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'site' => $this->whenLoaded('site', fn () => ['id' => $this->site?->id, 'name' => $this->site?->name]),
            'area' => $this->whenLoaded('area', fn () => ['id' => $this->area?->id, 'name' => $this->area?->name]),
            'severity' => $this->whenLoaded('severity', fn () => ['id' => $this->severity?->id, 'name' => $this->severity?->name]),
            'priority' => $this->whenLoaded('priority', fn () => ['id' => $this->priority?->id, 'name' => $this->priority?->name]),
            'reporter' => $this->whenLoaded('reporter', fn () => ['id' => $this->reporter?->id, 'name' => $this->reporter?->name]),
        ];
    }
}
