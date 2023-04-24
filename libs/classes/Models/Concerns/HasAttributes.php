<?php

namespace BSWP\Models\Concerns;

trait HasAttributes
{
	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = array();

	/**
	 * Get an attribute from the model.
	 *
	 * @param string $key
	 * 
	 * @return mixed
	 */
	public function getAttribute( $key )
	{
		if (! $key) {
			return;
		}

		$value = isset( $this->model->{$key} ) ? $this->model->{$key} : null;

		if ( $this->hasCast( $key ) ) {
			return $this->castAttribute( $key, $value );
		}

		return $value;
	}

	/**
	 * Determine whether an attribute should be cast to a native type.
	 *
	 * @param  string  $key
	 * @param  array|string|null  $types
	 * @return bool
	 */
	public function hasCast( $key, $types = null )
	{
		if ( array_key_exists( $key, $this->getCasts() ) ) {
			return $types ? in_array( $this->getCastType( $key ), (array) $types, true) : true;
		}

		return false;
	}

	/**
	 * Cast an attribute to a native PHP type.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	protected function castAttribute( $key, $value )
	{
		if ( is_null( $value ) ) {
			return $value;
		}

		switch ( $this->getCastType( $key ) ) {
			case 'int':
			case 'integer':
				return (int) $value;
			case 'real':
			case 'float':
			case 'double':
				return $this->fromFloat( $value );
			case 'string':
				return (string) $value;
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'array':
			case 'json':
				return $this->fromJson( $value );
			case 'unserialized':
				return maybe_unserialize( $value );
			default:
				return $value;
		}
	}

	/**
	 * Get the type of cast for a model attribute.
	 *
	 * @param string $key
	 * 
	 * @return string
	 */
	protected function getCastType( $key )
	{
		return trim( strtolower( $this->getCasts()[ $key ] ) );
	}

	/**
	 * Get the casts array.
	 *
	 * @return array
	 */
	public function getCasts()
	{
		return $this->casts;
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param string $value
	 * @param bool   $asObject
	 * 
	 * @return mixed
	 */
	public function fromJson( $value, $asObject = false )
	{
		return json_decode( $value, ! $asObject );
	}

	/**
	 * Decode the given float.
	 *
	 * @param mixed $value
	 * 
	 * @return mixed
	 */
	public function fromFloat( $value )
	{
		switch ( (string) $value ) {
			case 'Infinity':
				return INF;
			case '-Infinity':
				return -INF;
			case 'NaN':
				return NAN;
			default:
				return (float) $value;
		}
	}
}