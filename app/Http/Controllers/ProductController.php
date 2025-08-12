<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Validator;
use App\Models\AttendanceEmployee;
use App\Models\Utility;
use App\Models\DealTask;
use App\Models\Branch;
use App\Models\Region;
use App\Models\User;
use App\Models\LogActivity;
use App\Models\Stage;
use App\Models\University;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\DealApplication;
use App\Models\TaskDiscussion;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;








class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function branchDetail()
{
    // Fetch the branch details by ID

    $id =   $employeeId = \Auth::user()->branch_id;
    $Branch = Branch::findOrFail($id);

    // Get the list of regions with their names and ids
    $Regions = Region::get()->pluck('name', 'id')->toArray();

    // Get the list of managers with their names and ids
    $Manager = User::get()->pluck('name', 'id')->toArray();

    // Return the details as a JSON response
    return response()->json([
        'status' => 'success',
        'branch' => $Branch,
        //'regions' => $Regions,
       // 'managers' => $Manager,
    ]);
}

     public function tasklist(Request $request)
{
    // Set pagination variables
    $start = 0;
    $num_results_on_page = 20;

    if ($request->has('page')) {
        $page = $request->input('page');
        $start = ($page - 1) * $num_results_on_page;
    }

        // Build the query to retrieve tasks
        $tasks = DealTask::select('deal_tasks.id','deal_tasks.name', 'deal_tasks.brand_id', 'deal_tasks.id', 'deal_tasks.due_date', 'deal_tasks.status', 'deal_tasks.description', 'deal_tasks.assigned_to','brand.name as brandName')
            ->join('users', 'users.id', '=', 'deal_tasks.assigned_to')
            ->join('users as brand', 'brand.id', '=', 'deal_tasks.brand_id');


            $tasks->where('deal_tasks.assigned_to', \Auth::user()->id);


        // Get total records
        $total_records = $tasks->count();

        // Fetch tasks with pagination
        $tasks = $tasks->orderBy('deal_tasks.created_at', 'DESC')->skip($start)->take($num_results_on_page)->get();

        // Return the tasks data as JSON response
        return response()->json([
            'status' => 'success',
            'tasks' => $tasks,
            'total_records' => $total_records,
        ]);

}
public function attendance(Request $request)
{

    $employee = Auth::user();
    $employeeId = $employee->id;
    $branchId = $employee->branch_id;

    $branch = Branch::find($branchId);
    $timezone = $branch->timezone ?? 'Asia/Karachi';

    if ($branch->timezone=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch timezone is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch timezone is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
    if ($branch->start_time=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch shift start time  is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch shift start time is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
    if ($branch->end_time=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch shift end time is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch shift end time  is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
   // date_default_timezone_set( $timezone);
    // Validate the request and handle validation errors
    $validator = \Validator::make($request->all(), [
        'longitude' => 'required|numeric',
        'latitude' => 'required|numeric',
        'clockInStatus' => 'required|numeric',
    ]);

    // If validation fails, return a JSON response with errors
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);  // 422 Unprocessable Entity status
    }

    $employeeId = !empty(\Auth::user()->id) ? \Auth::user()->id : 0;
    $employee = !empty(\Auth::user()) ? \Auth::user() : 0;
    $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)
        ->where('date', date('Y-m-d'))
        ->first();

    if (empty($todayAttendance)) {

        $startTime = Utility::getValByName('company_start_time');
        $endTime = Utility::getValByName('company_end_time');

        $attendance = AttendanceEmployee::orderBy('id', 'desc')
            ->where('employee_id', '=', $employeeId)
            ->where('clock_out', '=', '00:00:00')
            ->first();

        if ($attendance != null) {
            $attendance = AttendanceEmployee::find($attendance->id);
            $attendance->clock_out = $endTime;
            $attendance->save();
        }

        $date = date("Y-m-d");
        $time = date("H:i:s");

        // Calculate late time
        $totalLateSeconds = time() - strtotime($date . $startTime);
        $hours = floor($totalLateSeconds / 3600);
        $mins = floor($totalLateSeconds / 60 % 60);
        $secs = floor($totalLateSeconds % 60);
        $late = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        $checkDb = AttendanceEmployee::where('employee_id', '=', \Auth::user()->id)->get()->toArray();

        if (empty($checkDb)) {
            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->shift_start = $branch->start_time;
            $employeeAttendance->shift_end = $branch->end_time; 
            $employeeAttendance->brand_id = $employee->brand_id;
            $employeeAttendance->region_id = $employee->region_id;
            $employeeAttendance->branch_id = $employee->branch_id;
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->created_by = \Auth::user()->id;

            // Save longitude and latitude
            $employeeAttendance->longitude = $request->longitude;
            $employeeAttendance->latitude = $request->latitude;
            $employeeAttendance->clockInStatus = $request->clockInStatus;

            $employeeAttendance->save();

            return response()->json([
                'success' => true,
                'message' => __('Employee Successfully Clock In.')
            ], 200);
        }

        foreach ($checkDb as $check) {
            $employeeAttendance = new AttendanceEmployee();
            $employeeAttendance->shift_start = $branch->start_time;
            $employeeAttendance->shift_end = $branch->end_time; 
            $employeeAttendance->brand_id = $employee->brand_id;
            $employeeAttendance->region_id = $employee->region_id;
            $employeeAttendance->branch_id = $employee->branch_id;
            $employeeAttendance->employee_id = $employeeId;
            $employeeAttendance->date = $date;
            $employeeAttendance->status = 'Present';
            $employeeAttendance->clock_in = $time;
            $employeeAttendance->clock_out = '00:00:00';
            $employeeAttendance->late = $late;
            $employeeAttendance->early_leaving = '00:00:00';
            $employeeAttendance->overtime = '00:00:00';
            $employeeAttendance->total_rest = '00:00:00';
            $employeeAttendance->created_by = \Auth::user()->id;

            // Save longitude and latitude
            $employeeAttendance->longitude = $request->longitude;
            $employeeAttendance->latitude = $request->latitude;
            $employeeAttendance->clockInStatus = $request->clockInStatus;

            $employeeAttendance->save();

            return response()->json([
                'success' => true,
                'message' => __('Employee Successfully Clock In.')
            ], 200);
        }
    } else {
        return response()->json([
            'success' => false,
            'message' => __('Employee are not allowed multiple time clock in & clock for every day.')
        ], 400);
    }
}

