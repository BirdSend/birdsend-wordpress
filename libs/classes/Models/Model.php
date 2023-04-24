<?php

namespace BSWP\Models;

use BSWP\Models\Concerns\HasAttributes;

class Model
{
	use HasAttributes;

	/**
	 * Form
	 *
	 * @var mixed
	 */
	protected $model;

	/**
	 * Create new model instance
	 *
	 * @param mixed $model
	 *
	 * @return void
	 */
	public function __construct( $model )
	{
		$this->model = $model;
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}
}