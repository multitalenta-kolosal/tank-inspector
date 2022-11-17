<?php

namespace Modules\Trip\Services;

use Modules\Trip\Entities\Core;
use Modules\Trip\Entities\Inspection;
use Modules\Recruiter\Entities\Booking;

use Exception;
use Carbon\Carbon;
use Auth;

use ConsoleTVs\Charts\Classes\Echarts\Chart;
use App\Charts\InspectionPerStatus;
use App\Exceptions\GeneralException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


use Modules\Trip\Imports\InspectionsImport;
use Modules\Trip\Events\InspectionRegistered;

use App\Events\Backend\UserCreated;
use App\Events\Backend\UserUpdated;

use App\Models\User;
use App\Models\Userprofile;

class InspectionService{

    public function __construct()
        {        
        $this->module_title = Str::plural(class_basename(Inspection::class));
        $this->module_name = Str::lower($this->module_title);
        
        }

    public function list(){

        Log::info(label_case($this->module_title.' '.__FUNCTION__).' | User:'.(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        $inspection =Inspection::query()->orderBy('id','desc')->get();
        
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }
    
    public function getAllInspections(){

        $inspection =Inspection::query()->available()->orderBy('id','desc')->get();
        
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function getPopularInspection(){

        $inspection =DB::table('bookings')
                    ->select('bookings.inspection_id','name','', DB::raw('count(*) as total'))
                    ->join('inspections', 'bookings.inspection_id', '=', 'inspections.id')
                    ->groupBy('bookings.inspection_id')
                    ->orderBy('total','desc')
                    ->get();
                
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function filterInspections($pagination,$request){

        $inspection =Inspection::query()->available();

        if(count($request->all()) > 0){
            if($request->has('major')){
                $inspection->whereIn('major', $request->input('major'));
            }

            if($request->has('year_class')){
                $inspection->whereIn('year_class', $request->input('year_class'));
            }

            if($request->has('height')){
                $inspection->where('height', ">=", (float)$request->input('height'));
            }

            if($request->has('weight')){
                $inspection->where('weight', ">=", (float)$request->input('weight'));
            }

            if($request->has('skills')){
                $inspection->where(function ($query) use ($request){
                    $checkSkills = $request->input('skills');
                    foreach($checkSkills as $skill){
                        if($request->input('must_have_all_skills')){
                            $query->where('skills', 'like','%'.$skill.'%');
                        }else{
                            $query->orWhere('skills', 'like','%'.$skill.'%');
                        }
                    }
                });
            }

            if($request->has('certificate')){
                $inspection->where(function ($query) use ($request){
                    $checkCerts = $request->input('certificate');
                    foreach($checkCerts as $cert){
                        if($request->input('must_have_all_certificate')){
                            $query->where('certificate', 'like','%'.$cert.'%');
                        }else{
                            $query->orWhere('certificate', 'like','%'.$cert.'%');
                        }
                    }
                });
            }
        }

        $inspection = $inspection->paginate($pagination);
        
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function getPaginatedInspections($pagination,$request){

        $inspection =Inspection::query()->available();

        if(count($request->all()) > 0){

        }

        $inspection = $inspection->paginate($pagination);
        
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }
    
    public function get_inspection($request){

        $id = $request["id"];

        $inspection =Inspection::findOrFail($id);
        
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function getList(){

        $inspection =Inspection::query()->orderBy('order','asc')->get();

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }


    public function create(){

       Log::info(label_case($this->module_title.' '.__function__).' | User:'.(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? '0').')');
        
        $createOptions = [];

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $createOptions,
        );
    }

    public function store(Request $request){

        $data = $request->all();
        DB::beginTransaction();

        try {
            
            $inspectionObject = new Inspection;
            $inspectionObject->fill($data);

            if($inspectionObject->tahun_registrasi){
                $inspectionObject->tahun_registrasi = convert_slash_to_basic_date($inspectionObject->tahun_registrasi);
            }
            if($inspectionObject->exp_stnk){
                $inspectionObject->exp_stnk = convert_slash_to_basic_date($inspectionObject->exp_stnk);
            }
            if($inspectionObject->exp_keur){
                $inspectionObject->exp_keur = convert_slash_to_basic_date($inspectionObject->exp_keur);
            }
            if($inspectionObject->exp_tera){
                $inspectionObject->exp_tera = convert_slash_to_basic_date($inspectionObject->exp_tera);
            }
            if($inspectionObject->exp_kip){
                $inspectionObject->exp_kip = convert_slash_to_basic_date($inspectionObject->exp_kip);
            }
            if($inspectionObject->end_date_mt){
                $inspectionObject->end_date_mt = convert_slash_to_basic_date($inspectionObject->end_date_mt);
            }

            $inspectionObjectArray = $inspectionObject->toArray();

            $inspection = Inspection::create($inspectionObjectArray);
            
        }catch (Exception $e){
            DB::rollBack();
            Log::critical(label_case($this->module_title.' ON LINE '.__LINE__.' AT '.Carbon::now().' | Function:'.__FUNCTION__).' | msg: '.$e->getMessage());
            return (object) array(
                'error'=> true,
                'message'=> $e->getMessage(),
                'data'=> null,
            );
        }

        DB::commit();

        Log::info(label_case($this->module_title.' '.__function__)." | '".$inspection->name.'(ID:'.$inspection->id.") ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function show($id, $inspectionId = null){

        Log::info(label_case($this->module_title.' '.__function__).' | User:'.(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> Inspection::findOrFail($id),
        );
    }

    public function edit($id){

        $inspection = Inspection::findOrFail($id);

        Log::info(label_case($this->module_title.' '.__function__)." | '".$inspection->name.'(ID:'.$inspection->id.") ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspection,
        );
    }

    public function update(Request $request,$id){

        $data = $request->all();

        DB::beginTransaction();

        try{

            $inspectionObject = new Inspection;
            $inspectionObject->fill($data);
            
            if($inspectionObject->tahun_registrasi){
                $inspectionObject->tahun_registrasi = convert_slash_to_basic_date($inspectionObject->tahun_registrasi);
            }
            if($inspectionObject->exp_stnk){
                $inspectionObject->exp_stnk = convert_slash_to_basic_date($inspectionObject->exp_stnk);
            }
            if($inspectionObject->exp_keur){
                $inspectionObject->exp_keur = convert_slash_to_basic_date($inspectionObject->exp_keur);
            }
            if($inspectionObject->exp_tera){
                $inspectionObject->exp_tera = convert_slash_to_basic_date($inspectionObject->exp_tera);
            }
            if($inspectionObject->exp_kip){
                $inspectionObject->exp_kip = convert_slash_to_basic_date($inspectionObject->exp_kip);
            }
            if($inspectionObject->end_date_mt){
                $inspectionObject->end_date_mt = convert_slash_to_basic_date($inspectionObject->end_date_mt);
            }
            
            $updating = Inspection::findOrFail($id)->update($inspectionObject->toArray());

            $updated_inspection = Inspection::findOrFail($id);


        }catch (Exception $e){
            DB::rollBack();
            report($e);
            Log::critical(label_case($this->module_title.' AT '.Carbon::now().' | Function:'.__FUNCTION__).' | Msg: '.$e->getMessage());
            return (object) array(
                'error'=> true,
                'message'=> $e->getMessage(),
                'data'=> null,
            );
        }

        DB::commit();

        Log::info(label_case($this->module_title.' '.__FUNCTION__)." | '".$updated_inspection->name.'(ID:'.$updated_inspection->id.") ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $updated_inspection,
        );
    }

    public function destroy($id){

        DB::beginTransaction();

        try{
            $inspections = Inspection::findOrFail($id);
    
            $deleted = $inspections->delete();
        }catch (Exception $e){
            DB::rollBack();
            Log::critical(label_case($this->module_title.' AT '.Carbon::now().' | Function:'.__FUNCTION__).' | Msg: '.$e->getMessage());
            return (object) array(
                'error'=> true,
                'message'=> $e->getMessage(),
                'data'=> null,
            );
        }

        DB::commit();

        Log::info(label_case($this->module_title.' '.__FUNCTION__)." | '".$inspections->name.', ID:'.$inspections->id." ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspections,
        );
    }

    public function trashed(){

        Log::info(label_case($this->module_title.' View'.__FUNCTION__).' | User:'.(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> Inspection::bookingonlyTrashed()->get(),
        );
    }

    public function restore($id){

        DB::beginTransaction();

        try{
            $restoring =  Inspection::bookingwithTrashed()->where('id',$id)->restore();
            $inspections = Inspection::findOrFail($id);
        }catch (Exception $e){
            DB::rollBack();
            Log::critical(label_case($this->module_title.' AT '.Carbon::now().' | Function:'.__FUNCTION__).' | Msg: '.$e->getMessage());
            return (object) array(
                'error'=> true,
                'message'=> $e->getMessage(),
                'data'=> null,
            );
        }

        DB::commit();

        Log::info(label_case(__FUNCTION__)." ".$this->module_title.": ".$inspections->name.", ID:".$inspections->id." ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspections,
        );
    }

    public function purge($id){
        DB::beginTransaction();

        try{
            $inspections = Inspection::bookingwithTrashed()->findOrFail($id);
    
            $deleted = $inspections->forceDelete();
        }catch (Exception $e){
            DB::rollBack();
            Log::critical(label_case($this->module_title.' AT '.Carbon::now().' | Function:'.__FUNCTION__).' | Msg: '.$e->getMessage());
            return (object) array(
                'error'=> true,
                'message'=> $e->getMessage(),
                'data'=> null,
            );
        }

        DB::commit();

        Log::info(label_case($this->module_title.' '.__FUNCTION__)." | '".$inspections->name.', ID:'.$inspections->id." ' by User:".(Auth::user()->name ?? 'unknown').'(ID:'.(Auth::user()->id ?? "0").')');

        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $inspections,
        );
    }

    public function import(Request $request){
        $import = Excel::import(new InspectionsImport($request), $request->file('data_file'));
    
        return (object) array(
            'error'=> false,            
            'message'=> '',
            'data'=> $import,
        );
    }

    public static function prepareStatusFilter(){
        
        $raw_status = Core::getRawData('recruitment_status');
        $status = [];
        foreach($raw_status as $key => $value){
            $status += [$value => $value];
        }

        return $status;
    }

    public static function prepareOptions(){
        
        $raw_majors = Core::getRawData('major');
        $majors = [];
        foreach($raw_majors as $key => $value){
            $majors += [$value => $value];
        }

        $skills_raw = Core::getRawData('skills');
        $skills = [];
        foreach($skills_raw as $value){
            $skills += [$value => $value];
        }

        $certificate_raw= Core::getRawData('certificate');
        $certificate = [];
        foreach($certificate_raw as $value){
            $certificate += [$value => $value];
        }

        $options = array(
            'majors'         => $majors,
            'skills'              => $skills,
            'certificate'         => $certificate,
        );

        return $options;
    }

    public static function prepareFilter(){
        
        $options = self::prepareOptions();

        $year_class_raw = DB::table('inspections')
                        ->select('year_class', DB::raw('count(*) as total'))
                        ->groupBy('year_class')
                        ->orderBy('year_class','desc')
                        ->get();
        $year_class = [];
            foreach($year_class_raw as $item){
                $year_class += [$item->year_class => $item->year_class];
                // $year_class += [$item->year_class => $item->year_class." (".$item->total.")"];
            }


        $filterOp = array(
            'year_class'          => $year_class,
        );

        return array_merge($options,$filterOp);
    }

    public function getInspectionPerStatusChart(){

        $chart = new Chart;

        $raw_status_order = Core::getRawData('recruitment_status');
        $status_order = [];
        foreach($raw_status_order as $key => $value){
            $status_order += [$value => 0];
        }

        $last_key = array_key_last($status_order);
        $remove_last_status = array_pop($status_order);

        $raw_majors = Core::getRawData('major');
        $majors = [];

        foreach($raw_majors as $key => $value){
            $majors[] = $value;
        }

        foreach($majors as $major){

            $status_raw = DB::table('bookings')
                        ->select('status', DB::raw('count(*) as total'))
                        ->join('inspections', 'bookings.inspection_id', '=', 'inspections.id')
                        ->where('inspections.major',$major)
                        ->where('inspections.available',1)
                        ->where('status',"<>",$last_key)
                        ->groupBy('status')
                        ->orderBy('status','desc')
                        ->get();
            $status = [];

            foreach($status_raw as $item){
                $status += [$item->status => $item->total];
            }

            $status = array_merge($status_order, $status);

            [$keys, $values] = Arr::divide($status);

            $chart->labels($keys);

            $chart->dataset($major, 'bar',$values);
        }

        $chart->options([
            "xAxis" => [
                "axisLabel" => [
                    "interval" => 0,
                    "overflow" => "truncate",
                ],
            ],
            "yAxis" => [
                "minInterval" => 1
            ],
        ]);

        return $chart;
    }

    public function getDoneInspectionsChart(){

        $chart = new Chart;

        $raw_status_order = Core::getRawData('recruitment_status');
        $status_order = [];
        foreach($raw_status_order as $key => $value){
            $status_order += [$value => 0];
        }

        $last_key = array_key_last($status_order);
        $remove_last_status = array_pop($status_order);

        $raw_majors = Core::getRawData('major');
        $majors = [];

        foreach($raw_majors as $key => $value){
            $majors[] = $value;
        }

        $year_class_list_raw = DB::table('inspections')
                                ->select('year_class')
                                ->groupBy('year_class')
                                ->orderBy('year_class','asc')
                                ->limit(8)
                                ->get();
        
        $year_class_list= [];


        foreach($year_class_list_raw as $item){
            $year_class_list += [$item->year_class => 0];
        }                    

        foreach($majors as $major){

            $year_class_raw = DB::table('bookings')
                        ->select('inspections.year_class', DB::raw('count(*) as total'))
                        ->join('inspections', 'bookings.inspection_id', '=', 'inspections.id')
                        ->distinct()
                        ->where('inspections.major',$major)
                        ->where('status',"=",$last_key)
                        ->groupBy('inspections.year_class')
                        ->orderBy('inspections.year_class','asc')
                        ->get();

            $year_class = [];

            foreach($year_class_raw as $item){
                $year_class += [$item->year_class => $item->total];
            }

            $year_class =  $year_class + $year_class_list;

            ksort($year_class);

            [$keys, $values] = Arr::divide($year_class);

            $chart->labels($keys);

            $chart->dataset($major, 'bar',$values);
        }

        $chart->options([
            "xAxis" => [
                "axisLabel" => [
                    "interval" => 0,
                    "overflow" => "truncate",
                ],
            ],
            "yAxis" => [
                "minInterval" => 1
            ],
        ]);

        return $chart;
    }

    public function getInspectionPerYearClassChart(){

        $chart = new Chart;

        $inspections_active = DB::table('inspections')
                            ->select('year_class', DB::raw('count(*) as total'))
                            ->where('available',1)
                            ->groupBy('year_class')
                            ->orderBy('year_class','asc')
                            ->get();

        $inspections=[];
        foreach($inspections_active as $item){
            $inspections += [$item->year_class => $item->total];
        }

        [$keys, $values] = Arr::divide($inspections);

        $chart->labels($keys);

        $chart->dataset("Jumlah Siswa", 'bar',$values);
        
        $chart->options([
            "xAxis" => [
                "axisLabel" => [
                    "interval" => 0,
                    "overflow" => "truncate",
                ],
            ],
            "yAxis" => [
                "minInterval" => 1
            ],
        ]);

        return $chart;
    }

    public static function prepareInsight(){

        $countAllInspections = Inspection::all()->count();

        $raw_status= Core::getRawData('recruitment_status');
        $status = [];

        foreach($raw_status as $key => $value){
            $status[] = $value;
        }

        $countDoneInspections = Booking::where('status',end($status))->get()->count();
        
        $stats = (object) array(
            'status'                    => $status,
            'countAllInspections'          => $countAllInspections,
            'countDoneInspections'         => $countDoneInspections,
        );

        return $stats;
    }

}