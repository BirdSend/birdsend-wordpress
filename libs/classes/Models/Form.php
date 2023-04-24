<?php

namespace BSWP\Models;

use BSWP\Helper;

class Form extends Model
{
	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = array(
		'triggers' => 'array',
		'raw_html' => 'unserialized',
		'wg_html' => 'unserialized',
	);

	/**
	 * Filters
	 *
	 * @var array
	 */
	protected $filters = array(
		'page-url' => 'filterPageUrl',
		'page-url-get-parameter' => 'filterPageUrlGetParameter',
		'wordpress-post-type' => 'filterWordpressPostType',
		'wordpress-post-category' => 'filterWordpressPostCategory',
		'wordpress-post-tag' => 'filterWordpressPostTag'
	);

	/**
	 * Allow multiple
	 *
	 * @var array
	 */
	protected $allowMultiple = array(
		'popup' => false,
		'welcome-screen' => false,
		'in-content' => true
	);

	/**
	 * Find a form by id
	 *
	 * @param int $id
	 *
	 * @return self
	 */
	public static function find( $id )
	{
		if (! $form = bswp_get_form( $id ) ) {
			return;
		}
		return new static( $form );
	}

	/**
	 * Allow multiple
	 *
	 * @return bool
	 */
	public function allowMultiple()
	{
		return Helper::get( $this->allowMultiple, $this->model->type, false );
	}

