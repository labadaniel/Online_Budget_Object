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
			'user'=>$this->user
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
		//echo var_dump($_POST); exit;
		//if($_POST['updateProfile']){
			if ($this->user->updateProfile($_POST))
			{
				Flash::addMessage('Changes saved');
				$this->redirect('/profile/show');

			} else {

				View::renderTemplate('Profile/show.html', [
					'user' => $this->user
				]);
			}
		//}

	}

	public function showUserIncomesListAction(){

		$incomes = User::getUserIncomesList();

		foreach($incomes as $income){
			echo '<div class="card tlo4"><div class="card-body float-right">';
			echo $income["name"];
			echo '<div class="float-right pl-1">Edytuj</div>';
			echo '<div class="float-right">Usuń</div>';
			echo '</div></div>';
		}
	}

	public function showUserExpensesListAction(){

		$expenses = User::getUserExpensesList();

		foreach($expenses as $expense){
			echo '<div class="card tlo4"><div class="card-body">';
			echo $expense["name"];
			echo '<div class="float-right pl-1">Edytuj</div>';
			echo '<div class="float-right">Usuń</div>';
			echo '</div></div>';
		}
	}

	public function showUserMethodPaymentListAction(){
		$paymentMethods = User::getUserMethodPaymentList();

		foreach($paymentMethods as $paymentMethod){
			echo '<div class="card tlo4"><div class="card-body">';
			echo $paymentMethod["name"];
			echo '<div class="float-right pl-1">Edytuj</div>';
			echo '<div class="float-right">Usuń</div>';
			echo '</div></div>';
		}
	}


}