public function getCurrentDayAttendance(Request $request)
{


    $employee = Auth::user();
    $employeeId = $employee->id;
    $branchId = $employee->branch_id;

    $branch = Branch::find($branchId);
    $timezone = $branch->timezone ?? 'Asia/Karachi';

    if ($branch->timezone=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch timezone is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch timezone is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
    //date_default_timezone_set( $timezone);
    // Get the logged-in employee ID
    $employeeId = \Auth::user()->id;

    // Get today's date
    $today = date('Y-m-d');

    // Fetch the current day's attendance for the employee
    $todayAttendance = AttendanceEmployee::where('employee_id', $employeeId)
        ->where('date', $today)
        ->first();

    // If attendance is found, return it
    if ($todayAttendance) {
        return response()->json([
            'success' => true,
            'attendance' => $todayAttendance
        ], 200);
    }

    // If no attendance found, return an error response
    return response()->json([
        'success' => false,
        'message' => __('No attendance record found for today.')
    ], 404);
}
public function clockOut(Request $request)
{

    $employee = Auth::user();
    $employeeId = $employee->id;
    $branchId = $employee->branch_id;

    $branch = Branch::find($branchId);
    $timezone = $branch->timezone ?? 'Asia/Karachi';

    if ($branch->timezone=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch timezone is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch timezone is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
    //date_default_timezone_set( $timezone);
    // Validate the request and handle validation errors

    $validator = \Validator::make($request->all(), [
        'longitude' => 'required|numeric',
        'latitude' => 'required|numeric',
        'clockOutStatus' => 'required|numeric',
    ]);

    // If validation fails, return a JSON response with errors
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);  // 422 Unprocessable Entity status
    }

    $employeeId = !empty(\Auth::user()->id) ? \Auth::user()->id : 0;
    $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)
        ->where('date', date('Y-m-d'))
        ->first();

    // Check if the employee has clocked in today
    if (empty($todayAttendance)) {
        return response()->json([
            'success' => false,
            'message' => __('No attendance record found for today. Please clock in first.')
        ], 404);
    }

    // Check if the employee has already clocked out
    if ($todayAttendance->clock_out != '00:00:00') {
        return response()->json([
            'success' => false,
            'message' => __('Employee has already clocked out.')
        ], 400);
    }

    // Set the clock_out time to the current time
    $todayAttendance->clock_out = date("H:i:s");

    // Calculate total hours worked (time difference between clock_in and clock_out)
    $clockInTime = strtotime($todayAttendance->clock_in);
    $clockOutTime = strtotime($todayAttendance->clock_out);
    $totalWorkedSeconds = $clockOutTime - $clockInTime;
    $totalWorkedHours = $totalWorkedSeconds / 3600;  // convert seconds to hours

            $id         =   $employeeId = \Auth::user()->branch_id;
            $Branch     =   Branch::findOrFail($id);


            //dd($Branch['timezone']);

            // Get the current time in both timezones


    // If worked hours are less than 8, validate the earlyCheckOutReason
    if ($totalWorkedHours < $Branch['shift_time']) {
        $validator = \Validator::make($request->all(), [
            'earlyCheckOutReason' => 'required|string',
        ]);

        // If validation fails, return a JSON response with errors
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Early Checkout is not allowed without reason',
                'errors' => $validator->errors()
            ], 422);  // 422 Unprocessable Entity status
        }

        // Save early check-out reason to the attendance record
        $todayAttendance->earlyCheckOutReason = $request->earlyCheckOutReason;
    }

    $hours = floor($totalWorkedSeconds / 3600);
    $mins = floor(($totalWorkedSeconds / 60) % 60);
    $secs = $totalWorkedSeconds % 60;
    $todayAttendance->overtime = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

    // Update the longitude and latitude during clock out
    $todayAttendance->longitude_out = $request->longitude;
    $todayAttendance->latitude_out = $request->latitude;
    $todayAttendance->clockOutStatus = $request->clockOutStatus;

    // Save the updated attendance record
    $todayAttendance->save();

    // Return a success response
    return response()->json([
        'success' => true,
        'message' => __('Employee Successfully Clocked Out.'),
        'clock_out_time' => $todayAttendance->clock_out
    ], 200);
}