	/**
	 * Check if the form is eligible for the current page
	 *
	 * @param array $pageProfile
	 *
	 * @return boolean
	 */
	public function isEligible( $pageProfile = null )
	{
		$pageProfile = $pageProfile ? $pageProfile : self::getCurrentPageProfile();

		if ( empty( $this->triggers['filters'] ) || empty( $this->triggers['filters']['show'] ) ) {
			return false;
		}

		$showFilterSets = (array) $this->triggers['filters']['show'];
		$hideFilterSets = array_merge(
			(array) isset( $this->triggers['filters']['hide'] ) ? $this->triggers['filters']['hide'] : array(),
			(array) isset( $this->triggers['filters']['hide_more'] ) ? $this->triggers['filters']['hide_more'] : array()
		);

		$showWhen = isset( $this->triggers['show_when'] ) ? $this->triggers['show_when'] : '';

		if ($showWhen == 'disabled'
			|| ! $this->passesFilterSets($pageProfile, $showFilterSets)
			|| $this->passesFilterSets($pageProfile, $hideFilterSets, false)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Passes filter sets.
	 *
	 * @param array $pageProfile
	 * @param array $filterSets
	 * @param bool  $default
	 *
	 * @return bool
	 */
	public function passesFilterSets( $pageProfile, $filterSets, $default = true )
	{
		if (! $filterSets) {
			return $default;
		}

		$checkResults = [];

		foreach ($filterSets as $filterSet) {
			$activeFilters = isset( $filterSet['filters'] ) ? $filterSet['filters'] : array();
			$activeFilters = array_filter( $activeFilters, function ($filter) {
				return ! empty( $filter['active'] );
			} );

			if (! $activeFilters) {
				continue;
			}

			if ($passes = $this->passesFilterSet($pageProfile, $filterSet, $default)) {
				return true;
			}

			$checkResults[] = $passes;
		}

		if (! $checkResults) {
			return $default;
		}

		return !! array_filter( $checkResults, function ( $result ) {
			return $result;
		} );
	}

	/**
	 * Passes conditions.
	 *
	 * @param array $pageProfile
	 * @param array $filterSet
	 * @param bool  $default
	 *
	 * @return bool|string
	 */
	public function passesFilterSet( $pageProfile, $filterSet, $default = true )
	{
		$match = isset( $filterSet['match'] ) ? $filterSet['match'] : '';

		$activeFilters = isset( $filterSet['filters'] ) ? $filterSet['filters'] : array();
		$activeFilters = array_filter( $activeFilters, function ($filter) {
			return ! empty( $filter['active'] );
		} );

		if (! $activeFilters) {
			return true;
		}

		$checkResults = [];

		foreach ($activeFilters as $filter) {
			$filterName = isset( $filter['filter']['name'] ) ? $filter['filter']['name'] : '';
			$handler = isset( $this->filters[$filterName] ) ? $this->filters[$filterName] : '';

			if (! $filterName || ! method_exists($this, $handler)) {
				continue;
			}

			$filterInputs = isset( $filter['filter']['inputs'] ) ? $filter['filter']['inputs'] : array();
			if (! ($passes = $this->{$handler}($pageProfile, $filterInputs)) && $match == 'all') {
				return false;
			}

			if ($passes && $match == 'any') {
				return true;
			}

			$checkResults[] = $passes;
		}

		if (! $checkResults) {
			return $default;
		}

		return !! array_filter( $checkResults, function ( $result ) {
			return $result;
		} );
	}

    /**
     * Check if current domain is correct
     *
     * @param string $referer
     * @param string $domain
     *
     * @return bool
     */
    protected function isInDomain( $referer, $domain )
    {
        $refererDomain = Helper::getRefererDomain( $referer );

        if ( $domain && ! Helper::endsWith( $domain, $refererDomain ) ) {
            return false;
        }

        return true;
    }

    /**
     * Filter by page URL.
     *
     * @param array $pageProfile
     * @param mixed   $inputs
     *
     * @return bool
     */
    public function filterPageUrl( $pageProfile, $inputs = array() )
    {
        if (! $referer = $pageProfile['url'] ) {
            return true;
        }

        $domain = isset( $inputs['domain'] ) ? $inputs['domain'] : '';
        if (! $this->isInDomain( $referer, $domain ) ) {
            return false;
        }

        $refererDomain = Helper::getRefererDomain( $referer );

        $value = isset( $inputs['value'] ) ? $inputs['value'] : '';
        $operator = isset( $inputs['operator'] ) ? $inputs['operator'] : 'equals';

        if ( ! $value || ! $operator ) {
            return true;
        }

        $value = (array) $value; // casted to array so it is more flexible e.g. value is from multiselect dropdown
        $referer = parse_url($referer);
        $host = isset( $referer['host'] ) ? $referer['host'] : '';
        $src = trim( isset( $referer['path'] ) ? $referer['path'] : '', '/' );

        foreach ( $value as $url ) {
            $url = parse_url( $url );
            $path = isset( $url['path'] ) ? $url['path'] : '/';

            if (Helper::is('*'.$refererDomain.'*', $path)) {
                $path = str_replace([$host, $refererDomain], '', $path);
            }

            $target = trim($path, '/');

            if ($this->compare($src, $target, $operator)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare values.
     *
     * @param string $src
     * @param string $target
     * @param string $operator
     *
     * @return bool
     */
    public function compare($src, $target, $operator)
    {
        switch ($operator) {
        case 'equals':
        case 'is':
            return $src == $target;
        case 'contains':
            return Helper::is('*'.$target.'*', $src);
        case 'doesnt-equal':
        case 'isnot':
            return ! $this->compare($src, $target, 'equals');
        case 'doesnt-contain':
            return ! $this->compare($src, $target, 'contains');
        case 'starts-with':
            return Helper::startsWith($src, $target);
        case 'ends-with':
            return Helper::endsWith($src, $target);
        }

        return false;
    }

    /**
     * Filter by page URL.
     *
     * @param array $pageProfile
     * @param mixed $inputs
     *
     * @return bool
     */
    public function filterPageUrlGetParameter($pageProfile, $inputs = null)
    {
        if (! ( $referer = $pageProfile['url'] ) ) {
            return true;
        }

        $domain = isset( $inputs['domain'] ) ? $inputs['domain'] : '';
        if ( $domain && ! Helper::endsWith($domain, Helper::getRefererDomain($referer))) {
            return false;
        }

        if (! $parameter = Helper::get($inputs, 'parameter')) {
            return true;
        }

        $value = Helper::get($inputs, 'value');
        $operator = Helper::get($inputs, 'operator', 'equals');

        $referer = parse_url($referer);
        $query = Helper::get($referer, 'query', '');

        parse_str($query, $query);

        $paramValue = Helper::get($query, $parameter, '');

        return $this->compare($paramValue, $value, $operator);
    }

    /**
     * Filter by WordPress post type
     *
     * @param array $pageProfile
     * @param array   $inputs
     *
     * @return bool
     */
    public function filterWordpressPostType($pageProfile, $inputs = null)
    {
        if (! $this->isInDomain($pageProfile['url'], Helper::get($inputs, 'domain'))) {
            return false;
        }

        $value = Helper::get($inputs, 'value');
        $operator = Helper::get($inputs, 'operator', 'is');

        $currentPostType = $pageProfile['type'];
        return $this->compare($currentPostType, $value, $operator);
    }

    /**
     * Filter by WordPress post category
     *
     * @param array $pageProfile
     * @param array   $inputs
     *
     * @return bool
     */
    public function filterWordpressPostCategory($pageProfile, $inputs = null)
    {
        if (! $this->isInDomain($pageProfile['url'], Helper::get($inputs, 'domain'))) {
            return false;
        }

        $value = Helper::get($inputs, 'value');
        $operator = Helper::get($inputs, 'operator', 'is');

        $currentPostCategories = $pageProfile['categories'];
        $within = in_array($value, $currentPostCategories);

        return $operator == 'is' ? $within : !$within;
    }

    /**
     * Filter by WordPress post tag
     *
     * @param array $pageProfile
     * @param array   $inputs
     *
     * @return bool
     */
    public function filterWordpressPostTag($pageProfile, $inputs = null)
    {
        if (! $this->isInDomain($pageProfile['url'], Helper::get($inputs, 'domain'))) {
            return false;
        }

        $value = Helper::get($inputs, 'value');
        $operator = Helper::get($inputs, 'operator', 'is');

        $currentPostTags = $pageProfile['tags'];
        $within = in_array($value, $currentPostTags);

        return $operator == 'is' ? $within : !$within;
    }

	/**
	 * Get current page profile
	 *
	 * @return array
	 */
	public static function getCurrentPageProfile()
	{
		global $wp;

		$pobj = get_queried_object();
		$postType = get_post_type();
		
		if ( empty( $postType ) ) {
			$postType = "";
		}

		$categories = array();
		$tags = array();

		if ( isset( $pobj->ID ) ) {
			$categories = array_map( function ( $category ) {
				return $category->slug;
			}, get_the_category( $pobj->ID ) );
			
			$allTags = get_the_tags( $pobj->ID );
			if ( is_array( $allTags ) ) {
				$tags = array_map( function ( $tag ) {
					return $tag->slug;
				}, $allTags );
			}
		}

		return array(
			'url' => home_url( add_query_arg( array(), $wp->request ) ),
			'type' => $postType,
			'categories' => $categories,
			'tags' => $tags,
		);
	}
}