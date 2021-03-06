<?php

namespace App\Http\Controllers;

use App\AccessedPrimaryAccounts;
use App\AccessedSecondaryAccounts;
use App\AccessedTertiaryAccounts;
use App\Budget;
use App\ListOfPrimaryAccounts;
use App\ListOfSecondaryAccounts;
use App\ListOfTertiaryAccounts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use DB;

class AccountController extends Controller
{
    public function accessedAccountsView(){
        $budget = Budget::latest()->whereDate('start_range', '<', Carbon::now())->first();
        $primary = AccessedPrimaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Open');
        $secondary = AccessedSecondaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Open');
        $tertiary = AccessedTertiaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Open');

        $pendingPA = AccessedPrimaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Pending');
        $pendingSA = AccessedSecondaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Pending');
        $pendingTA = AccessedTertiaryAccounts::all()->where('user_id', Auth::user()->id)
                                            ->where('status', 'Pending');


//        $tempPA = ListOfPrimaryAccounts::where('budget_id', $budget->id)->get();
//
//        $primary = $tempPA->filter(function($p) use ($apa){
//            foreach($apa as $a){
//                if($a->list_id == $p->id)
//                    return true;
//            }
//            return false;
//        });
//
//        $tempSA = ListOfSecondaryAccounts::all();
//
//        $secondary = $tempSA->filter(function($s) use ($asa){
//            foreach($asa as $a){
//                if($a->list_id == $s->id)
//                    return true;
//            }
//            return false;
//        });
//
//        $tempTA = ListOfTertiaryAccounts::all();
//
//        $tertiary = $tempTA->filter(function($t) use ($ata){
//            foreach($ata as $a){
//                if($a->list_id == $t->id)
//                    return true;
//            }
//            return false;
//        });

        return view('accessedAccountsView')
            ->with('primary', $primary)
            ->with('secondary', $secondary)
            ->with('tertiary', $tertiary)
            ->with('pendingPA', $pendingPA)
            ->with('pendingSA', $pendingSA)
            ->with('pendingTA', $pendingTA);
    }