public function viewAttendance(Request $request)
{


    $employee = Auth::user();
    $employeeId = $employee->id;
    $branchId = $employee->branch_id;

    $branch = Branch::find($branchId);
    $timezone = $branch->timezone ?? 'Asia/Karachi';

    if ($branch->timezone=='') {
        return response()->json([
            'success' => false,
            'message' => 'The branch timezone is not configured. Please contact the administrator for assistance.',
            'errors' => 'The branch timezone is not configured. Please contact the administrator for assistance.'
        ], 422);  // 422 Unprocessable Entity status
    }
    //date_default_timezone_set( $timezone);

    $startDate = date('Y-m-d', strtotime($request->input('start_date', now()->startOfMonth())));
    $endDate = date('Y-m-d', strtotime($request->input('end_date', now())));

    $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
    $today = now()->format('Y-m-d');

    $attendanceRecords = AttendanceEmployee::where('employee_id', $employeeId)
        ->whereBetween('date', [$startDate, $endDate])
        ->get();

    $branch = Branch::find($branchId);
    $timezone = $branch->timezone ?? 'Asia/Karachi';

    $timeInBranch = Carbon::now('UTC');
    $timeInUTC = Carbon::now('UTC');
    $timezoneDifference = ($timeInBranch->getOffset() - $timeInUTC->getOffset()) / 3600;

    $attendanceData = [];

    foreach ($period as $date) {
        $formattedDate = $date->format('Y-m-d');
        $dayOfWeek = $date->format('l');

        if ($formattedDate > $today) continue;

        $attendance = $attendanceRecords->firstWhere('date', $formattedDate);

        if (!$attendance) {
            $attendanceData[] = [
                'date' => $formattedDate,
                'status' => in_array($dayOfWeek, [ 'Sunday']) ? 'Holiday' : 'Absent',
                'clock_in' => null,
                'clock_out' => null,
                'late' => null,
                'early_punch_out' => null,
                'hours_worked' => null,
                'early_check_out_reason' => null,
            ];
        } else {

              $shift_start = Carbon::parse($attendance->shift_start); 
               $shift_end = Carbon::parse($attendance->shift_end);
            


            $hourstotal = $shift_end->diff($shift_start);
            $hourstotalFormatted = $hourstotal->format('%H:%I:%S');

            $clockIn = Carbon::parse($attendance->clock_in);
            if($attendance->clock_out !== '00:00:00'){
               $clockOut = Carbon::parse($attendance->clock_in);
            }else{
                $clockOut = Carbon::parse($attendance->clock_out);
            }


            $hoursWorked = $clockOut->diff($clockIn);
            $hoursWorkedFormatted = $hoursWorked->format('%H:%I:%S');

            $earlyPunchOut = $attendance->earlyCheckOutReason != NULL ? 'Yes' : 'No'; 

            if ($attendance->status == 'Absent') {
                $attendance->clock_out = null;
                $attendance->clock_in = null;
            }
            if ($attendance->status == 'Leave') {
                $attendance->clock_out = null;
                $attendance->clock_in = null;
            }

            $attendanceData[] = [
                'date' =>               $formattedDate,
                'status' =>             $attendance->status,
                'clock_in' =>           $attendance->clock_in,
                'clock_out' =>          $attendance->clock_out,
                'late' =>               $attendance->late,
                'early_punch_out' =>    $earlyPunchOut,
                'hours_worked' =>       $hoursWorkedFormatted,
                'minimumTotalHours' =>       $hourstotalFormatted,
                'early_check_out_reason' => $attendance->earlyCheckOutReason ?? null,
            ];
        }
    }

    return response()->json([
        'success' => true,
        'data' => $attendanceData,
    ], 200);
}



    public function index()
    {
        $products = Product::latest()->get();

        if (is_null($products->first())) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No product found!',
            ], 200);
        }

        $response = [
            'status' => 'success',
            'message' => 'Products are retrieved successfully.',
            'data' => $products,
        ];

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:250',
            'description' => 'required|string|'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $product = Product::create($request->all());

        $response = [
            'status' => 'success',
            'message' => 'Product is added successfully.',
            'data' => $product,
        ];

        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (is_null($product)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Product is not found!',
            ], 200);
        }

        $response = [
            'status' => 'success',
            'message' => 'Product is retrieved successfully.',
            'data' => $product,
        ];

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required'
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error!',
                'data' => $validate->errors(),
            ], 403);
        }

        $product = Product::find($id);

        if (is_null($product)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Product is not found!',
            ], 200);
        }

        $product->update($request->all());

        $response = [
            'status' => 'success',
            'message' => 'Product is updated successfully.',
            'data' => $product,
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (is_null($product)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Product is not found!',
            ], 200);
        }

        Product::destroy($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Product is deleted successfully.'
            ], 200);
    }

    /**
     * Search by a product name
     *
     * @param  str  $name
     * @return \Illuminate\Http\Response
     */
    public function search($name)
    {
        $products = Product::where('name', 'like', '%'.$name.'%')
            ->latest()->get();

        if (is_null($products->first())) {
            return response()->json([
                'status' => 'failed',
                'message' => 'No product found!',
            ], 200);
        }

        $response = [
            'status' => 'success',
            'message' => 'Products are retrieved successfully.',
            'data' => $products,
        ];

        return response()->json($response, 200);
    }


    public function deleteAttendanceRecord(Request $request)
{

    // Get the authenticated employee ID
    $employeeId = !empty(\Auth::user()->id) ? \Auth::user()->id : 0;

    // Find the attendance record for today
    $todayAttendance = AttendanceEmployee::where('employee_id', '=', $employeeId)
        ->where('date', date('Y-m-d'))
        ->first();

    // Check if the employee has clocked in today
    if (empty($todayAttendance)) {
        return response()->json([
            'success' => false,
            'message' => __('No attendance record found for today.')
        ], 404);
    }



    // Delete the attendance record for today
    $todayAttendance->delete();

    // Return a success response
    return response()->json([
        'success' => true,
        'message' => __('Attendance record successfully deleted.')
    ], 200);
}
public function createtask(Request $request)
{
    $usr = \Auth::user();
    $employeeId = !empty(\Auth::user()->id) ? \Auth::user()->id : 0;

    // // Check if the user has permission to create a task
    // if ($usr->can('create task')) {

        // Validation rules and messages
        $rules = [
            'task_name' => 'required',
            'brand_id' => 'required|integer|min:1',
            'region_id' => 'required|integer|min:1',
            'branch_id' => 'required|integer|min:1',
            'assigned_to' => 'required|integer|min:1',
            'assign_type' => 'required',
            'due_date' => 'required',
            'start_date' => 'required',
        ];

        $messages = [
            'brand_id.min' => 'The brand id must be required',
            'region_id.min' => 'The Region id must be required',
            'branch_id.min' => 'The branch id must be required',
            'assigned_to.min' => 'The Assigned id must be required',
        ];

        // Validate the request
        $validator = \Validator::make($request->all(), $rules, $messages);

        // Return validation error if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Create a new DealTask
        $dealTask = new DealTask();
        $dealTask->deal_id = $request->related_to ?? 0;
        $dealTask->related_to = $request->related_to ?? 0;
        $dealTask->related_type = $request->related_type ?? 'task';
        $dealTask->name = $request->task_name;
        $dealTask->branch_id = $request->branch_id;
        $dealTask->region_id = $request->region_id;
        $dealTask->brand_id =  $request->brand_id;
        $dealTask->created_by = $employeeId;
        $dealTask->assigned_to = $request->assigned_to;
        $dealTask->assigned_type = $request->assign_type;
        $dealTask->due_date = $request->due_date ?? '';
        $dealTask->start_date = $request->start_date;
        $dealTask->date = $request->start_date;
        $dealTask->status = 0;
        $dealTask->remainder_date = $request->remainder_date;
        $dealTask->description = $request->description;
        $dealTask->visibility = $request->visibility;
        $dealTask->priority = 1;
        $dealTask->time = $request->remainder_time ?? '';
        $dealTask->save();

        // Add log activity (optional)
        $remarks = [
            'title' => 'Task Created',
            'message' => 'Task Created successfully'
        ];

        $related_id = '';
        $related_type = '';

        if (isset($dealTask->deal_id) && in_array($dealTask->related_type, ['organization', 'lead', 'deal', 'application', 'toolkit', 'agency', 'task'])) {
            $related_id = $dealTask->deal_id;
            $related_type = $dealTask->related_type;
        }

        $logData = [
            'type' => 'info',
            'note' => json_encode($remarks),
            'module_id' => $related_type == 'task' ? $dealTask->id : $related_id,
            'module_type' => $related_type,
            'notification_type' => 'Task created'
        ];
        $this->addLogActivity($logData);

        // Notification data (optional)
        $html = '<p class="mb-0"><span class="fw-bold">
               <span style="cursor:pointer;font-weight:bold;color:#1770b4 !important" onclick="openSidebar(\'/get-task-detail?task_id=' . $dealTask->id . '\')" data-task-id="' . $dealTask->id . '">' . $dealTask->name . '</span></span>
               Created By <span style="cursor:pointer;font-weight:bold;color:#1770b4 !important" onclick="openSidebar(\'/users/' . \Auth::id() . '/user_detail\')">' . User::find(\Auth::id())->name . '</span></p>';

        $notificationData = [
            'type' => 'Tasks',
            'data_type' => 'Task_Created',
            'sender_id' => $dealTask->created_by,
            'receiver_id' => $dealTask->assigned_to,
            'data' => $html,
            'is_read' => 0,
            'related_id' => $dealTask->id,
            'created_by' => \Auth::id(),
            'created_at' => \Carbon\Carbon::now()
        ];

        // Send notification if the creator is not the assigned user
        if ($dealTask->created_by !== (int)$dealTask->assigned_to) {
            $this->addNotifications($notificationData);
        }

        // Return success response
        return response()->json([
            'status' => 'success',
            'task_id' => $dealTask->id,
            'message' => __('Task successfully created!')
        ], 201);

    // } else {
    //     // Return error response if the user does not have permission
    //     return response()->json([
    //         'status' => 'error',
    //         'message' => __('Permission Denied.')
    //     ], 403);
    // }
}

