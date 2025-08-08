<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationStage extends Model
{
    use HasFactory;

    public function deals(){
        $usr = \auth::user();
        $start = 0;
        $num_results_on_page = env("RESULTS_ON_PAGE");
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $num_of_result_per_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
            $start = ($page - 1) * $num_results_on_page;
        }else{
            $num_results_on_page = isset($_GET['num_results_on_page']) ? $_GET['num_results_on_page'] : $num_results_on_page;
        }


        if($usr->can('view all deals') || \Auth::user()->type == 'super admin'){
            $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
            $deal1_query->where('deal_applications.stage_id', '=', $this->id)->where('deal_applications.stage_id', '=', $this->id);
            return $deal1_query->orderBy('deal_applications.stage_id')->orderBy('deal_applications.id', 'DESC')->skip($start)->take($num_results_on_page)->get();
        }else if(\auth::user()->type == "company") {
            $users = User::select(['users.id'])->join('roles', 'roles.name', '=', 'users.type')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where(['users.created_by' => \auth::id(), 'permissions.name' => 'create deal'])
            ->groupBy('users.id')
            ->pluck('id')
            ->toArray();

            $deal_created_by = $users;
            $deal_created_by[] = $usr->id;
            $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
            $deal1_query->whereIn('user_deals.user_id', $deal_created_by);
            $deal1_query->where('deal_applications.stage_id', '=', $this->id)->where('deal_applications.stage_id', '=', $this->id);
            return $deal1_query->orderBy('deal_applications.order')->orderBy('deal_applications.id', 'DESC')->skip($start)->take($num_results_on_page)->get();

        }else{
            $deal_created_by[] = \auth::user()->created_by;
            $deal_created_by[] = $usr->id;

            $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
            $deal1_query->whereIn('user_deals.user_id', $deal_created_by);
            $deal1_query->where('deal_applications.stage_id', '=', $this->id)->where('deal_applications.stage_id', '=', $this->id);
            return $deal1_query->orderBy('deal_applications.order')->orderBy('deal_applications.id', 'DESC')->skip($start)->take($num_results_on_page)->get();
        }
    }

    public function deals_count(){
        // return DealApplication::select('deal_applications.*')->join('client_deals','client_deals.deal_id','=','deal_applications.id')->where('client_deals.client_id', '=', \Auth::user()->id)->where('deal_applications.stage_id', '=', $this->id)->orderBy('deal_applications.order')->count();

           $usr =\auth::user();
        if($usr->can('view all deals') || \Auth::user()->type == 'super admin'){
           $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
           return $deal1_query->where('deal_applications.stage_id', '=', $this->id)->count();
       }else if(\auth::user()->type == "company") {
           $users = User::select(['users.id'])->join('roles', 'roles.name', '=', 'users.type')
           ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'roles.id')
           ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
           ->where(['users.created_by' => \auth::id(), 'permissions.name' => 'create deal'])
           ->groupBy('users.id')
           ->pluck('id')
           ->toArray();
           $deal_created_by = $users;
           $deal_created_by[] = $usr->id;
           $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
           $deal1_query->whereIn('user_deals.user_id', $deal_created_by);
           return $deal1_query->where('deal_applications.stage_id', '=', $this->id)->count();

       }else{
           $deal_created_by[] = \auth::user()->created_by;
           $deal_created_by[] = $usr->id;

           $deal1_query = DealApplication::select('deal_applications.*')->join('user_deals', 'user_deals.deal_id', '=', 'deal_applications.id');
           $deal1_query->whereIn('user_deals.user_id', $deal_created_by);
           return $deal1_query->where('deal_applications.stage_id', '=', $this->id)->count();
       }
   }

}
