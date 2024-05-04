<?php

namespace App\Http\Controllers;

use App\Mail\JobNobtificationEmail;
use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobsController extends Controller
{

    // hàm này hiển thị trang danh sách việc làm
    public function index(Request $request)
    {

        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();

        $jobs = Job::where('status', 1);

        // Tìm kiếm bằng từ khóa
        if (!empty($request->keyword)) {
            $jobs = $jobs->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                $query->orWhere('keywords', 'like', '%' . $request->keyword . '%');
            });
        }

        // Tìm kiếm bằng địa điểm
        if (!empty($request->location)) {
            $jobs = $jobs->where('location', $request->location);
        }

        // Tìm kiếm bằng loại
        if (!empty($request->category)) {
            $jobs = $jobs->where('category_id', $request->category);
        }


        $jobTypeArray = [];
        // Tìm kiếm bằng thời gian
        if (!empty($request->jobType)) {
            // Tìm kiếm nhiều thời gian cùng 1 lúc
            $jobTypeArray = explode(',', $request->jobType); // chuyển một chuỗi sang một mảng dựa trên các ký tự phân cách. giá trị 1 là dấu phân cách, giá trị 2 là chuỗi cần tách mảng
            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }

        // Tìm kiếm bằng kinh nghiệm làm
        if (!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }

        $jobs = $jobs->with(['jobType', 'category']);

        if($request->sort == '0'){
            $jobs = $jobs->orderBy('created_at', 'ASC');
        }else{
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }
        $jobs = $jobs->paginate(3);

        return view('front.jobs', [
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'jobs' => $jobs,
            'jobTypeArray' => $jobTypeArray
        ]);
    }

    // Hàm này trả về chi tiết công việc
    public function detail($id){

        $job = Job::where([
            'id'=>$id,
            'status' => 1
        ])
        ->with(['jobType','category'])
        ->first();

        if($job == null) {
            abort(404);
        }

        $count = 0;

        if(Auth::user()){
            $count = SavedJob::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id
            ])->count();
        }

        // Hiển thị những người đã apply công việc
        $applications = JobApplication::where('job_id', $id)->with('user')->get();

        return view('front.jobDetail',[
            'job' => $job,
            'count' => $count,
            'applications' => $applications
        ]);
    }

    // hàm trả về chức năng apply công việc
    public function applyJob(Request $request){
        $id = $request->id;

        $job = Job::where('id',$id)->first();

        // Nếu không tìm thấy bài đăng tuyển việc
        if($job == null){
            $message = 'Job does not exist';
            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        // Bạn sẽ không thể apply công việc bạn đã đăng lên
        $employer_id = $job->user_id;

        if($employer_id == Auth::user()->id){
            $message = 'You can not apply on your own jobs';
            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        // Không thể apply 1 công việc 2 lần
        $jobApplicationCount = JobApplication::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        if($jobApplicationCount>0){
            $message = 'You already applied on this job.';
            session()->flash('error', $message);
            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        // Apply job

        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();

        // Gửi thông báo về Email của người dùng
        $employer = User::where('id', $employer_id)->first();
        $mailData = [
            'employer' => $employer,
            'user' => Auth::user(),
            'job' => $job,

        ];
        Mail::to($employer->email)->send(new JobNobtificationEmail($mailData));

        $message = 'You have successfully applied.';

        session()->flash('success', $message);
        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function saveJob(Request $request){
        $id = $request->id;

        $job = Job::find($id);

        if($job == null){

            session()->flash('error','Job not found.');

            return response()->json([
                'status' => false,
            ]);
        }

        // kiểm tra nếu người dùng đã lưu job này rồi
        $count = SavedJob::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        if($count>0){

            session()->flash('error','You already saved this job.');

            return response()->json([
                'status' => false,
            ]);
        }

        $saveJob = new SavedJob();
        $saveJob->job_id = $id;
        $saveJob->user_id = Auth::user()->id;
        $saveJob->save();

        session()->flash('success','You have successfully saved the job.');

        return response()->json([
            'status' => true,
        ]);

    }
}
