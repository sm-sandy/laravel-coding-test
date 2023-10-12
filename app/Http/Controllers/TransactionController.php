<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::all();
        $balance = Transaction::where('transaction_type', 'deposit')->sum('amount') - Transaction::where('transaction_type', 'withdrawal')->sum('amount');

        return response()->json(['transactions' => $transactions, 'balance' => $balance]);
    }

    public function depositIndex()
    {
        $deposits = Transaction::where('transaction_type', 'deposit')->get();

        return response()->json(['deposits' => $deposits]);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|numeric'
        ]);

        Transaction::create([
            'user_id' => $request->user_id,
            'transaction_type' => 'deposit',
            'amount' => $request->amount,
            'fee' => 0,
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $user = User::find($request->user_id);
        $user->balance += $request->amount;
        $user->save();

        return response()->json(['message' => 'Deposit recorded successfully']);
    }

    public function withdrawalIndex()
    {
        $withdrawals = Transaction::where('transaction_type', 'withdrawal')->get();

        return response()->json(['withdrawals' => $withdrawals]);
    }

    public function withdrawal(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'amount' => 'required|numeric',
            'date' => 'required'
        ]);

        // Retrieve the user based on the user_id
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        //Free withdrawal condition check

        // $currentDate = Carbon::now();
        $currentDate = Carbon::createFromDate($request->date);
        $isFriday = $currentDate->isFriday();

        $isFirstTransaction = ($user->withdrawa_count == 0);

        $is5kTransactionThisMonth = Transaction::where('user_id', $user->id)
            ->where('transaction_type', 'withdrawal')
            ->whereMonth('date', $currentDate->month)
            ->sum('amount');



        // Calculate the withdrawal fee based on the user's account type
        $accountType = $user->account_type;
        $withdrawalRate = ($accountType === 'individual') ? 0.00015 : 0.00025;
        $withdrawalFee = $request->amount * $withdrawalRate;

        if ($isFriday || $isFirstTransaction || ($is5kTransactionThisMonth <= 5000)) {
            // Withdrawal is free
            $withdrawalFee = 0;
            $totalAmount = $request->amount;
        } else {
            // Apply withdrawal fee
            $withdrawalFee = $request->amount * $withdrawalRate;
            $totalAmount = $request->amount + $withdrawalFee;
        }

        // Ensure the user has sufficient balance
        if ($user->balance >= $totalAmount) {
            $user->balance -= $totalAmount;
            $user->withdrawa_count++;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'transaction_type' => 'withdrawal',
                'fee' => $withdrawalFee,
                'date' => $request->date,
            ]);

            return response()->json(['message' => 'Withdrawal successful']);
        } else {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }
    }
}
