<?php
namespace App\Services;
use App\Models\Transaction;
use App\Models\User;
use App\Services\EmailService;

class TransactionService {
    protected $transaction;
    public function __construct(Transaction $transaction) {
        $this->transaction = $transaction;
    }

    public function createTransaction($request) {
        return $this->transaction->createTransaction($request);
    }

    public function getUserTransactions($user_id) {
        return $this->transaction->getTransactionsByUserId($user_id);
    }

    public function getUserById($user_id) {
        return User::find($user_id);
    }
}
?>