function addNotifications($data = [])
    {
       \DB::table('notifications')->insert($data);
    }

 function addLogActivity($data = [])
    {
        $new_log = new LogActivity();
        $new_log->type = $data['type'];
        $new_log->start_date = date('Y-m-d');
        $new_log->time = date('H:i:s');
        $new_log->note = $data['note'];
        $new_log->module_type = isset($data['module_type']) ? $data['module_type'] : '';
        $new_log->module_id = isset($data['module_id']) ? $data['module_id'] : 0;
        $new_log->created_by = \Auth::user()->id;
        $new_log->save();



        ///////////////////Creating Notification
        $msg = '';
        if(strtolower($data['notification_type']) == 'application stage update'){
            $msg = 'Application stage updated.';
        }else if(strtolower($data['notification_type']) == 'lead updated'){
            $msg = 'Lead updated.';
        }else if(strtolower($data['module_type']) == 'application'){
            $msg = 'New application created.';
        }else if(strtolower($data['notification_type']) == 'University Created'){
            $msg = 'New University Created.';
        }else if(strtolower($data['notification_type']) == 'University Updated'){
            $msg = 'University Updated.';
        }else if(strtolower($data['notification_type']) == 'University Deleted'){
            $msg = 'University Deleted.';
        }else if(strtolower($data['notification_type']) == 'Deal Created'){
            $msg = 'Deal Created.';
        }else if(strtolower($data['notification_type']) == 'Deal Updated'){
            $msg = 'Deal Updated.';
        }else if(strtolower($data['notification_type']) == 'Lead Updated'){
            $msg = 'Lead Updated.';
        }else if(strtolower($data['notification_type']) == 'Deal Notes Created'){
            $msg = 'Deal Notes Created.';
        }else if(strtolower($data['notification_type']) == 'Task Created'){
            $msg = 'Task Created.';
        }else if(strtolower($data['notification_type']) == 'Task Updated'){
            $msg = 'Task Updated.';
        }else if(strtolower($data['notification_type']) == 'Stage Updated'){
            $msg = 'Stage Updated.';
        }else if(strtolower($data['notification_type']) == 'Deal Stage Updated'){
            $msg = 'Deal Stage Updated.';
        }else if(strtolower($data['notification_type']) == 'Organization Created'){
            $msg = 'Organization Created.';
        }else if(strtolower($data['notification_type']) == 'Organization Updated'){
            $msg = 'Organization Updated.';
        }else if(strtolower($data['notification_type']) == 'Lead Notes Updated'){
            $msg = 'Lead Notes Updated.';
        }else if(strtolower($data['notification_type']) == 'Notes created'){
            $msg = 'Notes created.';
        }else if(strtolower($data['notification_type']) == 'Task Deleted'){
            $msg = 'Task Deleted.';
        }else if(strtolower($data['notification_type']) == 'Lead Created'){
            $msg = 'Lead Created.';
        }else if(strtolower($data['notification_type']) == 'Lead Updated'){
            $msg = 'Lead Updated.';
        }else if(strtolower($data['notification_type']) == 'Lead Deleted'){
            $msg = 'Lead Deleted.';
        }else if(strtolower($data['notification_type']) == 'Discussion created'){
            $msg = 'Discussion created.';
        }else if(strtolower($data['notification_type']) == 'Drive link added'){
            $msg = 'Drive link added.';
        }else if(strtolower($data['notification_type']) == 'Lead Notes Updated'){
            $msg = 'Lead Notes Updated.';
        }else if(strtolower($data['notification_type']) == 'Lead Notes Deleted'){
            $msg = 'Lead Notes Deleted.';
        }else if(strtolower($data['notification_type']) == 'Lead Converted'){
            $msg = 'Lead Converted.';
        }else if(strtolower($data['notification_type']) == 'Application Notes Created'){
            $msg = 'Application Notes Created.';
        }else if(strtolower($data['notification_type']) == 'Application Notes Updated'){
            $msg = 'Application Notes Updated.';
        }else if(strtolower($data['notification_type']) == 'Applicaiton Notes Deleted'){
            $msg = 'Applicaiton Notes Deleted.';
        }else{
            $msg = 'New record created';
        }



        // $notification = new Notification;
        // $notification->user_id = \Auth::user()->id;
        // $notification->type = 'push notificationn';
        // $notification->data = $msg;
        // $notification->is_read = 0;

        // $notification->save();
       // event(new NewNotification($notification));
    }

    public function getTaskDetails(Request $request)
{
    // Validate the task_id input
    $validator = \Validator::make($request->all(), [
        'task_id' => 'required|integer|exists:deal_tasks,id'
    ]);

    // Return validation error if task_id is not valid
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first()
        ], 400);
    }

    try {
        // Fetch the task by ID
        $taskId = $request->task_id;
        $task = DealTask::findOrFail($taskId);

        // Fetch related data
        $branches = Branch::pluck('name', 'id')->toArray();
        $users = User::pluck('name', 'id')->toArray();
        $stages = Stage::pluck('name', 'id')->toArray();
        $universities = University::pluck('name', 'id')->toArray();
        $organizations = User::where('type', 'organization')->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $leads = Lead::where('branch_id', $task->branch_id)->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $deals = Deal::where('branch_id', $task->branch_id)->orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $toolkits = University::orderBy('name', 'ASC')->pluck('name', 'id')->toArray();
        $applications = DealApplication::join('deals', 'deals.id', '=', 'deal_applications.deal_id')
            ->where('deals.branch_id', $task->branch_id)
            ->orderBy('deal_applications.name', 'ASC')
            ->pluck('deal_applications.application_key', 'deal_applications.id')
            ->toArray();

        // Fetch task discussions
        $discussions = TaskDiscussion::select('task_discussions.id', 'task_discussions.comment', 'task_discussions.created_at', 'users.name', 'users.avatar')
            ->join('users', 'task_discussions.created_by', 'users.id')
            ->where('task_discussions.task_id', $taskId)
            ->orderBy('task_discussions.created_at', 'DESC')
            ->get()
            ->toArray();

        // Fetch log activities for the task
        $log_activities = $this->getLogActivity($taskId, 'task');

        // Fetch related agency if applicable
        $Agency = \App\Models\Agency::find($task->related_to);

        // Return data as JSON response
        return response()->json([
            'status' => 'success',
            'task' => $task,
            'branches' => $branches,
            'users' => $users,
            'stages' => $stages,
            'universities' => $universities,
            'organizations' => $organizations,
            'leads' => $leads,
            'deals' => $deals,
            'toolkits' => $toolkits,
            'applications' => $applications,
            'discussions' => $discussions,
            'log_activities' => $log_activities,
            'agency' => $Agency
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while fetching the task details.'
        ], 500);
    }
}

 function getLogActivity($id, $type)
    {
        return LogActivity::where('module_id', $id)->where('module_type', $type)->orderBy('created_at', 'desc')->get();
    }

    public function getLeaves(Request $request)
{


        // Build query for leaves without pagination, filters, and extra data
        $query = Leave::query();



            $user = \Auth::user(); 
            $leaves =Leave::where('employee_id', $user->id)
                    ->where('start_date', '>=', now()->startOfDay()) // Only leaves that ended before today
                    ->orderBy('start_date', 'desc')->get();
           // $leaves = array_reverse($leaves);


        // Return response with only leaves data
        return response()->json([
            'leaves' =>$leaves ,
        ]);

}
    public function getLeavesHistory(Request $request)
{


        // Build query for leaves without pagination, filters, and extra data
        $query = Leave::query();



            $user = \Auth::user(); 
            
           // $leaves = $query->where('employee_id', $user->id)->where('start_date','<', now())->orderBy('start_date', 'desc')->get();

            $leaves =Leave::where('employee_id', $user->id)
                    ->where('end_date', '<', now()->startOfDay()) // Only leaves that ended before today
                    ->orderBy('start_date', 'desc')->get();
           // $leaves = array_reverse($leaves);


        // Return response with only leaves data
        return response()->json([
            'leaves' =>$leaves ,
        ]);

}  
 public function deleteLeave(Request $request)
{
    try {
        $user = auth()->user();
        
        // Validate the request
        $request->validate([
            'leaveID' => 'required|integer|exists:leaves,id'
        ]);

        // Find the specific leave
        $leave = Leave::where('employee_id', $user->id)
                    ->where('id', $request->leaveID)
                    ->first();

        // Check if leave exists
        if (!$leave) {
            return response()->json([
                'status' => 'error',
                'message' => 'Leave not found or not authorized to delete.'
            ], 404);
        }

        // Check if leave status is Pending
        if ($leave->status != 'Pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'You can only delete leaves with Pending status.'
            ], 400);
        }

        // Delete the leave
        if ($leave->delete()) {

             // Log the deletion activity
        $logData = [
            'type' => 'warning',
            'note' => json_encode([
               'title' => $leave->user->name. ' leave  deleted',
                'message' => $leave->user->name. ' leave  deleted'
            ]),
            'module_id' => $leave->id,
            'module_type' => 'leave',
            'notification_type' => 'Leave deleted'
        ];
        $this->addLogActivity($logData);
  // Log the deletion activity
        $logData = [
            'type' => 'warning',
            'note' => json_encode([
               'title' => $leave->user->name. ' leave  deleted',
                'message' => $leave->user->name. ' leave  deleted'
            ]),
              'module_id' => $leave->employee_id,
            'module_type' => 'employeeprofile',
            'notification_type' => 'Leave Deleted'
        ];
        $this->addLogActivity($logData);
            return response()->json([
                'status' => 'success',
                'message' => 'Leave successfully deleted.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to delete leave.'
        ], 500);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while processing your request.',
            'error' => env('APP_DEBUG') ? $e->getMessage() : null
        ], 500);
    }
}