    public function requestAccessForm(){
        //TODO Ayusin yung filter... :D
        $latest_budget = Budget::latest()->whereDate('start_range', '<', Carbon::now())->first(); //can cause problems

        $primary = DB::table('list_of_primary_accounts')
                        ->leftJoin('accessed_primary_accounts', 'list_of_primary_accounts.id', '=',
                            'accessed_primary_accounts.list_id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'list_of_primary_accounts.account_id')
                        ->select(
                            'primary_accounts.name as primary_name',
                            'list_of_primary_accounts.id as id'
                        )
                        ->where('list_of_primary_accounts.budget_id', $latest_budget->id)
                        ->whereNull('accessed_primary_accounts.id')
                        ->get();

        $secondary = DB::table('list_of_secondary_accounts')
                        ->leftJoin('accessed_secondary_accounts', 'list_of_secondary_accounts.id',
                            '=', 'accessed_secondary_accounts.list_id')
                        ->join('list_of_primary_accounts', 'list_of_primary_accounts.id', '=',
                            'list_of_secondary_accounts.list_id')
                        ->join('secondary_accounts', 'secondary_accounts.id', '=',
                            'list_of_secondary_accounts.account_id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'secondary_accounts.account_id')
                        ->select(
                            'secondary_accounts.name as secondary_name',
                            'list_of_secondary_accounts.id as id',
                            'primary_accounts.name as primary_name'
                        )
                        ->where('list_of_primary_accounts.budget_id', $latest_budget->id)
                        ->whereNull('accessed_secondary_accounts.id')
                        ->get();

        $tertiary = DB::table('list_of_tertiary_accounts')
                        ->leftJoin('accessed_tertiary_accounts', 'list_of_tertiary_accounts.id', '=',
                            'accessed_tertiary_accounts.list_id')
                        ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.id', '=',
                            'list_of_tertiary_accounts.list_id')
                        ->join('list_of_primary_accounts', 'list_of_primary_accounts.id', '=',
                            'list_of_secondary_accounts.list_id')
                        ->join('tertiary_accounts', 'list_of_tertiary_accounts.account_id', '=',
                            'tertiary_accounts.id')
                        ->join('secondary_accounts', 'secondary_accounts.id', '=',
                            'tertiary_accounts.subaccount_id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'secondary_accounts.account_id')
                        ->select(
                            'tertiary_accounts.name as tertiary_name',
                            'list_of_tertiary_accounts.id as id',
                            'secondary_accounts.name as secondary_name',
                            'primary_accounts.name as primary_name'
                        )
                        ->where('list_of_primary_accounts.budget_id', $latest_budget->id)
                        ->whereNull('accessed_tertiary_accounts.id')
                        ->get();


        //return view form
        return view('requestAccessAccountForm')
            ->with('primary', $primary)
            ->with('secondary', $secondary)
            ->with('tertiary', $tertiary);
    }

    public function requestAccessSave(Request $request){
        $this->validate($request, [
            'explanation' => 'required',
            'account' => 'required'
        ]);

        $type = str_before($request->account, '-');
        $acc = str_after($request->account, '-');
        $id = (int) $acc;

        if($type == 'p'){
            $access = new AccessedPrimaryAccounts();
            $access->explanation = $request->explanation;
            $access->user_id = Auth::user()->id;
            $access->list_id = $id;
            $access->save();
        } else if($type == 's'){
            $access = new AccessedSecondaryAccounts();
            $access->explanation = $request->explanation;
            $access->user_id = Auth::user()->id;
            $access->list_id = $id;
            $access->save();
        } else {
            $access = new AccessedTertiaryAccounts();
            $access->explanation = $request->explanation;
            $access->user_id = Auth::user()->id;
            $access->list_id = $id;
            $access->save();
        }

        return redirect()->route('accessedAccountsView');
    }

    public function requestsForAccess(){
        $primary = DB::table('accessed_primary_accounts')
                        ->join('users', 'users.id', '=',
                            'accessed_primary_accounts.user_id')
                        ->join('list_of_primary_accounts', 'list_of_primary_accounts.id', '=',
                            'accessed_primary_accounts.list_id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'list_of_primary_accounts.account_id')
                        ->select(
                            'accessed_primary_accounts.id as id',
                            'users.name as user_name',
                            'primary_accounts.name as account_name',
                            'accessed_primary_accounts.explanation as explanation'
                        )
                        ->where('accessed_primary_accounts.status', 'Pending')
                        ->get();

        $secondary = DB::table('accessed_secondary_accounts')
                        ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.id',
                            '=', 'accessed_secondary_accounts.list_id')
                        ->join('secondary_accounts', 'secondary_accounts.id',
                            '=','list_of_secondary_accounts.account_id')
                        ->join('primary_accounts', 'primary_accounts.id',
                            '=', 'secondary_accounts.account_id')
                        ->join('users', 'users.id', '=',
                            'accessed_secondary_accounts.user_id')
                        ->select(
                            'accessed_secondary_accounts.id as id',
                            'users.name as user_name',
                            'secondary_accounts.name as account_name',
                            'primary_accounts.name as pa_name',
                            'accessed_secondary_accounts.explanation as explanation'
                        )
                        ->where('accessed_secondary_accounts.status', 'Pending')
                        ->get();

        $tertiary = DB::table('accessed_tertiary_accounts')
                        ->join('users', 'users.id', '=',
                            'accessed_tertiary_accounts.user_id')
                        ->join('list_of_tertiary_accounts', 'list_of_tertiary_accounts.id', '=',
                            'accessed_tertiary_accounts.list_id')
                        ->join('tertiary_accounts', 'tertiary_accounts.id', '=',
                            'list_of_tertiary_accounts.account_id')
                        ->join('secondary_accounts', 'secondary_accounts.id', '=',
                            'tertiary_accounts.subaccount_id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'secondary_accounts.account_id')
                        ->select(
                            'accessed_tertiary_accounts.id as id',
                            'users.name as user_name',
                            'primary_accounts.name as pa_name',
                            'secondary_accounts.name as sa_name',
                            'tertiary_accounts.name as account_name',
                            'accessed_tertiary_accounts.explanation as explanation'
                        )
                        ->where('accessed_tertiary_accounts.status', 'Pending')
                        ->get();

        return view('approveAccessAccounts')
            ->with('primary', $primary)
            ->with('secondary', $secondary)
            ->with('tertiary', $tertiary);
    }

    public function respondRequest(Request $request){
        $id = (int) str_after($request->id, '-');
        $type = str_before($request->id, '-');
        $status = "";
        if($request->submit == 'Approve'){
            $status = 'approved';
            if($type == 'p'){
                $acc = AccessedPrimaryAccounts::find($id);
                $acc->status = 'Open';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
                $pa = ListOfPrimaryAccounts::find($acc->list_id);
                foreach($pa->list_of_secondary_accounts as $sec){
                    $s = new AccessedSecondaryAccounts();
                    $s->user_id = $acc->user_id;
                    $s->explanation = 'Under Approved Primary Account';
                    $s->status = 'Open';
                    $s->list_id = $sec->id;
                    $s->approved_by = Auth::user()->id;
                    $s->save();

                    foreach($sec->list_of_tertiary_accounts as $ter){
                        $t = new AccessedTertiaryAccounts();
                        $t->user_id = $acc->user_id;
                        $t->explanation = 'Under Approved Secondary Account';
                        $t->status = 'Open';
                        $t->list_id = $ter->id;
                        $t->approved_by = Auth::user()->id;
                        $t->save();
                    }
                }
            } else if($type == 's'){
                $acc = AccessedSecondaryAccounts::find($id);
                $acc->status = 'Open';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
                $sa = ListOfSecondaryAccounts::find($acc->list_id);
                foreach($sa->list_of_tertiary_accounts as $ta){
                    $t = new AccessedTertiaryAccounts();
                    $t->user_id = $acc->user_id;
                    $t->explanation = 'Under Approved Secondary Account';
                    $t->status = 'Open';
                    $t->list_id = $ta->id;
                    $t->approved_by = Auth::user()->id;
                    $t->save();
                }
            } else {
                $acc = AccessedTertiaryAccounts::find($id);
                $acc->status = 'Open';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
            }
        } else {
            $status = "denied";
            if($type == 'p'){
                $acc = AccessedPrimaryAccounts::find($id);
                $acc->status = 'Denied';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
            } else if($type == 's'){
                $acc = AccessedSecondaryAccounts::find($id);
                $acc->status = 'Denied';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
            } else {
                $acc = AccessedTertiaryAccounts::find($id);
                $acc->status = 'Denied';
                $acc->approved_by = Auth::user()->id;
                $acc->save();
            }
        }

        $send_string = $request->owner . " request to access " . $request->account . " was " . $status;
        return redirect('request-accounts')->with('message', $send_string);
    }
}
