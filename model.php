<?php namespace Mongor;

class Model {

	/**
	 * Collection for active model
	 *
	 * @var null
	 */
	public static $collection = null;

	/**
	 * Indicates if the model has creation and update timestamps.
	 *
	 * @var bool
	 */
	public static $timestamps = false;

	/**
	 * Connection to use for active model
	 *
	 * @var string
	 */
	public $connection = 'database';

	/**
	 * Indicates if the model exists in the database.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * The model's attributes.
	 *
	 * Typically, a model has an attribute for each column of the collection.
	 *
	 * @var array
	 */
	public $attributes = array();

	/**
	 * The model's dirty attributes.
	 *
	 * @var array
	 */
	public $dirty = array();

	/**
	 * The model's ignored attributes.
	 *
	 * Ignored attributes will not be saved to the database, and are
	 * primarily used to hold relationships.
	 *
	 * @var array
	 */
	public $ignore = array();

	/**
	 * The relationships that should be eagerly loaded.
	 *
	 * @var array
	 */
	public $includes = array();

	/**
	 * The relationship type the model is currently resolving.
	 *
	 * @var string
	 */
	public $relating;

	/**
	 * The foreign key of the "relating" relationship.
	 *
	 * @var string
	 */
	public $relating_key;

	/**
	 * The collection name of the model being resolved.
	 *
	 * This is used during many-to-many eager loading.
	 *
	 * @var string
	 */
	public $relating_table;

	/**
	 * @var null
	 */
	public $_limit = null;

	/**
	 * @var null
	 */
	public $_skip = null;

	/**
	 * @var array
	 */
	public $_where = array();

	/**
	 * @var array
	 */
	public $_sort = array();

	public function __construct($connection = NULL)
	{
		if ($connection !== NULL)
		{
			$this->connection = $connection;
		}

		if (is_string($this->connection))
		{
			$this->connection = new MongoDB($this->connection);
		}

		if (is_null(static::$collection))
		{
			static::$collection = strtolower(get_called_class());
		}
	}

	/****************************************************
	 *	Query Methods
	 ****************************************************/

	/**
	 * @param  array|string  $where
	 * @param  string        $value
	 * @return Model
	 */
	public function _where($where, $value = null)
	{
		if(is_array($where))
		{
			$this->_where = $where;
		}
		else
		{
			$this->_where = array($where => $value);
		}

        $this->_count = null;

		return $this;
	}

	/**
	 * @param  int     $limit
	 * @return Model
	 */
	public function _take($limit)
	{
		$this->_limit = $limit;

		return $this;
	}

	/**
	 * @param  int    $skip
	 * @return Model
	 */
	public function _skip($skip)
	{
		$this->_skip = $skip;

		return $this;
	}

	/**
	 * @param  array    $fields
	 * @return Model
	 */
	public function _sort($fields)
	{
		$this->_sort = $fields;

		return $this;
	}

	/**
	 * Set the attributes of the model using an array.
	 *
	 * @param  array  $attributes
	 * @return Model
	 */
	public function _fill($attributes)
	{
		foreach($attributes as $key => $value)
		{
			$this->$key = $value;
		}

		return $this;
	}

	/**
	 * Set the eagerly loaded models on the queryable model.
	 *
	 * @return Model
	 */
	private function _with()
	{
		$this->includes = func_get_args();

		return $this;
	}

	/**
	 * Set the creation and update timestamps on the model.
	 *
	 * Uses the time() method
	 *
	 * @return void
	 */
	private function _timestamp()
	{
		$this->updated_at = time();

		if ( ! $this->exists) $this->created_at = $this->updated_at;
	}

	/**
	 * Get a single result
	 *
	 * @param  array  $fields
	 * @return Model
	 */
	public function _first($fields = array())
	{
		$this->attributes =  $this->connection->find_one(static::$collection, $this->_where, $fields);
		$this->exists = is_null($this->attributes) ? false : true;

		return $this;
	}

    protected $_count = null;

    public function _count($fields = array())
    {
        if (null === $this->_count) {
            $this->_count = $this->connection->find(static::$collection, $this->_where, $fields)->count();
        }
        return $this->_count;
    }

    public function _order_by($field, $dir = null)
    {
        $this->_sort[$field] = ('desc' === strtolower($dir)) ? -1 : 1;
        return $this;
    }

	/**
	 * @param  array  $fields
	 * @return array
	 */
	public function _get($fields = array())
	{
		$results =  $this->connection->find(static::$collection, $this->_where, $fields);

        $this->_count = $results->count();

		if ( ! is_null($this->_limit))
		{
			$results->limit($this->_limit);
		}

		if( !  is_null($this->_skip))
		{
			$results->skip($this->_skip);
		}

		if ( ! empty($this->_sort))
		{
			$results->sort($this->_sort);
		}

		return Hydrator::hydrate($this, $results);
	}