public function createLeave(Request $request)
{
    // Validate incoming request
    $validator = \Validator::make(
        $request->all(),
        [
            'brand_id' => 'required',
            'region_id' => 'required',
            'branch_id' => 'required',
            'leave_type_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'leave_reason' => 'required',
            'remark' => 'required',
        ]
    );

    if ($validator->fails()) {
        // Return validation error response
        return response()->json(['error' => $validator->errors()->first()], 400);
    }

    $user = \Auth::user();

    if(!$user){
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }
 

           // dd( $user);

    // Create new Leave instance
    $leave = new Leave();

    $leave->employee_id = $user->id;
    $leave->brand_id = $request->brand_id;
    $leave->region_id = $request->region_id;
    $leave->branch_id = $request->branch_id;
    $leave->leave_type_id = $request->leave_type_id;
    $leave->applied_on = date('Y-m-d');
    $leave->start_date = $request->start_date;
    $leave->end_date = $request->end_date;
    $leave->total_leave_days = 0;  // This can be calculated later if needed
    $leave->leave_reason = $request->leave_reason;
    $leave->remark = $request->remark;
    $leave->status = 'Pending';
    $leave->created_by = \Auth::id();

    // Save the leave record
    $leave->save();

   $this->addLogActivity([
            'type' => 'success',
              'note' => json_encode([
                'title' => $leave->user->name. ' leave  created',
                'message' => $leave->user->name. 'leave  created'
            ]),
            'module_id' => $leave->id,
            'module_type' => 'leave',
            'notification_type' => 'leave created',
        ]);
        $this->addLogActivity([
                'type' => 'success',
                'note' => json_encode([
                    'title' => $leave->user->name. ' leave  created',
                    'message' => $leave->user->name. 'leave  created'
                ]),
                'module_id' => $leave->employee_id,
                'module_type' => 'employeeprofile',
                'notification_type' => 'leave created',
            ]);

    // Return success response
    return response()->json(['success' => __('Leave successfully created.')], 201);
}

