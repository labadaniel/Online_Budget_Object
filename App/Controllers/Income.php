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
class Income extends \Core\Controller
{


    /**
     * Show the index page
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Income/show.html', [
          'incomes'=>User::getUserIncomesList()
        ]);
    }

    public function addIncomeAction(){
      if(User::addIncome($_POST, $_SESSION['user_id'])){
        $this->redirect('/Income/show-message-income');
      }
    }

    public function showMessageIncomeAction(){
      Flash::addMessage('Dodano do bazy danych.');

      $this->redirect('/');

    }


}
