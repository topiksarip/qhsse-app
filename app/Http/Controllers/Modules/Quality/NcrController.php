<?php
namespace App\Http\Controllers\Modules\Quality;
use App\Core\{Activity\ActivityService,Audit\AuditService,Export\CsvExporter,Numbering\NumberingService,Query\ListQuery};
use App\Http\Controllers\Controller;
use App\Http\Requests\Modules\Quality\{StoreNcrRequest,UpdateNcrRequest};
use App\Models\Core\MasterData\{Department,Severity,Site};
use App\Models\Modules\Quality\Ncr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\{Inertia,Response as InertiaResponse};

class NcrController extends Controller {
    public function __construct(protected NumberingService $numberingService, protected AuditService $auditService, protected ActivityService $activityService) {
        $this->authorizeResource(Ncr::class,'ncr');
    }
    public function index(Request $request): InertiaResponse {
        $query = Ncr::query()->with(['site','department','severity'])->select('ncrs.*');
        $scope = $request->input('scope','all'); $user = $request->user();
        if($scope==='site' && $user->employee?->site_id) $query->where('ncrs.site_id',$user->employee->site_id);
        if($request->filled('site_id')) $query->where('ncrs.site_id',$request->input('site_id'));
        if($request->filled('source')) $query->where('ncrs.source',$request->input('source'));
        if($request->filled('status')) $query->where('ncrs.status',$request->input('status'));
        if($request->filled('search')) { $search=$request->input('search'); $query->where(fn($q)=>$q->where('ncrs.ncr_number','like',"%{$search}%")->orWhere('ncrs.title','like',"%{$search}%")); }
        $ncrs = ListQuery::for($query)->defaultSort('-created_at')->paginate($request->input('per_page',15))->withQueryString();
        return Inertia::render('Modules/Quality/Ncrs/Index',['ncrs'=>$ncrs,'filters'=>$request->only(['scope','site_id','source','status','search']),'sites'=>Site::select('id','name')->orderBy('name')->get(),'sources'=>Ncr::getSources(),'statuses'=>Ncr::getStatuses()]);
    }
    public function create(): InertiaResponse {
        return Inertia::render('Modules/Quality/Ncrs/Form',['sites'=>Site::select('id','name')->orderBy('name')->get(),'departments'=>Department::select('id','name')->orderBy('name')->get(),'severities'=>Severity::select('id','name','level')->orderBy('level')->get(),'sources'=>Ncr::getSources()]);
    }
    public function store(StoreNcrRequest $request) {
        return DB::transaction(function() use($request) {
            $user=$request->user();
            $ncrNumber=$this->numberingService->generate(key:'quality',siteId:null,includeSiteCode:false);
            $data=$request->validated(); $data['ncr_number']=$ncrNumber;
            $ncr=Ncr::create($data);
            $this->auditService->log(moduleName:'quality',action:'create',referenceId:$ncr->id,details:"NCR {$ncr->ncr_number} created",userId:$user->id);
            $this->activityService->log(moduleName:'quality',referenceId:$ncr->id,action:'create',description:"NCR {$ncr->ncr_number} created by {$user->name}",userId:$user->id);
            return redirect()->route('quality.ncrs.show',$ncr)->with('success',"NCR {$ncr->ncr_number} created successfully");
        });
    }
    public function show(Ncr $ncr): InertiaResponse {
        $ncr->load(['site','department','severity','capaAction']);
        return Inertia::render('Modules/Quality/Ncrs/Show',['ncr'=>$ncr]);
    }
    public function edit(Ncr $ncr): InertiaResponse {
        return Inertia::render('Modules/Quality/Ncrs/Form',['ncr'=>$ncr,'sites'=>Site::select('id','name')->orderBy('name')->get(),'departments'=>Department::select('id','name')->orderBy('name')->get(),'severities'=>Severity::select('id','name','level')->orderBy('level')->get(),'sources'=>Ncr::getSources(),'statuses'=>Ncr::getStatuses()]);
    }
    public function update(UpdateNcrRequest $request, Ncr $ncr) {
        return DB::transaction(function() use($request,$ncr) {
            $user=$request->user();
            if($request->input('status')==='closed' && $ncr->status!=='closed') $ncr->closed_at=now();
            $ncr->update($request->validated());
            $this->auditService->log(moduleName:'quality',action:'update',referenceId:$ncr->id,details:"NCR {$ncr->ncr_number} updated",userId:$user->id);
            return redirect()->route('quality.ncrs.show',$ncr)->with('success','NCR updated successfully');
        });
    }
    public function export(Request $request) {
        $this->authorize('export',Ncr::class);
        $query=Ncr::query()->with(['site','severity']); $scope=$request->input('scope','all'); $user=$request->user();
        if($scope==='site' && $user->employee?->site_id) $query->where('ncrs.site_id',$user->employee->site_id);
        if($request->filled('site_id')) $query->where('ncrs.site_id',$request->input('site_id'));
        $ncrs=$query->orderBy('created_at','desc')->get();
        return CsvExporter::export(data:$ncrs,filename:'ncrs_'.now()->format('Y-m-d_His').'.csv',columns:['ncr_number'=>'NCR Number','source'=>'Source','title'=>'Title','site.name'=>'Site','severity.name'=>'Severity','status'=>'Status','created_at'=>'Created At']);
    }
}