public function userDetail(Request $request)
{
    try {

        // Find the user by ID or throw an exception if not found te sdd
        $user = User::findOrFail($request->userID);



        // Return a JSON response with the user details and the array of users
        return response()->json([
            'status' => 'success',
            'user' => $user,
        ], 200);

    } catch (\Exception $e) {

        // Handle the exception and return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }
}


public function regionDetail(Request $request)
{
    try {

        // Find the user by ID or throw an exception if not found te sdd
        $region = Region::findOrFail($request->regionID);



        // Return a JSON response with the user details and the array of users
        return response()->json([
            'status' => 'success',
            'region' => $region,
        ], 200);

    } catch (\Exception $e) {

        // Handle the exception and return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'region not found'
        ], 404);
    }
}

public function branchDetailByID(Request $request)
{
    try {

        // Find the user by ID or throw an exception if not found te sdd
        $region = Branch::findOrFail($request->branchID);



        // Return a JSON response with the user details and the array of users
        return response()->json([
            'status' => 'success',
            'branch' => $region,
        ], 200);

    } catch (\Exception $e) {

        // Handle the exception and return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'branch not found'
        ], 404);
    }
}
public function LeaveTypeDetail(Request $request)
{
    try {

        // Find the user by ID or throw an exception if not found
        $leaveType = LeaveType::get();



        // Return a JSON response with the user details and the array of users
        return response()->json([
            'status' => 'success',
            'leaveType' => $leaveType,
        ], 200);

    } catch (\Exception $e) {

        // Handle the exception and return an error response
        return response()->json([
            'status' => 'error',
            'message' => 'leave type not found'
        ], 404);
    }
}

