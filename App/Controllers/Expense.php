<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;
use \App\Models\User;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Expense extends Authenticated
{


    /**
     * Show the index page
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Expense/show.html',[
          'expenses'=>User::getUserExpensesList(),
          'methods'=>User::getUserMethodPaymentList(),
          'currentBalance'=>User::getExpenses(date('Y-m'))
        ]);
    }

    public function addExpenseAction(){

      if(User::addExpense($_POST, $_SESSION['user_id'])){
        Flash::addMessage('Dodano do bazy danych.');
        $this->redirect('/');
      }
    }
}
