<?php

namespace App\Models;

use PDO;
use \App\Token;
USE \App\Auth;
use \App\Mail;
use \Core\View;

/**
 * User model
 *
 * PHP version 7.0
 */
class User extends \Core\Model
{



    /**
     * Error messages
     *
     * @var array
     */
    public $errors = [];

    /**
     * Class constructor
     *
     * @param array $data  Initial property values (optional)
     *
     * @return void
     */
    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        };
    }

    /**
     * Save the user model with the current property values
     *
     * @return boolean  True if the user was saved, false otherwise
     */
    public function save()
    {
        $this->validate();

        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $token = new Token();
            $hashed_token = $token->getHash();
			$this->activation_token = $token->getValue();

            $sql = 'INSERT INTO users (name, email, password_hash, activation_hash)
                    VALUES (:name, :email, :password_hash, :activation_hash)';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
            $stmt->bindValue(':activation_hash', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }

    /**
     * Validate current property values, adding valiation error messages to the errors array property
     *
     * @return void
     */
    public function validate()
    {
        // Name
        if ($this->name == '') {
            $this->errors[] = 'Name is required';
        }

        // email address
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = 'Invalid email';
        }
        if (static::emailExists($this->email, $this->id ?? null)) {
            $this->errors[] = 'email already taken';
        }

		if (isset($this->password))
		{
			// Password
			if (strlen($this->password) < 6) {
				$this->errors[] = 'Please enter at least 6 characters for the password';
			}

			if (preg_match('/.*[a-z]+.*/i', $this->password) == 0) {
				$this->errors[] = 'Password needs at least one letter';
			}

			if (preg_match('/.*\d+.*/i', $this->password) == 0) {
				$this->errors[] = 'Password needs at least one number';
			}
		}
    }

    /**
     * See if a user record already exists with the specified email
     *
     * @param string $email email address to search for
     * @param string $ignore_id Return false anyway if the record found has this ID
     *
     * @return boolean  True if a record already exists with the specified email, false otherwise
     */
    public static function emailExists($email, $ignore_id = null)
    {
        $user = static::findByEmail($email);

        if ($user) {
            if ($user->id != $ignore_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find a user model by email address
     *
     * @param string $email email address to search for
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByEmail($email)
    {
        $sql = 'SELECT * FROM users WHERE email = :email';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Authenticate a user by email and password.
     *
     * @param string $email email address
     * @param string $password password
     *
     * @return mixed  The user object or false if authentication fails
     */
    public static function authenticate($email, $password)
    {
        $user = static::findByEmail($email);

        if ($user && $user->is_active) {
            if (password_verify($password, $user->password_hash)) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Find a user model by ID
     *
     * @param string $id The user ID
     *
     * @return mixed User object if found, false otherwise
     */
    public static function findByID($id)
    {
        $sql = 'SELECT * FROM users WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Remember the login by inserting a new unique token into the remembered_logins table
     * for this user record
     *
     * @return boolean  True if the login was remembered successfully, false otherwise
     */
    public function rememberLogin()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->remember_token = $token->getValue();

        $this->expiry_timestamp = time() + 60 * 60 * 24 * 30;  // 30 days from now

        $sql = 'INSERT INTO remembered_logins (token_hash, user_id, expires_at)
                VALUES (:token_hash, :user_id, :expires_at)';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $this->expiry_timestamp), PDO::PARAM_STR);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions to the user specified
     *
     * @param string $email The email address
     *
     * @return void
     */
    public static function sendPasswordReset($email)
    {
        $user = static::findByEmail($email);

        if ($user) {

            if ($user->startPasswordReset()) {

                $user->sendPasswordResetEmail();

            }
        }
    }

    /**
     * Start the password reset process by generating a new token and expiry
     *
     * @return void
     */
    protected function startPasswordReset()
    {
        $token = new Token();
        $hashed_token = $token->getHash();
        $this->password_reset_token = $token->getValue();

        $expiry_timestamp = time() + 60 * 60 * 2;  // 2 hours from now

        $sql = 'UPDATE users
                SET password_reset_hash = :token_hash,
                    password_reset_expires_at = :expires_at
                WHERE id = :id';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', date('Y-m-d H:i:s', $expiry_timestamp), PDO::PARAM_STR);
        $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Send password reset instructions in an email to the user
     *
     * @return void
     */
    protected function sendPasswordResetEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/password/reset/' . $this->password_reset_token;

        $text = View::getTemplate('Password/reset_email.txt', ['url' => $url]);
        $html = View::getTemplate('Password/reset_email.html', ['url' => $url]);

        Mail::send($this->email, 'Password reset', $text, $html);
    }

    /**
     * Find a user model by password reset token and expiry
     *
     * @param string $token Password reset token sent to user
     *
     * @return mixed User object if found and the token hasn't expired, null otherwise
     */
    public static function findByPasswordReset($token)
    {
        $token = new Token($token);
        $hashed_token = $token->getHash();

        $sql = 'SELECT * FROM users
                WHERE password_reset_hash = :token_hash';

        $db = static::getDB();
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':token_hash', $hashed_token, PDO::PARAM_STR);

        $stmt->setFetchMode(PDO::FETCH_CLASS, get_called_class());

        $stmt->execute();

        $user = $stmt->fetch();

        if ($user) {

            // Check password reset token hasn't expired
            if (strtotime($user->password_reset_expires_at) > time()) {

                return $user;
            }
        }
    }

    /**
     * Reset the password
     *
     * @param string $password The new password
     *
     * @return boolean  True if the password was updated successfully, false otherwise
     */
    public function resetPassword($password)
    {
        $this->password = $password;

        $this->validate();

        //return empty($this->errors);
        if (empty($this->errors)) {

            $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

            $sql = 'UPDATE users
                    SET password_hash = :password_hash,
                        password_reset_hash = NULL,
                        password_reset_expires_at = NULL
                    WHERE id = :id';

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);

            return $stmt->execute();
        }

        return false;
    }

	public function sendActivationEmail()
    {
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/signup/activate/' . $this->activation_token;

        $text = View::getTemplate('Signup/activation_email.txt', ['url' => $url]);
        $html = View::getTemplate('Signup/activation_email.html', ['url' => $url]);

        Mail::send($this->email, 'Account activation', $text, $html);
    }

	public static function activate ($value)
	{
		$token = new Token ($value);
		$hashed_token = $token->getHash();

		$sql = 'UPDATE users
                    SET is_active = 1,
                        activation_hash = NULL
                    WHERE activation_hash = :hashed_token';

            $db = static::getDB();
            $stmt = $db->prepare($sql);


            $stmt->bindValue(':hashed_token', $hashed_token, PDO::PARAM_STR);

            return $stmt->execute();

	}

	public function updateProfile($data)
	{
		$this->name = $data['name'];
		$this->email = $data['email'];


		if ($data['password'] != '')
		{
			$this->password = $data['password'];
		}

		$this->validate();

		if (empty($this->errors))
		{
			$sql = 'UPDATE users
                    SET name = :name,
                        email = :email';
			if (isset($this->password)){
				$sql .= ', password_hash = :password_hash';
			}

			$sql .= "\nWHERE id = :id";

            $db = static::getDB();
            $stmt = $db->prepare($sql);

            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
            $stmt->bindValue(':name', $this->name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);

			if (isset($this->password)){
				$password_hash = password_hash($this->password, PASSWORD_DEFAULT);
				$stmt->bindValue(':password_hash', $password_hash, PDO::PARAM_STR);
			}

            return $stmt->execute();
		}
		return false;
	}

  private static function getIncomeCategoryID($category, $id){
    //query("SELECT id FROM incomes_category_assigned_to_users WHERE name = '$category' AND user_id = '$id'"))

    $sql = "SELECT id
            FROM incomes_category_assigned_to_users
            WHERE name = '$category'
            AND user_id = '$id'";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    $id_category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $id_category['id'];
  }

  public static function addIncome($data, $id)
  {
    $amount = $data['amount'];
    //$amount = $_POST['amount'];
    $date = date('Y-m-d',strtotime($data['date']));
    //$date = date('Y-m-d',strtotime($_POST['date']));
    $category = $data['category'];
    //$category = $_POST['category'];
    $comment = $data['comment'];
    //$comment = $_POST['comment'];

    $id_category = static::getIncomeCategoryID($category, $id);

//"INSERT INTO incomes VALUES (NULL, '$id', '$id_category',  '$amount', '$date', '$comment')"
    $sql = "INSERT INTO incomes (user_id, income_category_assigned_to_user_id, amount, date_of_income, income_comment)
            VALUES (:user_id, :income_category_assigned_to_user_id, :amount, :date_of_income, :income_comment)";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':income_category_assigned_to_user_id', $id_category, PDO::PARAM_INT);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindValue(':date_of_income', $date, PDO::PARAM_STR);
    $stmt->bindValue(':income_comment', $comment, PDO::PARAM_STR);

    return $stmt->execute();
  }

  public static function addExpense($data, $id)
  {

    //query("INSERT INTO expenses VALUES (NULL, '$id', '$id_category', '$id_method', '$amount', '$date', '$comment')"
    $amount = $data['amount'];
    $date = date('Y-m-d',strtotime($data['date']));
    $comment = $data['comment'];
    $method = $data['method'];
    $category = $data['category'];
    $id_category = static::getExpenseCategoryID($category, $id);
    $id_method = static::getMethodID($method, $id);

    $sql = "INSERT INTO expenses (user_id, expense_category_assigned_to_user_id, payment_method_assigned_to_user_id, amount, date_of_expense, expense_comment)
            VALUES (:user_id, :expense_category_assigned_to_user_id, :payment_method_assigned_to_user_id, :amount, :date_of_expense, :expense_comment)";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':expense_category_assigned_to_user_id', $id_category, PDO::PARAM_INT);
    $stmt->bindValue(':payment_method_assigned_to_user_id', $id_method, PDO::PARAM_INT);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
    $stmt->bindValue(':date_of_expense', $date, PDO::PARAM_STR);
    $stmt->bindValue(':expense_comment', $comment, PDO::PARAM_STR);


    return $stmt->execute();
  }

  private static function getExpenseCategoryID($category, $id){

    $sql = "SELECT id
            FROM expenses_category_assigned_to_users
            WHERE name = '$category'
            AND user_id = '$id'";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    $id_category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $id_category['id'];
  }

  private static function getMethodID($method, $id){

    $sql = "SELECT id
            FROM payment_methods_assigned_to_users
            WHERE name = '$method'
            AND user_id = '$id'";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    $id_category = $stmt->fetch(PDO::FETCH_ASSOC);
    return $id_category['id'];
  }

  public function assignExpensesToUser($id){

    $sql = "INSERT INTO expenses_category_assigned_to_users (name, user_id)
            SELECT expenses_category_default.name, users.id
            FROM expenses_category_default, users
            WHERE users.id = '$id'";
    $db = static::getDB();
    $stmt = $db->prepare($sql);
    return $stmt->execute();

  }

  public function assignIncomesToUser($id){

    $sql = "INSERT INTO incomes_category_assigned_to_users (name, user_id)
            SELECT incomes_category_default.name, users.id
            FROM incomes_category_default, users
            WHERE users.id = '$id'";
    $db = static::getDB();
    $stmt = $db->prepare($sql);
    return $stmt->execute();

  }

  public function assignMethodsToUser($id){

    $sql = "INSERT INTO payment_methods_assigned_to_users (name, user_id)
            SELECT payment_methods_default.name, users.id
            FROM payment_methods_default, users
            WHERE users.id = '$id'";
    $db = static::getDB();
    $stmt = $db->prepare($sql);
    return $stmt->execute();

  }


  public function getNewUserID(){
    $sql = "SELECT MAX(id)
            FROM users";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    $id = $stmt->fetch();

    return $id[0];
  }

  public static function getExpenses($date){

    $id = $_SESSION['user_id'];

    $sql = "SELECT expuser.name, SUM(exp.amount) AS sum
            FROM expenses  AS exp
            INNER JOIN expenses_category_assigned_to_users AS expuser
            WHERE expuser.id = exp.expense_category_assigned_to_user_id
            AND exp.user_id = '$id'
            AND exp.date_of_expense LIKE '%$date%'
            GROUP BY expuser.name
            ORDER BY exp.date_of_expense DESC";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getExpensesUserDate($date1, $date2){

    $id = $_SESSION['user_id'];

    $sql = "SELECT expuser.name, SUM(exp.amount) AS sum
            FROM expenses  AS exp
            INNER JOIN expenses_category_assigned_to_users AS expuser
            WHERE expuser.id = exp.expense_category_assigned_to_user_id
            AND exp.user_id = '$id'
            AND exp.date_of_expense >= '$date1'
            AND exp.date_of_expense <= '$date2'
            GROUP BY expuser.name
            ORDER BY exp.date_of_expense DESC";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getIncomes($date){

    $id = $_SESSION['user_id'];

    $sql = "SELECT incuser.name, SUM(inc.amount) AS sum
            FROM incomes  AS inc
            INNER JOIN incomes_category_assigned_to_users AS incuser
            WHERE incuser.id = inc.income_category_assigned_to_user_id
            AND inc.user_id = '$id'
            AND inc.date_of_income LIKE '%$date%'
            GROUP BY incuser.name
            ORDER BY inc.date_of_income DESC";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getIncomesUserDate($date1, $date2){

    $id = $_SESSION['user_id'];

    $sql = "SELECT incuser.name, SUM(inc.amount) AS sum
            FROM incomes  AS inc
            INNER JOIN incomes_category_assigned_to_users AS incuser
            WHERE incuser.id = inc.income_category_assigned_to_user_id
            AND inc.user_id = '$id'
            AND inc.date_of_income >= '$date1'
            AND inc.date_of_income <= '$date2'
            GROUP BY incuser.name
            ORDER BY inc.date_of_income DESC";

    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getUserIncomesList(){

    $id = $_SESSION['user_id'];

    $sql = "SELECT *
            FROM incomes_category_assigned_to_users
            WHERE user_id = '$id'";
            
    $db = static::getDB();
    $stmt = $db->prepare($sql);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);

  }


}
