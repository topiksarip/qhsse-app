<?php

namespace App\Http\Controllers\Modules\Training;

use App\Core\Activity\ActivityService;
use App\Core\Audit\AuditService;
use App\Core\Query\ListQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Training\StoreTrainingProgramRequest;
use App\Http\Requests\Modules\Training\UpdateTrainingProgramRequest;
use App\Models\Modules\Training\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrainingProgramController extends Controller
{
    public function __construct(
        private readonly ListQuery $listQuery,
        private readonly AuditService $auditService,
        private readonly ActivityService $activityService,
    ) {}

    /**
     * Display a listing of training programs.
     */
    public function index(Request $request): Response
    {
        $query = TrainingProgram::query()
            ->when($request->get('category'), fn ($q, $category) => $q->where('category', $category))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', (bool) $request->get('is_active')))
            ->when($request->has('is_certification'), fn ($q) => $q->where('is_certification', (bool) $request->get('is_certification')))
            ->orderBy('created_at', 'desc');

        $programs = $this->listQuery->paginate(
            $query,
            $request->get('search'),
            ['code', 'name', 'description'],
            (int) $request->get('per_page', 15)
        );

        return Inertia::render('Modules/Training/Programs/Index', [
            'programs' => $programs,
            'filters' => $request->only(['search', 'category', 'is_active', 'is_certification']),
            'categories' => TrainingProgram::getCategories(),
        ]);
    }

    /**
     * Show the form for creating a new training program.
     */
    public function create(): Response
    {
        $this->authorize('training.programs.create');

        return Inertia::render('Modules/Training/Programs/Form', [
            'categories' => TrainingProgram::getCategories(),
        ]);
    }

    /**
     * Store a newly created training program in storage.
     */
    public function store(StoreTrainingProgramRequest $request): RedirectResponse
    {
        $program = TrainingProgram::create($request->validated());

        $this->auditService->log(
            'training',
            'create',
            $program->id,
            'Created training program: '.$program->name
        );

        $this->activityService->log(
            'training',
            $program->id,
            'created',
            'Program pelatihan "'.$program->name.'" dibuat',
            null,
            $program->toArray()
        );

        return redirect()->route('training.programs.show', $program)
            ->with('success', 'Program pelatihan berhasil dibuat.');
    }

    /**
     * Display the specified training program.
     */
    public function show(TrainingProgram $program): Response
    {
        $this->authorize('training.programs.view');

        $program->load(['trainingRecords' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Modules/Training/Programs/Show', [
            'program' => $program,
            'statistics' => [
                'total_records' => $program->trainingRecords()->count(),
                'completed' => $program->trainingRecords()->where('status', 'completed')->count(),
                'in_progress' => $program->trainingRecords()->where('status', 'in_progress')->count(),
                'scheduled' => $program->trainingRecords()->where('status', 'scheduled')->count(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified training program.
     */
    public function edit(TrainingProgram $program): Response
    {
        $this->authorize('training.programs.update');

        return Inertia::render('Modules/Training/Programs/Form', [
            'program' => $program,
            'categories' => TrainingProgram::getCategories(),
        ]);
    }

    /**
     * Update the specified training program in storage.
     */
    public function update(UpdateTrainingProgramRequest $request, TrainingProgram $program): RedirectResponse
    {
        $oldData = $program->toArray();
        $program->update($request->validated());

        $this->auditService->log(
            'training',
            'update',
            $program->id,
            'Updated training program: '.$program->name
        );

        $this->activityService->log(
            'training',
            $program->id,
            'updated',
            'Program pelatihan "'.$program->name.'" diperbarui',
            $oldData,
            $program->fresh()->toArray()
        );

        return redirect()->route('training.programs.show', $program)
            ->with('success', 'Program pelatihan berhasil diperbarui.');
    }
}
