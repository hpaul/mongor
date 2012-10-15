<?php namespace Mongor; use Laravel\Hash as Hash, Laravel\Config as Config;

class MongoAuth extends \Laravel\Auth\Drivers\Driver {

	/**
	 * Get the current user of the application.
	 *
	 * If the user is a guest, null should be returned.
	 *
	 * @param  int         $id
	 * @return mixed|null
	 */
	public function retrieve($id)
	{
		if (!$id) {
			return NULL;
		}
		
		return $this->model()->where("_id", new \MongoId($id))->first();
	}

	/**
	 * Attempt to log a user into the application.
	 *
	 * @param  array  $arguments
	 * @return void
	 */
	public function attempt($arguments = array())
	{
		$username = Config::get('auth.username');

		$user = $this->model()->where($username, $arguments[$username])->first();

		// This driver uses a basic username and password authentication scheme
		// so if the credentials match what is in the database we will just
		// log the user into the application and remember them if asked.
		$password = $arguments['password'];

		if ( ! is_null($user) and Hash::check($password, $user->password))
		{
			return $this->login($user->_id, array_get($arguments, 'remember'));
		}

		return false;
	}

	/**
	 * Get a fresh model instance.
	 *
	 * @return Eloquent
	 */
	protected function model()
	{
		$model = Config::get('auth.model');

		return new $model;
	}

}