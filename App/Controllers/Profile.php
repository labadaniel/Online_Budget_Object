<?php

namespace App\Controllers;


use Core\View;
use App\Auth;
use App\Flash;

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
		
		if ($this->user->updateProfile($_POST))
		{
			Flash::addMessage('Changes saved');
			
			$this->redirect('/profile/show');
			
		} else {
			
			View::renderTemplate('Profile/edit.html', [
				'user' => $this->user
			]);
		}
	}
}
