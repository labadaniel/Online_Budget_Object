<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Models\User;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Balance extends \Core\Controller
{

    /**
     * Show the index page
     *
     * @return void
     */
    public function showAction()
    {
        $data = $this->showUserBudget(date('Y-m'));
        
    }

    public function showUserBudget($date){
      //$date = date('Y-m');

      $this->expenses = User::getExpenses($date);
      $this->incomes = User::getIncomes($date);

      $this->expensesSum = $this->getSum($this->expenses);
      $this->incomesSum = $this->getSum($this->incomes);

      View::renderTemplate('Balance/show.html', [
        'expenses' => $this->expenses,
        'expensesSum' => $this->expensesSum,
        'incomes' => $this->incomes,
        'incomesSum' => $this->incomesSum
      ]);
    }





    //
    public function showUserBalanceAction(){
      $data = $_POST;
      $balance = $data['balance'];
      $this->fromDate = $data['from_date'];
      $this->toDate = $data['to_date'];



      if($balance == "current_month"){
        $this->showUserBudget(date('Y-m'));
      }
      if($balance == "previouse_month"){
        $this->showUserBudget(date('Y-m', strtotime('-1 month')));
      }
      if($balance == "current_year"){
        $this->showUserBudget(date('Y'));
      }
      if($balance == "user_period"){
        $this->showUserPeriod();
      }


    }

    private function getSum($data){
      $sum = 0;
      foreach($data as $row => $innerArray){

        $int = (float)$innerArray['sum'];
        $sum += $int;
      }

      return $sum;
    }

    public function showUserPeriod(){
      $expenses = User::getExpensesUserDate($this->fromDate, $this->toDate);
      $incomes = User::getIncomesUserDate($this->fromDate, $this->toDate);

      $expensesSum = $this->getSum($expenses);
      $incomesSum = $this->getSum($incomes);

      View::renderTemplate('Balance/show.html', [
        'expenses' => $expenses,
        'expensesSum' => $expensesSum,
        'incomes' => $incomes,
        'incomesSum' => $incomesSum
      ]);
    }

}
