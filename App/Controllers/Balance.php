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

    public function showCurrentMonthAction(){
      $date = date('Y-m');
      $this->expenses = $this->user->getExpenses($date);
      $expensesSum = $this->getSum($this->expenses);

      View::renderTemplate('Balance/show.html', [
        'rows' => $this->expenses,
        'expensesSum' => $expensesSum
      ]);
    }

    public function showPreviouseMonthAction(){
      $date = date('Y-m', strtotime('-1 month'));
      $this->expenses = $this->user->getExpenses($date);
      $expensesSum = $this->getSum($this->expenses);

      View::renderTemplate('Balance/show.html', [
        'rows' => $this->expenses,
        'expensesSum' => $expensesSum
      ]);
    }

    public function showCurrentYearAction(){
      $date = date('Y');
      $this->expenses = $this->user->getExpenses($date);
      $expensesSum = $this->getSum($this->expenses);

      View::renderTemplate('Balance/show.html', [
        'rows' => $this->expenses,
        'expensesSum' => $expensesSum
      ]);
    }

    //
    public function showUserBalanceAction(){
      $data = $_POST;
      $this->balance = $data['balance'];

      if($this->balance == "current_month"){
        $this->redirect('/balance/show-current-month');
      }
      if($this->balance == "previouse_month"){
        $this->redirect('/balance/show-previouse-month');
      }
      if($this->balance == "current_year"){
        $this->redirect('/balance/show-current-year');
      }
      else{
        $this->redirect('/balance/show');
      }
    }

    private function getSum($data){
      $sum = 0;
      foreach($data as $row => $innerArray){

        $int = (int)$innerArray['sum'];
        $sum += $int;
      }

      return $sum;
    }

}