public function TaskStatusChange(Request $request)
{
    // Validate the request input
    $validated = $request->validate([
        'id' => 'required|integer',
    ]);

    // Retrieve the task ID
    $id = $validated['id'];

    // Find the task by its ID
    $dealTask = DealTask::findOrFail($id);

    // Check if the authenticated user is the assigned user but not the creator
    // if ($dealTask->created_by !== (int) $dealTask->assigned_to && \Auth::id() == (int) $dealTask->assigned_to) {

        // Create notification content
        $html = '<p class="mb-0">
            <span class="fw-bold">
                <span style="cursor:pointer;font-weight:bold;color:#1770b4 !important"
                    onclick="openSidebar(\'/get-task-detail?task_id=' . $dealTask->id . '\')"
                    data-task-id="' . $dealTask->id . '">' . $dealTask->name . '</span>
            </span>
            Task Completed By <span style="cursor:pointer;font-weight:bold;color:#1770b4 !important"
                onclick="openSidebar(\'/users/' . \Auth::id() . '/user_detail\')">
                ' . User::find(\Auth::id())->name ?? '' . ' </span>
        </p>';

        // Add notification for the task creator
        $this->addNotifications([
            'type' => 'Tasks',
            'data_type' => 'Task_Completed',
            'sender_id' => \Auth::id(),
            'receiver_id' => $dealTask->created_by,
            'data' => $html,
            'is_read' => 0,
            'related_id' => $dealTask->id,
            'created_by' => \Auth::id(),
            'created_at' => \Carbon\Carbon::now()
        ]);

        // Update the task status to 'completed'
        $dealTask->update(['status' => '1']);

        // Return success response with 200 status code
        return response()->json([
            'status' => 'success',
            'message' => 'Task status updated successfully'
        ], 200);

    // } else {
    //     // If the user is unauthorized, return 401 status code
    //     return response()->json([
    //         'status' => 'error',
    //         'message' => 'Unauthorized to change this task status'
    //     ], 401);
    // }
}


