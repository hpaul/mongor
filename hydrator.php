<?php namespace Mongor;

class Hydrator {

	public static function hydrate($mongor, $results)
	{
		$models = static::base(get_class($mongor), $results);

		if(count($models) > 0)
		{
			foreach($mongor->includes as $include)
			{
				if(!method_exists($mongor, $include))
				{
					throw new \LogicException("Attempting to eager load [$include], but the relationship is not defined.");
				}

				static::eagerly($mongor, $models, $include);
			}
		}

		return $models;
	}

	private static function base($class, $results)
	{
		$models = array();

		foreach ($results as $result)
		{
			$model = new $class;

			$model->attributes = (array) $result;

			$model->exists = true;

			if (isset($model->attributes['_id']))
			{
				$id = (string) $model->attributes['_id'];

				$models[$id] = $model;
			}
			else
			{
				$models[] = $model;
			}
		}

		return $models;
	}

	private static function eagerly($mongor, &$parents, $include)
	{
		$first = reset($parents);

		$mongor->attributes = $first->attributes;

		$relationship = $mongor->$include();

		$mongor->attributes = $_ids = array();

		foreach ($parents as &$parent)
		{
			$_ids[] = $parent->_id;

			$parent->ignore[$include] = (in_array($mongor->relating, array('has_many', 'has_and_belongs_to_many'))) ? array() : null;
		}

		if (in_array($relating = $mongor->relating, array('has_one', 'has_many', 'belongs_to')))
		{
			static::$relating($relationship, $parents, $mongor->relating_key, $include, $_ids);
		}
		else
		{
			static::has_and_belongs_to_many($relationship, $parents, $mongor->relating_key, $mongor->relating_table, $include, $_ids);
		}
	}

	/**
	 * Eagerly load a 1:1 relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $include
	 * @param  array   $_ids
	 * @return void
	 */
	private static function has_one($relationship, &$parents, $relating_key, $include, $_ids)
	{
		foreach($relationship->where(array($relating_key => array('$in' => $_ids)))->get() as $key => $child)
		{
			$parents[(string)$child->$relating_key]->ignore[$include] = $child;
		}
	}

	/**
	 * Eagerly load a 1:* relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $include
	 * @param  array   $_ids
	 * @return void
	 */
	private static function has_many($relationship, &$parents, $relating_key, $include, $_ids)
	{
		foreach($relationship->where(array($relating_key => array('$in' => $_ids)))->get() as $key => $child)
		{
			$parents[(string)$child->$relating_key]->ignore[$include][(string) $child->_id] = $child;
		}
	}

	/**
	 * Eagerly load a 1:1 belonging relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $include
	 * @param  array   $_ids
	 * @return void
	 */
	private static function belongs_to($relationship, &$parents, $relating_key, $include, $_ids)
	{
		$children = $relationship->where(array($relating_key => array('$in' => $_ids)))->get();

		foreach ($parents as &$parent)
		{
			if (array_key_exists((string)$parent->$relating_key, $children))
			{
				$parent->ignore[$include] = $children[(string)$parent->$relating_key];
			}
		}
	}

	/**
	 * Eagerly load a many-to-many relationship.
	 *
	 * @param  object  $relationship
	 * @param  array   $parents
	 * @param  string  $relating_key
	 * @param  string  $relating_table
	 * @param  string  $include
	 *
	 * @return void
	 */
	private static function has_and_belongs_to_many($relationship, &$parents, $relating_key, $relating_table, $include, $_ids)
	{
		// The model "has and belongs to many" method sets the SELECT clause; however, we need
		// to clear it here since we will be adding the foreign key to the select.
		$relationship->where = null;

		$relationship->where($relating_table.'.'.$relating_key, array_keys($parents));

		// The foreign key is added to the select to allow us to easily match the models back to their parents.
		// Otherwise, there would be no apparent connection between the models to allow us to match them.
		$children = $relationship->query->get(array(Model::table(get_class($relationship)).'.*', $relating_table.'.'.$relating_key));

		$class = get_class($relationship);

		foreach ($children as $child)
		{
			$related = new $class;

			$related->attributes = (array) $child;

			$related->exists = true;

			// Remove the foreign key since it was only added to the query to help match the models.
			unset($related->attributes[$relating_key]);

			$parents[$child->$relating_key]->ignore[$include][$child->id] = $related;
		}
	}
}
