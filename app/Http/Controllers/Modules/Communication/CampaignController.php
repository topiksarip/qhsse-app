<?php

namespace App\Http\Controllers\Modules\Communication;

use App\Core\Activity\ActivityService;
use App\Core\Notifications\NotificationService;
use App\Core\Numbering\NumberingService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Communication\AcknowledgeCampaignRequest;
use App\Http\Requests\Modules\Communication\StoreCampaignRequest;
use App\Http\Requests\Modules\Communication\UpdateCampaignRequest;
use App\Models\Modules\Communication\Campaign;
use App\Models\Modules\Communication\CampaignAcknowledgment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        protected ActivityService $activityService,
        protected NumberingService $numberingService,
        protected NotificationService $notificationService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $query = Campaign::with(['author', 'site', 'department', 'createdBy', 'updatedBy'])
            ->withCount('acknowledgments');

        $filters = $request->only(['search', 'type', 'status', 'target_audience', 'site_id', 'department_id']);
        
        $listQuery = ListQuery::for($query, $request);
        $listQuery->search(['campaign_number', 'title'], $filters['search'] ?? null);
        $listQuery->filter('type', $filters['type'] ?? null);
        $listQuery->filter('status', $filters['status'] ?? null);
        $listQuery->filter('target_audience', $filters['target_audience'] ?? null);
        $listQuery->filter('site_id', $filters['site_id'] ?? null);
        $listQuery->filter('department_id', $filters['department_id'] ?? null);
        $listQuery->sort($request->input('sort', 'created_at'), $request->input('direction', 'desc'));

        $campaigns = $listQuery->paginate($request->input('per_page', 15));

        return Inertia::render('Modules/Communication/Campaign/Index', [
            'campaigns' => $campaigns,
            'filters' => $filters,
            'can' => [
                'create' => $request->user()->can('create', Campaign::class),
                'delete' => $request->user()->can('delete', Campaign::class),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Campaign::class);

        return Inertia::render('Modules/Communication/Campaign/CreateOrEdit', [
            'campaign' => null,
        ]);
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();
            
            $campaignNumber = $this->numberingService->generate(
                'communication',
                $user,
                null,
                false,
                'campaign'
            );

            $campaign = Campaign::create([
                ...$request->validated(),
                'campaign_number' => $campaignNumber->number,
                'status' => 'draft',
                'author_id' => $user->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityService->log(
                'communication',
                $campaign->id,
                'campaign.created',
                "Campaign {$campaign->campaign_number} created: {$campaign->title}",
                $user
            );

            DB::commit();

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'Kampanye berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat kampanye: ' . $e->getMessage());
        }
    }

    public function show(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load(['author', 'site', 'department', 'createdBy', 'updatedBy']);
        
        $canViewAcknowledgments = $this->authorize('viewAcknowledgments', Campaign::class);
        
        if ($canViewAcknowledgments) {
            $campaign->load(['acknowledgments.user']);
        }

        // Track view (increment view_count once per user)
        $cacheKey = "campaign:{$campaign->id}:viewed:" . auth()->id();
        if (!cache()->has($cacheKey)) {
            $campaign->increment('view_count');
            cache()->put($cacheKey, true, now()->addDay());
        }

        return Inertia::render('Modules/Communication/Campaign/Show', [
            'campaign' => $campaign,
            'hasAcknowledged' => $campaign->isAcknowledgedBy(auth()->user()),
            'canAcknowledge' => auth()->user()->can('acknowledge', $campaign),
            'canViewAcknowledgments' => $canViewAcknowledgments,
        ]);
    }

    public function edit(Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        $campaign->load(['site', 'department']);

        return Inertia::render('Modules/Communication/Campaign/CreateOrEdit', [
            'campaign' => $campaign,
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $campaign->update([
                ...$request->validated(),
                'updated_by' => $request->user()->id,
            ]);

            $this->activityService->log(
                'communication',
                $campaign->id,
                'campaign.updated',
                "Campaign {$campaign->campaign_number} updated: {$campaign->title}",
                $request->user()
            );

            DB::commit();

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'Kampanye berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui kampanye: ' . $e->getMessage());
        }
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        DB::beginTransaction();
        try {
            $campaignNumber = $campaign->campaign_number;
            $campaign->delete();

            $this->activityService->log(
                'communication',
                $campaign->id,
                'campaign.deleted',
                "Campaign {$campaignNumber} deleted",
                auth()->user()
            );

            DB::commit();

            return redirect()
                ->route('campaigns.index')
                ->with('success', 'Kampanye berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus kampanye: ' . $e->getMessage());
        }
    }

    public function publish(Campaign $campaign): RedirectResponse
    {
        $this->authorize('publish', $campaign);

        DB::beginTransaction();
        try {
            $campaign->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_by' => auth()->id(),
            ]);

            // M11 WS-1: notify the resolved target audience
            $recipients = $campaign->audienceUsers()->get();
            $this->notificationService->notifyMany(
                $recipients,
                'campaign.published',
                [
                    'title' => $campaign->title,
                    'message' => "New campaign published: {$campaign->title}",
                ],
                auth()->user(),
                'communication',
                $campaign->id,
                route('campaigns.show', $campaign)
            );

            $this->activityService->log(
                'communication',
                $campaign->id,
                'campaign.published',
                "Campaign {$campaign->campaign_number} published: {$campaign->title}",
                auth()->user()
            );

            DB::commit();

            return redirect()
                ->route('campaigns.show', $campaign)
                ->with('success', 'Kampanye berhasil dipublikasikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mempublikasikan kampanye: ' . $e->getMessage());
        }
    }

    public function acknowledge(AcknowledgeCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        DB::beginTransaction();
        try {
            CampaignAcknowledgment::acknowledge($campaign, $request->user(), $request->ip());

            $this->activityService->log(
                'communication',
                $campaign->id,
                'campaign.acknowledged',
                "Campaign {$campaign->campaign_number} acknowledged by " . $request->user()->name,
                $request->user()
            );

            DB::commit();

            return back()->with('success', 'Kampanye berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengkonfirmasi kampanye: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $this->authorize('export', Campaign::class);

        // TODO: Implement CSV export
        return back()->with('info', 'Export feature coming soon.');
    }
}
