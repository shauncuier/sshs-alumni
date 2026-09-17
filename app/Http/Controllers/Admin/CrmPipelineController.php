<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PipelineStage;
use App\Http\Controllers\Controller;
use App\Http\Resources\CrmContactResource;
use App\Models\CrmContact;
use App\Services\Crm\PipelineService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The pipeline board.
 *
 * Eight columns, each capped. A column showing four hundred contacts is not a
 * board, it is a list with extra scrolling — the cap keeps the board readable
 * and the contact list is where you go to work through a whole stage.
 *
 * @see docs/05-modules.md section 6
 */
class CrmPipelineController extends Controller
{
    /**
     * How many contacts each column shows before it says "and N more".
     */
    private const PER_COLUMN = 25;

    public function __construct(
        private readonly PipelineService $pipeline,
    ) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', CrmContact::class);

        $owner = (string) $request->query('owner', '');
        $counts = $this->pipeline->counts();

        $columns = [];

        foreach (PipelineStage::cases() as $stage) {
            $contacts = CrmContact::query()
                ->with(['owner', 'member', 'tags'])
                ->where('pipeline_status', $stage)
                ->when($owner === 'me', fn ($query) => $query->where('owner_id', $request->user()?->id))
                ->when($owner === 'none', fn ($query) => $query->whereNull('owner_id'))
                ->orderByDesc('last_activity_at')
                ->orderByDesc('id')
                ->limit(self::PER_COLUMN)
                ->get();

            $columns[] = [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'total' => $counts[$stage->value],
                // The filtered count, so "and N more" is honest when a filter
                // is on rather than quoting the unfiltered total.
                'shown' => $contacts->count(),
                'contacts' => CrmContactResource::collection($contacts)->resolve(),
            ];
        }

        return Inertia::render('admin/crm/pipeline', [
            'columns' => $columns,
            'filters' => ['owner' => $owner === '' ? null : $owner],
            'can' => [
                'move' => $request->user()?->can('crm.manage') ?? false,
            ],
        ]);
    }
}
