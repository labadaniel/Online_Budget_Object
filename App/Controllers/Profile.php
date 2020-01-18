<?php

namespace App\Controllers;


use Core\View;
use App\Auth;
use App\Flash;
use App\Models\User;
/**
 * Account controller
 *
 * PHP version 7.0
 */
class Profile extends Authenticated
{
	protected function before()
	{
		parent::before();

		$this->user = Auth::getUser();
	}

    /**
     * Validate if email is available (AJAX) for a new signup.
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Profile/show.html',[
			'user'=>$this->user,
			'expenses'=>User::getUserExpensesList(),
			'incomes'=>User::getUserIncomesList(),
			'methods'=>User::getUserMethodPaymentList()
			]);
    }

	public function editAction()
	{
		View::renderTemplate('profile/edit.html',[
			'user'=>$this->user
			]);
	}

	public function updateAction()
	{
		if(isset($_REQUEST['newName'])){

			$newName = $_REQUEST['newName'];
			$subject = $_REQUEST['subject'];

			switch($subject){
				case 'editExpense':
					$idExpense = $_REQUEST['editSubjectID'];
					User::changeUserExpenseName($idExpense, $newName);
					break;

				case 'editIncome':
					$idIncome = $_REQUEST['editSubjectID'];
					User::changeUserIncomeName($idIncome, $newName);
					break;
			}

		}

		if ($this->user->updateProfile($_POST))
		{
			Flash::addMessage('Zmiany zapisane.');
			$this->redirect('/profile/show');

		} else {

			View::renderTemplate('Profile/show.html', [
				'user' => $this->user
			]);
		}
	}



}
