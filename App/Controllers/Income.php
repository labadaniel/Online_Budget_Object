<?php

namespace App\Controllers;

use \Core\View;
use \App\Auth;

/**
 * Home controller
 *
 * PHP version 7.0
 */
class Income extends \Core\Controller
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
        View::renderTemplate('Income/show.html');
    }

    public function addIncomeAction(){
      if($this->user->addIncome($_POST, $this->user->id)){
        View::renderTemplate('Home/index.html');
      }
    }


}