public function getUserProfile(Request $request)
{
    // Get the logged-in employee ID
    $user = \Auth::user();


    // $employee_docs = \DB::table('employee_documents')
    // ->where('employee_id', $user->id)
    // ->get();

    $employee_docs = \DB::table('employee_documents')
    ->where('employee_id', $user->id)
    ->get()
    ->map(function ($doc) {
        unset($doc->id, $doc->created_at, $doc->profile_picture, $doc->employee_id, $doc->created_by, $doc->updated_at);
        return $doc;
    });



    // If attendance is found, return it
    if ($user) {
        return response()->json([
            'success'       => true,
            'avtar_base_url'       => 'https://backend.convosoftserver.com/EmployeeDocument/',
            'doc_base_url'       => 'https://backend.convosoftserver.com/EmployeeDocument/',
            'employee_docs' => $employee_docs,
            'profileData'   => $user
        ], 200);
    }

    // If no attendance found, return an error response
    return response()->json([
        'success' => false,
        'message' => __('No user Found')
         ], 200);

}



public function appMeta(Request $request)
{


    $metaArray =    array(
            'isLoginForm'=>0,
            'allowedRadius'=>500
        );


        return response()->json([
            'success' => true,
            'metaData' => $metaArray
        ], 200);



}

public function editProfile(Request $request)
{
    // Get authenticated user
    $userDetail = \Auth::user();
    $user = User::find($userDetail['id']);

    // Check if user exists
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.'
        ], 404);
    }

    // Validate input data
    $this->validate(
        $request,
        [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users,email,' . $userDetail['id'],
        ]
    );

    // Update user details
    $user['name'] = $request['name'];
    $user['email'] = $request['email'];
    $user['address'] = $request['address'];
    $user['phone'] = $request['phone'];
    $user->save();


    // Return JSON response
    return response()->json([
        'success' => true,
        'message' => 'Profile successfully updated.',
        'data' => $user
    ]);
}

 public function totalSummary(Request $request)
    {
        $employeeId =   \Auth::user()->id;
        $type = $request->input('type', 'week'); // 'week' or 'month'

        if (!$employeeId) {
            return response()->json(['error' => 'employee_id is required'], 400);
        }

        $query = DB::table('attendance_employees')
            ->where('employee_id', $employeeId);

        // Filter by date range
        if ($type === 'week') {
            $query->whereBetween('date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString()
            ]);
        } elseif ($type === 'month') {
            $query->whereYear('date', now()->year)
                  ->whereMonth('date', now()->month);
        }

        $records = $query->get();

        // Status counts
        $present = $records->where('status', 'Present')->count();
        $absent = $records->where('status', 'Absent')->count();
        $leave = $records->where('status', 'Leave')->count();
        $holiday = $records->where('status', 'Holiday')->count();
       $on_time_rate = $records->filter(function ($record) {
                return $record->status === 'Present' && 
                    $record->clock_in && 
                    $record->shift_start &&
                    strtotime($record->clock_in) <= strtotime($record->shift_start);
            })->count();
        $total = $records->count();

        // Working days = All except holidays
        $workingDays = $present + $absent + $leave;

        // Total working time in seconds (only for Present days)
        $totalSeconds = 0;
        foreach ($records as $record) {
            if ($record->status === 'Present' && $record->clock_in && $record->clock_out) {
                $clockIn = strtotime($record->clock_in);
                $clockOut = strtotime($record->clock_out);
                $diff = $clockOut - $clockIn;

                if ($diff > 0) {
                    $totalSeconds += $diff;
                }
            }
        }

        // Total working hours (HH:MM)
        $totalHours = floor($totalSeconds / 3600);
        $totalMinutes = floor(($totalSeconds % 3600) / 60);
        $totalWorking = sprintf('%02d:%02d', $totalHours, $totalMinutes);

        // Average working time = totalSeconds / workingDays
        $avgSeconds = $workingDays > 0 ? intval($totalSeconds / $workingDays) : 0;
        $avgHours = floor($avgSeconds / 3600);
        $avgMinutes = floor(($avgSeconds % 3600) / 60);
        $avgWorking = sprintf('%02d:%02d', $avgHours, $avgMinutes);

        return response()->json([
            'present' => $present,
            'absent' => $absent,
            'leave' => $leave,
            'holiday' => $holiday,
            'total_days' => $total,
            'working_days' => $workingDays,
            'total_working_hours' => $totalWorking,
            'average_working_hours' => $avgWorking,
           'on_time_rate' => $present > 0 ? round(($on_time_rate / $present) * 100, 2) : 0,
        ]);
    }


}
