<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadStage extends Model
{
    protected $fillable = [
        'name',
        'pipeline_id',
        'created_by',
        'order',
    ];

    public function lead()
    {
        $start = 0;
        $num_results_on_page = env("RESULTS_ON_PAGE");
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $num_results_on_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
            $start = ($page - 1) * $num_results_on_page;
        } else {
            $num_results_on_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
        }

        // Initialize variables
        $companies = FiltersBrands();
        $brand_ids = array_keys($companies);

        // Build the leads query
        $leads_query = Lead::select('leads.*')
            ->join('lead_stages', 'leads.stage_id', '=', 'lead_stages.id')
            ->join('users', 'users.id', '=', 'leads.brand_id')
            ->join('branches', 'branches.id', '=', 'leads.branch_id')
            ->leftJoin('users as assigned_to', 'assigned_to.id', '=', 'leads.user_id');

        $leads_query->where('leads.stage_id', $this->id);
        // Apply user type-based filtering
        $userType = \Auth::user()->type;
        if (in_array($userType, ['super admin', 'Admin Team']) || \Auth::user()->can('level 1')) {
            // No additional filtering needed
        } elseif ($userType === 'company') {
            $leads_query->where('leads.brand_id', \Auth::user()->id);
        } elseif (in_array($userType, ['Project Director', 'Project Manager']) || \Auth::user()->can('level 2')) {
            $leads_query->whereIn('leads.brand_id', $brand_ids);
        } elseif (($userType === 'Region Manager' || \Auth::user()->can('level 3')) && !empty(\Auth::user()->region_id)) {
            $leads_query->where('leads.region_id', \Auth::user()->region_id);
        } elseif (($userType === 'Branch Manager' || in_array($userType, ['Admissions Officer', 'Admissions Manager', 'Marketing Officer'])) || \Auth::user()->can('level 4') && !empty(\Auth::user()->branch_id)) {
            $leads_query->where('leads.branch_id', \Auth::user()->branch_id);
        } else {
            $leads_query->where('user_id', \Auth::user()->id);
        }

        // Apply global search if provided
        if (request()->filled('ajaxCall') && request()->ajax() && request()->filled('search')) {
            $g_search = request()->input('search');
            $leads_query->where(function ($query) use ($g_search) {
                $query->where('leads.name', 'like', "%$g_search%")
                    ->orWhere('users.name', 'like', "%$g_search%")
                    ->orWhere('branches.name', 'like', "%$g_search%")
                    ->orWhere('lead_stages.name', 'like', "%$g_search%")
                    ->orWhere('leads.email', 'like', "%$g_search%")
                    ->orWhere('assigned_to.name', 'like', "%$g_search%")
                    ->orWhere('leads.phone', 'like', "%$g_search%");
            });
        }

        // Apply default filters when $_GET is empty
        if (empty($_GET)) {
            $leads_query->whereNotIn('lead_stages.name', ['Unqualified', 'Junk Lead'])
                ->where('leads.is_converted', 0);
        }

        $leads = $leads_query->groupBy('leads.id')
            ->orderBy('leads.created_at', 'desc')
            ->skip($start)
            ->take($num_results_on_page)
            ->get();

        return $leads;
    }

    public function lead_count()
    {
        $start = 0;
        $num_results_on_page = env("RESULTS_ON_PAGE");
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $num_results_on_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
            $start = ($page - 1) * $num_results_on_page;
        } else {
            $num_results_on_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
        }

        // Initialize variables
        $companies = FiltersBrands();
        $brand_ids = array_keys($companies);

        // Build the leads query
        $leads_query = Lead::select('leads.*')
            ->join('lead_stages', 'leads.stage_id', '=', 'lead_stages.id')
            ->join('users', 'users.id', '=', 'leads.brand_id')
            ->join('branches', 'branches.id', '=', 'leads.branch_id')
            ->leftJoin('users as assigned_to', 'assigned_to.id', '=', 'leads.user_id');
        $leads_query->where('leads.stage_id', $this->id);
        // Apply user type-based filtering
        $userType = \Auth::user()->type;
        if (in_array($userType, ['super admin', 'Admin Team']) || \Auth::user()->can('level 1')) {
            // No additional filtering needed
        } elseif ($userType === 'company') {
            $leads_query->where('leads.brand_id', \Auth::user()->id);
        } elseif (in_array($userType, ['Project Director', 'Project Manager']) || \Auth::user()->can('level 2')) {
            $leads_query->whereIn('leads.brand_id', $brand_ids);
        } elseif (($userType === 'Region Manager' || \Auth::user()->can('level 3')) && !empty(\Auth::user()->region_id)) {
            $leads_query->where('leads.region_id', \Auth::user()->region_id);
        } elseif (($userType === 'Branch Manager' || in_array($userType, ['Admissions Officer', 'Admissions Manager', 'Marketing Officer'])) || \Auth::user()->can('level 4') && !empty(\Auth::user()->branch_id)) {
            $leads_query->where('leads.branch_id', \Auth::user()->branch_id);
        } else {
            $leads_query->where('user_id', \Auth::user()->id);
        }

        // Apply global search if provided
        if (request()->filled('ajaxCall') && request()->ajax() && request()->filled('search')) {
            $g_search = request()->input('search');
            $leads_query->where(function ($query) use ($g_search) {
                $query->where('leads.name', 'like', "%$g_search%")
                    ->orWhere('users.name', 'like', "%$g_search%")
                    ->orWhere('branches.name', 'like', "%$g_search%")
                    ->orWhere('lead_stages.name', 'like', "%$g_search%")
                    ->orWhere('leads.email', 'like', "%$g_search%")
                    ->orWhere('assigned_to.name', 'like', "%$g_search%")
                    ->orWhere('leads.phone', 'like', "%$g_search%");
            });
        }

        // Apply default filters when $_GET is empty
        if (empty($_GET)) {
            $leads_query->whereNotIn('lead_stages.name', ['Unqualified', 'Junk Lead'])
                ->where('leads.is_converted', 0);
        }

        $leads = $leads_query->groupBy('leads.stage_id')->count();
        // ->orderBy('leads.created_at', 'desc')
        // ->skip($start)
        // ->take($num_results_on_page)
        // ->count();

        return $leads;
    }
}
