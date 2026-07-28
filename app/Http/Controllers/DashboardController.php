<?php
namespace App\Http\Controllers;
use App\Models\{Lead,CallLog,FollowUp,Order,Payment,User};
use Illuminate\Http\Request;
class DashboardController extends Controller {
 public function index(Request $r){$cid=$r->user()->company_id; $base=Lead::where('company_id',$cid); return view('dashboard',[ 'totalLeads'=>(clone $base)->count(),'newToday'=>(clone $base)->whereDate('created_at',today())->count(),'hotLeads'=>(clone $base)->where('temperature','hot')->count(),'callsToday'=>CallLog::where('company_id',$cid)->whereDate('created_at',today())->count(),'connectedToday'=>CallLog::where('company_id',$cid)->whereHas('disposition',fn($q)=>$q->where('type','connected'))->whereDate('created_at',today())->count(),'followUpsDue'=>FollowUp::where('company_id',$cid)->where('status','pending')->whereDate('scheduled_at',today())->count(),'overdue'=>FollowUp::where('company_id',$cid)->where('status','pending')->where('scheduled_at','<',now())->count(),'sales'=>Order::where('company_id',$cid)->sum('total_amount'),'received'=>Payment::where('company_id',$cid)->sum('amount'),'activeUsers'=>User::where('company_id',$cid)->where('is_active',true)->count(),'recentLeads'=>(clone $base)->with(['assignedUser','status'])->latest()->limit(8)->get() ]); }
}
