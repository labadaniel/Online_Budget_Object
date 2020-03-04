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

		if(!empty($_POST['newName'])){

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

					case 'editMethod':
						$idMethod = $_REQUEST['editSubjectID'];
						User::changeUserMethodName($idMethod, $newName);
						break;

					case 'addIncomeCategory':
						User::addNewIncomeCategory($newName);
						break;

					case 'addExpenseCategory':
						User::addNewExpenseCategory($newName);
						break;

					case 'addMethodPaymentCategory':
						User::addNewMethodPaymentCategory($newName);
						break;
				}
		}elseif(isset($_REQUEST['newLimit'])){
			if($_REQUEST['newLimit']==0){

				$idCategory = $_REQUEST['editSubjectID'];
				$newLimit = NULL;
				User::addLimitToCategory($idCategory, $newLimit);
			}else{
				$idCategory = $_REQUEST['editSubjectID'];
				$newLimit = $_REQUEST['newLimit'];

				User::addLimitToCategory($idCategory, $newLimit);
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

	public function deleteAction(){

		$subject = $_REQUEST['subject'];
		


			switch($subject){
				case 'deleteExpense':

					$idExpense = $_REQUEST['deleteSubjectID'];

					if(User::copyDeletedExpenseToOther($idExpense)){
						User::deleteUserExpenseName($idExpense);
					}
					break;

				case 'deleteIncome':
					$idIncome = $_REQUEST['deleteSubjectID'];

					if(User::copyDeletedIncomeToOther($idIncome)){
						User::deleteUserIncomeName($idIncome);
					}
					break;

				case 'deleteMethod':
					$idMethod = $_REQUEST['deleteSubjectID'];
					User::deleteUserMethodName($idMethod);
					break;


		}



	}



}