	/**
	 * @param  array  $options
	 * @return bool
	 */
	public function _save($options = array())
	{
		if ($this->exists and count($this->dirty) == 0) return true;

		if(static::$timestamps)
		{
			$this->_timestamp();
		}

		if ($this->exists)
		{
			$success = $this->connection->update(static::$collection, array('_id' => $this->attributes['_id']), array('$set' => $this->dirty), $options);
		}
		else
		{
			$insert = $this->connection->insert(static::$collection, $this->attributes, $options);

			$success = !is_null($this->attributes['_id'] = $insert['_id']);
		}

		($this->exists = true) and $this->dirty = array();

		return $success;
	}

	/****************************************************
	 *	Relationship Methods
	 ****************************************************/

	/**
	 * Retrieve the query for a 1:1 relationship.
	 *
	 * @param  string  $model
	 * @param  string  $foreign_key
	 * @return mixed
	 */
	public function has_one($model, $foreign_key = null)
	{
		$this->relating = __FUNCTION__;

		return $this->has_one_or_many($model, $foreign_key);
	}

	/**
	 * Retrieve the query for a 1:* relationship.
	 *
	 * @param  string  $model
	 * @param  string  $foreign_key
	 * @return mixed
	 */
	public function has_many($model, $foreign_key = null)
	{
		$this->relating = __FUNCTION__;

		return $this->has_one_or_many($model, $foreign_key);
	}

	/**
	 * Retrieve the query for a 1:1 or 1:* relationship.
	 *
	 * The default foreign key for has one and has many relationships is the name
	 * of the model with an appended _id. For example, the foreign key for a
	 * User model would be user_id. Photo would be photo_id, etc.
	 *
	 * @param  string  $model
	 * @param  string  $foreign_key
	 * @return mixed
	 */
	private function has_one_or_many($model, $foreign_key)
	{
		$this->relating_key = (is_null($foreign_key)) ? strtolower(static::model_name($this)).'_id' : $foreign_key;

		return $model::where('_id', $this->attributes[$this->relating_key]);
	}

	/**
	 * Retrieve the query for a 1:1 belonging relationship.
	 *
	 * The default foreign key for belonging relationships is the name of the
	 * relationship method name with _id. If a model has a "manager" method
	 * the returned belongs_to relationship key would be manager_id.
	 *
	 * @param  string  $model
	 * @param  string  $foreign_key
	 * @return mixed
	 */
	public function belongs_to($model, $foreign_key = null)
	{
		$this->relating = __FUNCTION__;

		if ( ! is_null($foreign_key))
		{
			$this->relating_key = $foreign_key;
		}
		else
		{
			list(, $caller) = debug_backtrace(false);

			$this->relating_key = $caller['function'].'_id';
		}

		return $model::where('_id', $this->attributes[$this->relating_key]);
	}

	/**
	 * Retrieve the query for a *:* relationship.
	 *
	 * The default foreign key for many-to-many relations is the name of the model
	 * with an appended _id. This is the same convention as has_one and has_many.
	 *
	 * @param  string  $model
	 * @param  string  $relation_model
	 * @param  string  $foreign_key
	 * @param  string  $associated_key
	 * @return mixed
	 */
	public function has_and_belongs_to_many($model, $relation_model, $foreign_key = null, $associated_key = null)
	{
		$this->relating = __FUNCTION__;

		$this->relating_key = $foreign_key;

		$relation = $relation_model::where($this->relating_key, $this->_id)->first();


	    return $model::where('_id', $relation->attributes[$associated_key]);
	}

	/****************************************************
	 *	Magic Methods
	 ****************************************************/

	/**
	 * @param  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}

		// Is the requested item a model relationship that has already been loaded?
		// All of the loaded relationships are stored in the "ignore" array.
		elseif (array_key_exists($key, $this->ignore))
		{
			return $this->ignore[$key];
		}

		// Is the requested item a model relationship? If it is, we will dynamically
		// load it and return the results of the relationship query.
		elseif (method_exists($this, $key))
		{
			$query = $this->$key();

			return $this->ignore[$key] = (in_array($this->relating, array('has_one', 'belongs_to'))) ? $query->first() : $query->get();
		}
	}

	/**
	 * Magic Method for handling dynamic method calls.
	 */
	public function __call($method, $parameters)
	{
		if(method_exists($this, '_' . $method))
		{
			return call_user_func_array(array($this, '_' . $method), $parameters);
		}
		throw new \Exception("Method [$method] is not defined.");
	}

	/**
	 * Magic Method for setting model attributes.
	 */
	public function __set($key, $value)
	{
		// If the key is a relationship, add it to the ignored attributes.
		// Ignored attributes are not stored in the database.
		if (method_exists($this, $key))
		{
			$this->ignore[$key] = $value;
		}
		else
		{
			$this->attributes[$key] = $value;
			$this->dirty[$key] = $value;
		}
	}

	/**
	 * Magic Method for handling dynamic static method calls.
	 */
	public static function __callStatic($method, $parameters)
	{
		$model = get_called_class();

		return call_user_func_array(array(new $model, $method), $parameters);
	}

}