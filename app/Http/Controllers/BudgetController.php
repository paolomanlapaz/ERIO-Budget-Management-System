<?php

namespace App\Http\Controllers;

use App\ListOfSecondaryAccounts;
use App\ListOfTertiaryAccounts;
use App\SecondaryAccounts;
use App\TertiaryAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Budget;
use App\PrimaryAccounts;
use App\ListOfPrimaryAccounts;
use DB;

class BudgetController extends Controller
{

    public function viewProposeBudget(){
        $budgetId = Budget::where([
            ['approved_by_vp', '=', '1'],
            ['approved_by_acc', '=', '1'],
        ])->orderBy('created_at', 'desc')
            ->first()
            ->pluck('id');

        $budgetP = Budget::where('approved_by_vp', '=', '0')
            ->orWhere('approved_by_acc', '=', '0')
            ->get();

        if($budgetP->isEmpty()){
            $primary_accounts = DB::table('primary_accounts')
                                    ->select('name')
                                    ->join('list_of_primary_accounts', 'list_of_primary_accounts.account_id', '=',
                                        'primary_accounts.id')
                                    ->join('budgets', 'budgets.id', '=', 'list_of_primary_accounts.budget_id')
                                    ->where('budget_id', '=', $budgetId)
                                    ->groupBy('name')
                                    ->get();

            $secondary_accounts = DB::table('secondary_accounts')
                                    ->select('name')
                                    ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.id', '=',
                                        'secondary_accounts.account_id')
                                    ->join('list_of_primary_accounts', 'list_of_primary_accounts.id', '=',
                                        'list_of_secondary_accounts.account_id')
                                    ->join('budgets', 'budgets.id', '=', 'list_of_primary_accounts.budget_id')
                                    ->where('budget_id', '=', $budgetId)
                                    ->groupBy('name')
                                    ->get();

            $tertiary_accounts = DB::table('tertiary_accounts')
                                    ->select('name')
                                    ->join('list_of_tertiary_accounts', 'tertiary_accounts.subaccount_id', '=',
                                        'list_of_tertiary_accounts.id')
                                    ->join('list_of_secondary_accounts', 'list_of_tertiary_accounts.account_id', '=',
                                        'list_of_secondary_accounts.id')
                                    ->join('list_of_primary_accounts', 'list_of_secondary_accounts.account_id', '=',
                                        'list_of_primary_accounts.id')
                                    ->join('budgets', 'budgets.id', '=', 'list_of_primary_accounts.budget_id')
                                    ->where('budget_id', '=', $budgetId)
                                    ->groupBy('name')
                                    ->get();

            return view('proposal/proposeBudget', [
                'primary_accounts' => $primary_accounts,
                'secondary_accounts' => $secondary_accounts,
                'tertiary_accounts' => $tertiary_accounts
            ]); //return to view with data of previous years budget
        }
    }

    /*
    public function submitBudget(Request $request){
        $validator = Validator::make($request->all(),[
            'counter' => 'required|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        if($validator->fails()){
            return redirect('/propose')
                ->withErrors($validator);
        }

        $counter = $request->counter; //counter for proposed accounts
        $budget_max_id = Budget::max('id');
        if(is_null($budget_max_id))   //check max budget id
            $budget_max_id=1;
        else
            $budget_max_id++;

        $budget = new Budget();
        $budget->start_range = $request->start_date;
        $budget->end_range = $request->end_date;
        $budget->id = $budget_max_id;
        //TDO add boolean sa budgets DB to know if executive approves of budget
        $budget->approved_by_acc = false;
        $budget->approved_by_vp = false;
        $budget->save();


        $pal_max_id = ListOfPrimaryAccounts::max('id');
        if(is_null($pal_max_id))   //check max primary account list id
            $pal_max_id=1;
        else
            $pal_max_id++;
        $primary_accounts_list->id = $pal_max_id;
         //autoincrement na ba to

        while($counter>0){
            $account = new PrimaryAccounts();

            $cat = 'account_num_'.$counter;
            $name = $request->$cat;
            //TODO check if empty, if empty break
            $cat2 = 'budget_num_'.$counter;
            $amt = $request->$cat2;
            if(is_null($name)||is_null($amt)){
                $counter--;
                break;
            }
            echo($name);
            $primary_accounts_list = new ListOfPrimaryAccounts();

            $record = PrimaryAccounts::where('name', '=', $name)->first();
            if(is_null($record)){ //check if account exists
                $max_id = PrimaryAccounts::max('id');
                if(is_null($max_id))   //check max primary account id
                    $max_id=1;
                else
                    $max_id++;

                $account->id = $max_id;
                $account->name = $name;
                $account->save();

                $primary_accounts_list->account_id = $max_id;
            }
            else{
                //get id of existing record
                $existing_id = PrimaryAccounts::where('name', '=', $name)->pluck('id');
                $primary_accounts_list->account_id = $existing_id;
            }
            $primary_accounts_list->budget_id = $budget_max_id;
            $primary_accounts_list->amount = $amt;
            $primary_accounts_list->save(); //save to DB

            $counter--;

        }

        return;
    }
*/ //old submit proposal

    public function addAccount(Request $request){
        //TODO validation
        if(isset($request->account_p) && !isset($request->account_s)){
            $primary_account_ref = $request->account_p;

            $account = new SecondaryAccounts();
            $account->name = $request->account;
            $account->account_id = $this->getPrimaryAccountId($primary_account_ref);
            $account->save();

            $list_account = new ListOfSecondaryAccounts();
            $list_account->account_id = $account->id;
            $list_account->amount = $request->budget;
            $list_account->list_id = $this->getPrimaryListId($primary_account_ref);
            $list_account->save();

            return redirect('/propose/'.$request->account_p);
        }

        if(isset($request->account_s)){
            $primary_account_ref = $request->account_p;
            $secondary_account_ref = $request->account_s;

            $account = new TertiaryAccounts();
            $account->name = $request->account;
            $account->subaccount_id = $this->getSecondaryAccountId($primary_account_ref, $secondary_account_ref);
            $account->save();

            $list_account = new ListOfTertiaryAccounts();
            $list_account->account_id = $account->id;
            $list_account->list_id  = $this->getSecondaryListId($primary_account_ref, $secondary_account_ref);
            $list_account->amount = $request->budget;
            $list_account->save();

            $prim_acc_name = $request->account_p;
            $sec_acc_name = $request->account_s;

            return redirect('/propose/'.$prim_acc_name.'/'.$sec_acc_name);
        }

        $account = new PrimaryAccounts();
        $account->name = $request->account;
        $account->code = $request->code;
        $account->save();

        $list_account = new ListOfPrimaryAccounts();
        $list_account->account_id = $account->id;
        $list_account->budget_id = $this->getProposalBudgetId(); //
        $list_account->amount = $request->budget;
        $list_account->save();

        return redirect('/propose');
    }

    public function getProposalBudgetId(){
        $proposal_id = DB::table('budgets')
                        ->select('id')
                        ->where('approved_by_vp', '=', '0')
                        ->orWhere('approved_by_acc', '=', '0')
                        ->get();

        foreach($proposal_id as $p){
            $proposal_id = $p->id;
        }

        return $proposal_id;
    }

    public function getSecondaryListId($primary_account_ref, $secondary_account_ref){
        $secondary_list_id = DB::table('budgets')
                                ->select('list_of_secondary_accounts.id')
                                ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                                    '=','budgets.id')
                                ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.list_id',
                                    '=', 'list_of_primary_accounts.id')
                                ->join('primary_accounts', 'primary_accounts.id', '=',
                                    'list_of_primary_accounts.account_id')
                                ->join('secondary_accounts', 'secondary_accounts.id', '=',
                                    'list_of_secondary_accounts.account_id')
                                ->where([
                                    ['secondary_accounts.name', '=', $secondary_account_ref],
                                    ['primary_accounts.name', '=', $primary_account_ref],
                                    ['approved_by_vp', '=', '0']])
                                ->orWhere([
                                    ['secondary_accounts.name', '=', $secondary_account_ref],
                                    ['primary_accounts.name', '=', $primary_account_ref],
                                    ['approved_by_acc', '=', '0']])
                                ->get();

        foreach($secondary_list_id as $sec){
            $list_id = $sec->id;
        }

        return $list_id;
    }

    public function getSecondaryAccountId($primary_account_ref, $secondary_account_ref){
        $secondary_account_id = DB::table('budgets')
                        ->select('secondary_accounts.id')
                        ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                            '=','budgets.id')
                        ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.list_id',
                            '=', 'list_of_primary_accounts.id')
                        ->join('primary_accounts', 'primary_accounts.id', '=',
                            'list_of_primary_accounts.account_id')
                        ->join('secondary_accounts', 'secondary_accounts.id', '=',
                            'list_of_secondary_accounts.account_id')
                        ->where([
                            ['secondary_accounts.name', '=', $secondary_account_ref],
                            ['primary_accounts.name', '=', $primary_account_ref],
                            ['approved_by_vp', '=', '0']])
                        ->orWhere([
                            ['secondary_accounts.name', '=', $secondary_account_ref],
                            ['primary_accounts.name', '=', $primary_account_ref],
                            ['approved_by_acc', '=', '0']])
                        ->get();

        foreach($secondary_account_id as $sec){
            $sec_acc_id = $sec->id;
        }

        return $sec_acc_id;
    }

    public function getPrimaryListId($primary_account_ref){
        $primary_list_id = DB::table('budgets')
                                ->select('list_of_primary_accounts.id')
                                ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                                    '=', 'budgets.id')
                                ->join('primary_accounts', 'primary_accounts.id', '=',
                                    'list_of_primary_accounts.account_id')
                                ->where([
                                    ['approved_by_vp', '=', '0'],
                                    ['primary_accounts.name', '=', $primary_account_ref]])
                                ->orWhere([
                                    ['approved_by_acc', '=', '0'],
                                    ['primary_accounts.name', '=', $primary_account_ref]])
                                ->get();

        foreach($primary_list_id as $pri){
            $list_id = $pri->id;
        }

        return $list_id;
    }

    public function getPrimaryAccountId($primary_account_ref){
        $primary_account_id = DB::table('budgets')
                            ->select('primary_accounts.id')
                            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                                '=','budgets.id')
                            ->join('primary_accounts', 'primary_accounts.id', '=',
                                'list_of_primary_accounts.account_id')
                            ->where([
                                ['primary_accounts.name', '=', $primary_account_ref],
                                ['approved_by_vp', '=', '0']])
                            ->orWhere([
                                ['primary_accounts.name', '=', $primary_account_ref],
                                ['approved_by_acc', '=', '0']])
                            ->get();

        foreach($primary_account_id as $prim){
            $acc_id = $prim->id;
        }

        return $acc_id;
    }

    public function createEmptyBudget(Request $request){
        //TODO validator to check if meron na

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $budget = new Budget();
        $budget->start_range = $start_date;
        $budget->end_range = $end_date;
        $budget->approved_by_acc = 0;
        $budget->approved_by_vp = 0;
        $budget->save();

        return redirect('propose/'); //redirect to add accounts page
    }

    public function getPrimaryAccounts(){
        $list = DB::table('budgets')
            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id', '=',
                'budgets.id')
            ->join('primary_accounts', 'primary_accounts.id', '=',
                'list_of_primary_accounts.account_id')
            ->where('approved_by_vp', '=', '0')
            ->orWhere('approved_by_acc', '=', '0')
            ->get();

        return $list;
    }

    /*
    public function getPrimaryAccountName($primary_account){
        $account_name = DB::table('budgets')
                            ->select('name')
                            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                                '=', 'budgets.id')
                            ->join('primary_accounts', 'primary_accounts.id', '=',
                                'list_of_primary_accounts.account_id')
                            ->where([
                                ['primary_accounts.name', '=', $primary_account],
                                ['approved_by_vp', '=', '0']])
                            ->orWhere([
                                ['primary_accounts.name', '=', $primary_account]])
                            ->get();

        return $account_name;
    }

    public function getSecondaryAccountName($secondary_account, $primary_account){
        $account_name = DB::table('budgets')
            ->select('secondary_account.name')
            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                '=', 'budgets.id')
            ->join('primary_accounts', 'primary_accounts.id', '=',
                'list_of_primary_accounts.account_id')
            ->where([
                ['primary_accounts.name', '=', $primary_account],
                ['secondary_accounts.name', '=', $secondary_account],
                ['approved_by_vp', '=', '0']])
            ->orWhere([
                ['primary_accounts.name', '=', $primary_account],
                ['secondary_accounts.name', '=', $secondary_account]])
            ->get();

        return $account_name;
    }
    */ //get account names

    public function getSecondaryAccounts($primary_account){
        $sub_accounts = DB::table('budgets')
            ->select('secondary_accounts.name', 'list_of_secondary_accounts.amount')
            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                '=', 'budgets.id')
            ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.list_id', '=',
                'list_of_primary_accounts.id')
            ->join('secondary_accounts', 'secondary_accounts.id', '=',
                'list_of_secondary_accounts.account_id')
            ->join('primary_accounts', 'primary_accounts.id', '=',
                'list_of_primary_accounts.account_id')
            ->where([
                ['primary_accounts.name', '=', $primary_account],
                ['approved_by_vp', '=', '0']])
            ->orWhere([
                ['primary_accounts.name', '=', $primary_account],
                ['approved_by_acc', '=', '0']])
            ->get();

        return $sub_accounts;
    }

    public function getTertiaryAccounts($secondary_account, $primary_account){
        $sub_accounts = DB::table('budgets')
                            ->select('tertiary_accounts.name', 'list_of_tertiary_accounts.amount')
                            ->join('list_of_primary_accounts', 'list_of_primary_accounts.budget_id',
                                '=', 'budgets.id')
                            ->join('list_of_secondary_accounts', 'list_of_secondary_accounts.list_id',
                                '=', 'list_of_primary_accounts.id')
                            ->join('list_of_tertiary_accounts', 'list_of_tertiary_accounts.list_id',
                                '=','list_of_secondary_accounts.id')
                            ->join('tertiary_accounts', 'tertiary_accounts.id', '=',
                                'list_of_tertiary_accounts.account_id')
                            ->join('secondary_accounts', 'secondary_accounts.id', '=',
                                'list_of_secondary_accounts.account_id')
                            ->join('primary_accounts', 'primary_accounts.id', '=',
                                'list_of_primary_accounts.account_id')
                            ->where([
                                ['secondary_accounts.name', '=', $secondary_account],
                                ['primary_accounts.name', '=', $primary_account],
                                ['approved_by_vp', '=', '0']])
                            ->orWhere([
                                ['secondary_accounts.name', '=', $secondary_account],
                                ['primary_accounts.name', '=', $primary_account],
                                ['approved_by_acc', '=', '0']])
                            ->get();

        return $sub_accounts;
    }

    public function getAccount($primary_account = null, $secondary_account = null){
        if($primary_account && $secondary_account){
            $sub_accounts = $this->getTertiaryAccounts($secondary_account, $primary_account);
            return view('proposal/AddAccount', [
                'account_1' => $primary_account,
                'account_2' => $secondary_account,
                'accounts' => $sub_accounts,

            ]);
        }
        else if($primary_account){
            // $account_name1 = $this->getPrimaryAccountName($primary_account);
            $sub_accounts = $this->getSecondaryAccounts($primary_account);
            return view('proposal/AddAccount', [
                'account_1' => $primary_account,
                'accounts' => $sub_accounts
            ]);
        }
        else{
            $primary_accounts_list = $this->getPrimaryAccounts();
            // $account_name1 = $this->getPrimaryAccountName($primary_account);
            // $account_name2 = $this->getSecondaryAccountName($primary_account, $secondary_account);
            return view('proposal/AddAccount', [
                'accounts' => $primary_accounts_list,
                'pa' => true
            ]);
        }

    }

    public function showLinks(){ //TEMPO
        return view('proposal/links');
    }

    public function createRangeView(){
        return view('proposal/addRange');
    }

}

//TODO CHECK if null list_ids