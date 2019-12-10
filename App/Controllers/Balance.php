<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Balance extends \Core\Controller
{
  protected function before()
  {
    parent::before();

    $this->user = Auth::getUser();
  }


    /**
     * Show the index page
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Balance/show.html');
    }

    public function showUserBudget($date){
      //$date = date('Y-m');

      $this->expenses = $this->user->getExpenses($date);
      $this->incomes = $this->user->getIncomes($date);

      $expensesSum = $this->getSum($this->expenses);
      $incomesSum = $this->getSum($this->incomes);

      View::renderTemplate('Balance/show.html', [
        'expenses' => $this->expenses,
        'expensesSum' => $expensesSum,
        'incomes' => $this->incomes,
        'incomesSum' => $incomesSum
      ]);
    }





    //
    public function showUserBalanceAction(){
      $data = $_POST;
      $this->balance = $data['balance'];
      $this->fromDate = $data['from_date'];
      $this->toDate = $data['to_date'];



      if($this->balance == "current_month"){
        $this->showUserBudget(date('Y-m'));
      }
      if($this->balance == "previouse_month"){
        $this->showUserBudget(date('Y-m', strtotime('-1 month')));
      }
      if($this->balance == "current_year"){
        $this->showUserBudget(date('Y'));
      }
      if($this->balance == "user_period"){
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
      $this->expenses = $this->user->getExpensesUserDate($this->fromDate, $this->toDate);
      $this->incomes = $this->user->getIncomesUserDate($this->fromDate, $this->toDate);

      $expensesSum = $this->getSum($this->expenses);
      $incomesSum = $this->getSum($this->incomes);

      View::renderTemplate('Balance/show.html', [
        'expenses' => $this->expenses,
        'expensesSum' => $expensesSum,
        'incomes' => $this->incomes,
        'incomesSum' => $incomesSum
      ]);
    }

}
