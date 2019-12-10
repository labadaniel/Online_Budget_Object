<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;
use \App\Flash;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Expense extends \Core\Controller
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
        View::renderTemplate('Expense/show.html');
    }

    public function addExpenseAction(){

      if($this->user->addExpense($_POST, $this->user->id)){
        $this->redirect('/Expense/show-message-expenses');
      }
    }

    public function showMessageExpensesAction(){
      Flash::addMessage('Dodano do bazy danych.');
      $this->redirect('/');
    }
}
