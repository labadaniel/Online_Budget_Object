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

    /**
     * Show the index page
     *
     * @return void
     */
    public function showAction()
    {
        View::renderTemplate('Income/show.html');
    }
}
