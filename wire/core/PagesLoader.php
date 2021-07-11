<?php namespace ProcessWire;

/**
 * ProcessWire Pages Loader
 * 
 * Implements page finding/loading methods of the $pages API variable
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 *
 */

class PagesLoader extends Wire {
	
	/**
	 * Controls the outputFormatting state for pages that are loaded
	 *
	 */
	protected $outputFormatting = false;

	/**
	 * Autojoin allowed?
	 *
	 * @var bool
	 *
	 */
	protected $autojoin = true;
	
	/**
	 * @var Pages
	 * 
	 */
	protected $pages;

	/**
	 * Columns native to pages table
	 * 
	 * @var array
	 * 
	 */
	protected $nativeColumns = array();

	/**
	 * Total number of pages loaded by getById()
	 * 
	 * @var int
	 * 
	 */
	protected $totalPagesLoaded = 0;

	/**
	 * Last used instance of PageFinder
	 * 
	 * @var PageFinder|null
	 * 
	 */
	protected $lastPageFinder = null;

	/**
	 * Debug mode for pages class
	 * 
	 * @var bool
	 * 
	 */
	protected $debug = false;

	/**
	 * Page instance ID
	 * 
	 * @var int
	 * 
	 */
	static protected $pageInstanceID = 0;

	/**
	 * Construct
	 * 
	 * @param Pages $pages
	 * 
	 */
	public function __construct(Pages $pages) {
		$this->pages = $pages;
		$this->debug = $pages->debug();
	}
	
	/**
	 * Set whether loaded pages have their outputFormatting turn on or off
	 *
	 * By default, it is turned on.
	 *
	 * @param bool $outputFormatting
	 *
	 */
	public function setOutputFormatting($outputFormatting = true) {
		$this->outputFormatting = $outputFormatting ? true : false;
	}
	
	public function getOutputFormatting() {
		return $this->outputFormatting;
	}
	
	/**
	 * Enable or disable use of autojoin for all queries
	 *
	 * Default should always be true, and you may use this to turn it off temporarily, but
	 * you should remember to turn it back on
	 *
	 * @param bool $autojoin
	 *
	 */
	public function setAutojoin($autojoin = true) {
		$this->autojoin = $autojoin ? true : false;
	}	
	
	public function getAutojoin() {
		return $this->autojoin;
	}

	/**
	 * Normalize a selector string 
	 * 
	 * @param string $selector
	 * @param bool $convertIDs Normalize to integer ID or array of integer IDs when possible (default=true)
	 * @return array|int|string
	 * 
	 */
	protected function normalizeSelectorString($selector, $convertIDs = true) {
		
		$selector = trim($selector, ', ');

		if(ctype_digit($selector)) {
			// normalize to page ID (int)
			$selector = (int) $selector;

		} else if($selector === '/' || $selector === 'path=/') {
			// normalize selectors that indicate homepage to just be ID 1
			$selector = (int) $this->wire('config')->rootPageID;

		} else if($selector[0] === '/') {
			// if selector begins with a slash, it is referring to a path
			$selector = "path=$selector";
			
		} else if(strpos($selector, ',') === false) {
			// there is just one “key=value” or “value” selector that needs further processing
			if(strpos($selector, 'id=')) {
				if($convertIDs) {
					// string like id=123 or id=123|456|789 converted to int or int-array
					$s = substr($selector, 3); // skip over 'id='
					if(ctype_digit($s)) {
						// id=123
						$selector = (int) $s;
					} else if(strpos($selector, '|') && ctype_digit(str_replace('|', '', $s))) {
						// id=123|456|789
						$a = explode('|', $s);
						foreach($a as $k => $v) $a[$k] = (int) $v;
						$selector = $a;
					}
				}
			} else if(!Selectors::stringHasOperator($selector)) {
				// no operator indicates this is just referring to a page name
				$sanitizer = $this->wire('sanitizer');
				if($sanitizer->pageNameUTF8($selector) === $selector) {
					// sanitized value consistent with a page name
					// optimize selector rather than determining value here
					$selector = 'name=' . $sanitizer->selectorValue($selector);
				}
			}
		}
		
		if(is_int($selector) || ctype_digit("$selector")) {
			// page ID integer
			if($convertIDs) {
				$selector = (int) $selector;
			} else {
				$selector = "id=$selector";
			}
		}

		return $selector;
	}
	
	/**
	 * Normalize a selector 
	 * 
	 * @param string|int|array $selector
	 * @param bool $convertIDs Convert ID-only selectors to integers or arrays of integers?
	 * @return array|int|string
	 * 
	 */
	protected function normalizeSelector($selector, $convertIDs = true) {
		
		if(empty($selector)) return '';
	
		if(is_int($selector)) {
			if(!$convertIDs) $selector = "id=$selector"; 
		} else if(is_string($selector)) {
			$selector = $this->normalizeSelectorString($selector, $convertIDs);
		} else if(is_array($selector)) {
			// array that is not associative, not selector array, and consists of only numbers
			if($this->isIdArray($selector)) {
				if(!$convertIDs) $selector = 'id=' . implode('|', $selector);
			}
		}

		return $selector;
	}

	/**
	 * Is this an array of IDs? Also sanitizes to all integers when true
	 * 
	 * @param array $a
	 * @return bool
	 * 
	 */
	protected function isIdArray(array &$a) {
		if(ctype_digit(implode('', array_keys($a))) && !is_array(reset($a)) && ctype_digit(implode('', $a))) {
			// regular array of page IDs, we delegate that to getById() method, but with access/visibility control
			foreach($a as $k => $v) $a[$k] = (int) $v;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Helper for find() method to attempt to shortcut the find when possible
	 * 
	 * @param string|array|Selectors $selector
	 * @param array $options
	 * @param array $loadOptions
	 * @return bool|Page|PageArray Returns boolean false when no shortcut available
	 * 
	 */
	protected function findShortcut($selector, $options, $loadOptions) {
		
		if(empty($selector)) {
			return $this->pages->newPageArray($loadOptions);
		}
		
		$value = false;
		$filter = empty($options['findAll']);
		$selector = $this->normalizeSelector($selector, true); 
	
		if(is_array($selector)) {
			if($this->isIdArray($selector)) {
				$value = $this->getById($selector, $loadOptions);
				$filter = true;
			}	
				
		} else if(is_int($selector)) {
			// page ID integer
			$value = $this->getById(array($selector), $loadOptions);
		}
	
		if($value) {
			if($filter) {
				$includeMode = isset($options['include']) ? $options['include'] : '';
				$value = $this->filterListable($value, $includeMode, $loadOptions);
			}
			if($this->debug) {
				$this->pages->debugLog('find', $selector . " [optimized]", $value);
			}
		}

		return $value;
	}

	/**
	 * Given a Selector string, return the Page objects that match in a PageArray.
	 *
	 * Non-visible pages are excluded unless an include=hidden|unpublished|all mode is specified in the selector string,
	 * or in the $options array. If 'all' mode is specified, then non-accessible pages (via access control) can also be included.
	 *
	 * @param string|int|array|Selectors $selector Specify selector (standard usage), but can also accept page ID or array of page IDs.
	 * @param array|string $options Optional one or more options that can modify certain behaviors. May be assoc array or key=value string.
	 *	- `findOne` (bool): Apply optimizations for finding a single page.
	 *  - `findAll` (bool): Find all pages with no exclusions (same as include=all option).
	 *  - `findIDs` (bool|int): Makes method return raw array rather than PageArray, specify one of the following:
	 *      • `true` (bool): return array of [ [id, templates_id, parent_id] ] for each page.
	 *      • `1` (int): Return just array of just page IDs, [id, id, id]
	 *      • `2` (int): Return all pages table columns in associative array for each page (3.0.153+).
	 *      • `3` (int): Same as 2 + dates are unix timestamps + has 'pageArray' key w/blank PageArray for pagination info (3.0.172+).
	 *      • `4` (int): Same as 3 + return PageArray instead if one is available in cache (3.0.172+).
	 *	- `getTotal` (bool): Whether to set returning PageArray's "total" property (default: true except when findOne=true)
	 *  - `cache` (bool): Allow caching of selectors and pages loaded (default=true). Also sets loadOptions[cache]. 
	 *  - `allowCustom` (bool): Whether to allow use of "_custom=new selector" in selectors (default=false). 
	 *  - `lazy` (bool): Makes find() return Page objects that don't have any data populated to them (other than id and template). 
	 *	- `loadPages` (bool): Whether to populate the returned PageArray with found pages (default: true).
	 *	   The only reason why you'd want to change this to false would be if you only needed the count details from
	 *	   the PageArray: getTotal(), getStart(), getLimit, etc. This is intended as an optimization for Pages::count().
	 * 	   Does not apply if $selectorString argument is an array.
	 *  - `caller` (string): Name of calling function, for debugging purposes, i.e. pages.count
	 * 	- `include` (string): Inclusion mode of 'hidden', 'unpublished' or 'all'. Default=none. Typically you would specify this
	 * 	   directly in the selector string, so the option is mainly useful if your first argument is not a string.
	 *  - `stopBeforeID` (int): Stop loading pages once page matching this ID is found (default=0).
	 *  - `startAfterID` (int): Start loading pages once page matching this ID is found (default=0).
	 * 	- `loadOptions` (array): Assoc array of options to pass to getById() load options. (does not apply when 'findIds' > 3). 
	 *  - `joinFields` (array): Names of fields to autojoin, or empty array to join none; overrides field autojoin settings (default=null) 3.0.172+
	 * @return PageArray|array
	 *
	 */
	public function find($selector, $options = array()) {

		if(is_string($options)) $options = Selectors::keyValueStringToArray($options);

		$loadOptions = isset($options['loadOptions']) && is_array($options['loadOptions']) ? $options['loadOptions'] : array();
		$loadPages = array_key_exists('loadPages', $options) ? (bool) $options['loadPages'] : true; 
		$caller = isset($options['caller']) ? $options['caller'] : 'pages.find';
		$lazy = empty($options['lazy']) ? false : true;
		$findIDs = isset($options['findIDs']) ? $options['findIDs'] : false;
		$debug = $this->debug && !$lazy;
		$allowShortcuts = $loadPages && !$lazy && (!$findIDs || $findIDs === 4); 
		$joinFields = isset($options['joinFields']) ? $options['joinFields'] : array();
		$cachePages = isset($options['cache']) ? (bool) $options['cache'] : true;
		if(!$cachePages && !isset($loadOptions['cache'])) $loadOptions['cache'] = false;
		
		if($allowShortcuts) {
			$pages = $this->findShortcut($selector, $options, $loadOptions);
			if($pages) return $pages;
		}
		
		if($selector instanceof Selectors) {
			$selectors = $selector;
		} else {
			$selector = $this->normalizeSelector($selector, false); 
			$selectors = $this->wire(new Selectors());
			$selectors->init($selector);
		}
		
		if(isset($options['include']) && in_array($options['include'], array('hidden', 'unpublished', 'all'))) {
			$selectors->add(new SelectorEqual('include', $options['include']));
		}

		$selectorString = is_string($selector) ? $selector : (string) $selectors;

		// check whether the joinFields option will be used
		if(!$lazy && !$findIDs) {
			$fields = $this->wire()->fields;
			// support the joinFields option when selector contains 'field=a|b|c' or 'join=a|b|c'
			foreach(array('field', 'join') as $name) {
				if(strpos($selectorString, "$name=") === false || $fields->get($name)) continue; 
				foreach($selectors as $selector) {
					if($selector->field() !== $name) continue;
					$joinFields = array_merge($joinFields, $selector->values());
					$selectors->remove($selector);
				}
			}
			if(count($joinFields)) {
				unset($options['include']); // because it was moved into $selectors earlier
				return $this->findMin($selectors, array_merge($options, array('joinFields' => $joinFields)));
			}
		} 
		
		// see if this has been cached and return it if so
		if($allowShortcuts) {
			$pages = $this->pages->cacher()->getSelectorCache($selectorString, $options);
			if($pages !== null) {
				if($debug) $this->pages->debugLog('find', $selectorString, $pages . ' [from-cache]');
				return $pages;
			}
		}
		
		$pageFinder = $this->pages->getPageFinder();
		$pagesInfo = array();
		$pagesIDs = array();
	
		if($debug) Debug::timer("$caller($selectorString)", true);
		$profiler = $this->wire('profiler');
		$profilerEvent = $profiler ? $profiler->start("$caller($selectorString)", "Pages") : null;
		
		if(($lazy || $findIDs) && strpos($selectorString, 'limit=') === false) $options['getTotal'] = false;
	
		if($lazy) {
			// [ pageID => templateID ]
			$pagesIDs = $pageFinder->findTemplateIDs($selectors, $options); 
			
		} else if($findIDs === 1) {
			// [ pageID ]
			$pagesIDs = $pageFinder->findIDs($selectors, $options);
			
		} else if($findIDs === 2) {
			// [ pageID => [ all pages columns ] ]
			$pagesInfo = $pageFinder->findVerboseIDs($selectors, $options);
			
		} else if($findIDs === 3 || $findIDs === 4) {
			// [ pageID => [ all pages columns + sortfield + dates as unix timestamps ],
			// 'pageArray' => PageArray(blank but with pagination info populated) ] ]
			$options['joinSortfield'] = true;
			$options['getNumChildren'] = true;
			$options['unixTimestamps'] = true;
			$pagesInfo = $pageFinder->findVerboseIDs($selectors, $options);
			
		} else {
			// [ [ 'id' => 3, 'templates_id' => 2, 'parent_id' => 1, 'score' => 1.123 ]
			$pagesInfo = $pageFinder->find($selectors, $options);
		}
		
		if($debug && empty($loadOptions['caller'])) {
			$loadOptions['caller'] = "$caller($selectorString)";
		}

		// note that we save this pagination state here and set it at the end of this method
		// because it's possible that more find operations could be executed as the pages are loaded
		$total = $pageFinder->getTotal();
		$limit = $pageFinder->getLimit();
		$start = $pageFinder->getStart();
		
		if($lazy) {
			// lazy load: create empty pages containing only id and template
			$pages = $this->pages->newPageArray($loadOptions);
			$pages->finderOptions($options);
			$pages->setDuplicateChecking(false);
			$loadPages = false;
			$cachePages = false;
			$template = null;
			$templatesByID = array();

			foreach($pagesIDs as $id => $templateID) {
				if(isset($templatesByID[$templateID])) {
					$template = $templatesByID[$templateID];
				} else {
					$template = $this->wire('templates')->get($templateID);
					$templatesByID[$templateID] = $template;
				}
				$page = $this->pages->newPage($template);
				$page->_lazy($id);
				$page->loaderCache = false;
				$pages->add($page);
			}

			$pages->setDuplicateChecking(true);
			if(count($pagesIDs)) $pages->_lazy(true);
			unset($template, $templatesByID);

		} else if($findIDs) {
			
			$loadPages = false;
			$cachePages = false;
			// PageArray for hooks or for findIDs==3 option
			$pages = $this->pages->newPageArray($loadOptions); 

		} else if($loadPages) {
			// parent_id is null unless a single parent was specified in the selectors
			$parent_id = $pageFinder->getParentID();
			$idsSorted = array();
			$idsByTemplate = array();
			$scores = array();

			// organize the pages by template ID
			foreach($pagesInfo as $page) {
				$tpl_id = (int) $page['templates_id'];
				$id = (int) $page['id'];
				if(!isset($idsByTemplate[$tpl_id])) $idsByTemplate[$tpl_id] = array();
				$idsByTemplate[$tpl_id][] = $id;
				$idsSorted[] = $id;
				if(!empty($page['score'])) $scores[$id] = (float) $page['score'];
			}

			if(count($idsByTemplate) > 1) {
				// perform a load for each template, which results in unsorted pages
				// @todo use $idsUnsorted array rather than $unsortedPages PageArray
				$unsortedPages = $this->pages->newPageArray($loadOptions);
				foreach($idsByTemplate as $tpl_id => $ids) {
					$opt = $loadOptions;
					$opt['template'] = $this->wire('templates')->get($tpl_id);
					$opt['parent_id'] = $parent_id;
					$unsortedPages->import($this->getById($ids, $opt));
				}

				// put pages back in the order that the selectorEngine returned them in, while double checking that the selector matches
				$pages = $this->pages->newPageArray($loadOptions);
				foreach($idsSorted as $id) {
					foreach($unsortedPages as $page) {
						if($page->id == $id) {
							$pages->add($page);
							break;
						}
					}
				}
			} else {
				// there is only one template used, so no resorting is necessary	
				$pages = $this->pages->newPageArray($loadOptions);
				reset($idsByTemplate);
				$opt = $loadOptions;
				$opt['template'] = $this->wire('templates')->get(key($idsByTemplate));
				$opt['parent_id'] = $parent_id;
				$pages->import($this->getById($idsSorted, $opt));
			}
			
			$sortsAfter = $pageFinder->getSortsAfter();
			if(count($sortsAfter)) $pages->sort($sortsAfter);
			
			if(count($scores)) {
				foreach($pages as $page) {
					$score = isset($scores[$page->id]) ? $scores[$page->id] : 0; 
					$page->setQuietly('_pfscore', $score); 
				}
			}

		} else {
			$pages = $this->pages->newPageArray($loadOptions);
		}

		$pageFinder->getPageArrayData($pages); 
		$pages->setTotal($total);
		$pages->setLimit($limit);
		$pages->setStart($start);
		$pages->setSelectors($selectorString);
		$pages->setTrackChanges(true);
		$this->lastPageFinder = $pageFinder; 

		if($loadPages && $cachePages) {
			if(strpos($selectorString, 'sort=random') !== false) {
				if($selectors->getSelectorByFieldValue('sort', 'random')) $cachePages = false;
			}
			if($cachePages) {
				$this->pages->cacher()->selectorCache($selectorString, $options, $pages);
			}
		}

		if($debug) {
			$this->pages->debugLog('find', $selectorString, $pages);
			$count = $pages->count();
			$note = ($count == $total ? $count : $count . "/$total") . " page(s)";
			if($count) {
				$note .= ": " . $pages->first()->path;
				if($count > 1) $note .= " ... " . $pages->last()->path;
			}
			if(substr($caller, -1) !== ')') $caller .= "($selectorString)";
			Debug::saveTimer($caller, $note);
			foreach($pages as $item) {
				if($item->_debug_loader) continue;
				$item->setQuietly('_debug_loader', $caller);
			}
		}
		
		if($profilerEvent) $profiler->stop($profilerEvent);

		if($this->pages->hasHook('found()')) $this->pages->found($pages, array(
			'pageFinder' => $pageFinder,
			'pagesInfo' => $pagesInfo,
			'options' => $options
		));
		
		if($findIDs) {
			if($findIDs === 3 || $findIDs === 4) $pagesInfo['pageArray'] = $pages;
			return $findIDs === 1 ? $pagesIDs : $pagesInfo;
		}

		return $pages;
	}

	/**
	 * Minimal find for reduced or delayed overload in some circumstances
	 * 
	 * This combines the page finding and page loading operation into a single operation
	 * and single query, unlike a regular find() which finds matching page IDs in one 
	 * query and then loads them in a separate query. As a result this method does not
	 * need to call the getByIds() method to load pages, as it is able to load them itself. 
	 * 
	 * This strategy may eventually replace the “find() + getByIds()” strategy, but for the
	 * moment is only used when the `$pages->find()` method specifies `field=name` in 
	 * the selector. In that selector, `name` can be any field name, or group of them, i.e.
	 * `title|date|summary`, or a non-existing field like `none` to specify that no fields 
	 * should be autojoin (for fastest performance). 
	 * 
	 * Note that while this might reduce overhead in some cases, it can also increase the 
	 * overall request time if you omit fields that are actually used on the resulting pages.
	 * For instance, if the `title` field is an autojoin field (as it is by default), and 
	 * we do a `$pages->find('template=blog-post, field=none');` and then render a list of
	 * blog post titles, then we have just increased overhead because PW would have to 
	 * perform a separate query to load each blog-post page’s title. On the other hand, if 
	 * we render a list of blog post titles with date and summary, and the date and summary 
	 * fields are not configured as autojoin fields, then we can specify all those that we 
	 * use in our rendered list to greatly improve performance, like this: 
	 * `$pages->find('template=blog-post, field=title|date|summary');`.
	 * 
	 * While this method combines what find() and getById() do in one query, there does not
	 * appear to be any overhead benefit when the two strategies are dealing with identical
	 * conditions, like the same autojoin fields. 
	 * 
	 * @param string|array|Selectors $selector
	 * @param array $options
	 *  - `cache` (bool): Allow pulling from and saving results to cache? (default=true)
	 *  - `joinFields` (array): Names of fields to also join into the page load
	 * @return PageArray
	 * @throws WireException
	 * @since 3.0.172
	 * 
	 */
	public function findMin($selector, array $options = array()) {

		$useCache = isset($options['cache']) ? $options['cache'] : true;
		$templates = $this->wire()->templates;
		$languages = $this->wire()->languages;
		$languageIds = array();
		$templatesById = array();
		
		if($languages) foreach($languages as $language) $languageIds[$language->id] = $language->id;
		
		$options['findIDs'] = $useCache ? 4 : 3;
		$joinFields = isset($options['joinFields']) ? $options['joinFields'] : array();
		$rows = $this->find($selector, $options);
		
		// if PageArray was already available in cache, return it now
		if($rows instanceof PageArray) return $rows;
	
		/** @var PageArray $pageArray */
		$pageArray = $rows['pageArray'];
		$pageArray->setTrackChanges(false);
		$paginationTotal = $pageArray->getTotal();
		unset($rows['pageArray']);

		foreach($rows as $row) {
			
			$page = $useCache ? $this->pages->getCache($row['id']) : null;
			$tid = (int) $row['templates_id'];
			
			if($page) {
				$pageArray->add($page);
				continue;
			}
		
			if(isset($templatesById[$tid])) {
				$template = $templatesById[$tid]; 
			} else {
				$template = $templates->get($tid);
				if(!$template) continue;
				$templatesById[$tid] = $template;
			}
			
			$sortfield = $template->sortfield;
			if(empty($sortfield) && isset($row['sortfield'])) $sortfield = $row['sortfield'];
			
			$set = array(
				'pageClass' => $template->getPageClass(),
				'isLoaded' => false,
				'id' => $row['id'],
				'template' => $template,
				'parent_id' => $row['parent_id'],
				'sortfield' => $sortfield,
			);
		
			unset($row['templates_id'], $row['parent_id'], $row['id'], $row['sortfield']);
			
			$page = $this->pages->newPage($set);
			$page->instanceID = ++self::$pageInstanceID;
			
			if($languages) {
				foreach($languageIds as $id) {
					$key = "name$id";
					if(isset($row[$key]) && strpos($row[$key], 'xn-') === 0) {
						$page->setName($row[$key], $key);
						unset($row[$key]);
					}
				}
			}

			foreach($row as $key => $value) {
				if(strpos($key, '__')) {
					$page->setFieldValue($key, $value, false);
				} else {
					$page->setForced($key, $value);
				}
			}

			// set blank values where joinField didn't appear on page row 
			foreach($joinFields as $joinField) {
				if(isset($row["{$joinField}__data"])) continue;
				if(!$template->fieldgroup->hasField($joinField)) continue;
				$field = $page->getField($joinField);
				if(!$field || !$field->type) continue;
				$blankValue = $field->type->getBlankValue($page, $field);
				$page->setFieldValue($field->name, $blankValue, false);
			}

			$page->setIsLoaded(true);
			$page->setIsNew(false);
			$page->resetTrackChanges(true);
			$page->setOutputFormatting($this->outputFormatting);
			$this->totalPagesLoaded++;

			$pageArray->add($page);
			
			if($useCache) $this->pages->cache($page);
		}

		$pageArray->setTotal($paginationTotal);
		$pageArray->resetTrackChanges(true);
		
		if($useCache) {
			$selectorString = $pageArray->getSelectors(true);
			$this->pages->cacher()->selectorCache($selectorString, $options, $pageArray);
		}

		return $pageArray;
	}


	/**
	 * Like find() but returns only the first match as a Page object (not PageArray)
	 *
	 * This is functionally similar to the get() method except that its default behavior is to
	 * filter for access control and hidden/unpublished/etc. states, in the same way that the
	 * find() method does. You can add an `include=` to your selector with value `hidden`, 
	 * `unpublished` or `all` to change this behavior, just like with find(). 
	 * 
	 * Unlike the find() method, this method performs a secondary runtime access check by calling 
	 * `$page->viewable()` with the found $page, and returns a `NullPage` if the page is not
	 * viewable with that call. In 3.0.142+, an `include=` mode of `all` or `unpublished` will 
	 * override this, where appropriate.
	 * 
	 * This method also accepts an `$options` array, whereas `Pages::get()` does not.
	 *
	 * @param string|int|array|Selectors $selector
	 * @param array|string $options See $options for `Pages::find`
	 * @return Page|NullPage
	 *
	 */
	public function findOne($selector, $options = array()) {
		
		if(empty($selector)) return $this->pages->newNullPage();
		if(is_string($options)) $options = Selectors::keyValueStringToArray($options);
		
		$defaults = array(
			'findOne' => true, // find only one page
			'getTotal' => false, // don't count totals
			'caller' => 'pages.findOne'
		);
		
		$options = array_merge($defaults, $options);
		$items = $this->pages->find($selector, $options);
		$page = $items->first();
		
		if($page && !$page->viewable(false)) {
			// page found but is not viewable, check if include mode was specified and would allow the page
			$selectors = $items->getSelectors();
			$include = $selectors ? $selectors->getSelectorByField('include') : null;
			if(!$include) {
				// there was no “include=” selector present
				$page = null;
			} else if($include->value() === 'all') {
				// allow $page to pass through with include=all mode
			} else if($include->value() === 'unpublished' && $page->hasStatus(Page::statusUnpublished)) {
				// check if user would have access without unpublished status
				$status = $page->status;
				$page->setQuietly('status', $status & ~Page::statusUnpublished);
				$viewable = $page->viewable(false);
				$page->setQuietly('status', $status); // restore
				if(!$viewable) $page = null;
			} else {
				$page = null;
			}
		}

		return $page && $page->id ? $page : $this->pages->newNullPage();
	}
	
	/**
	 * Returns the first page matching the given selector with no exclusions
	 *
	 * @param string|int|array|Selectors $selector
	 * @param array $options See Pages::find method for options
	 * @return Page|NullPage Always returns a Page object, but will return NullPage (with id=0) when no match found
	 *
	 */
	public function get($selector, $options = array()) {
		
		if(empty($selector)) return $this->pages->newNullPage();
		
		if(is_int($selector)) {
			$getCache = true;
		} else if(is_string($selector) && (ctype_digit($selector) || strpos($selector, 'id=') === 0)) {
			$getCache = true;
		} else {
			$getCache = false;
		}
		
		if($getCache) {
			// if cache is possible, allow user-specified options to dictate whether cache is allowed
			if(isset($options['loadOptions']) && isset($options['loadOptions']['getFromCache'])) {
				$getCache = (bool) $options['loadOptions']['getFromCache'];
			}
			if($getCache) {
				$page = $this->pages->getCache($selector); // selector is either 123 or id=123
				if($page) return $page;
			}
		}
		
		$defaults = array(
			'findOne' => true, // find only one page
			'findAll' => true, // no exclusions
			'getTotal' => false, // don't count totals
			'caller' => 'pages.get'
		);
		
		$options = count($options) ? array_merge($defaults, $options) : $defaults;
		$page = $this->pages->find($selector, $options)->first();
		if(!$page) $page = $this->pages->newNullPage();
		
		return $page;
	}

	/**
	 * Is there any page that matches the given $selector in the system? (with no exclusions)
	 *
	 * - This can be used as an “exists” or “getID” type of method.
	 * - Returns ID of first matching page if any exist, or 0 if none exist (returns array if `$verbose` is true).
	 * - Like with the `get()` method, no pages are excluded, so an `include=all` is not necessary in selector.
	 * - If you need to quickly check if something exists, this method is preferable to using a count() or get().
	 *
	 * When `$verbose` option is used, an array is returned instead. Verbose return array includes all columns
	 * from the matching row in the pages table. 
	 * 
	 * @param string|int|array|Selectors $selector
	 * @param bool $verbose Return verbose array with all pages columns rather than just page id? (default=false)
	 * @param array $options Additional options to pass in find() $options argument (not currently applicable)
	 * @return array|int
	 * @since 3.0.153
	 * 
	 */
	public function has($selector, $verbose = false, array $options = array()) {
	
		$defaults = array(
			'findOne' => true, // find only one page
			'findAll' => true, // no exclusions
			'findIDs' => $verbose ? 2 : 1, // 2=all cols, 1=IDs only
			'getTotal' => false, // don't count totals
			'caller' => 'pages.has',
		);

		$options = count($options) ? array_merge($defaults, $options) : $defaults;
		if(empty($selector)) return $verbose ? array() : 0;

		if((is_string($selector) || is_int($selector)) && !$verbose) {
			// see if any matching page is already in the cache
			$page = $this->pages->getCache($selector);
			if($page) return $page->id;
		}
		
		$items = $this->pages->find($selector, $options);
		
		if($verbose) {
			$value = count($items) ? reset($items) : array();
		} else {
			$value = count($items) ? (int) reset($items) : 0;
		}
		
		return $value; 
	}
	
	/**
	 * Given an array or CSV string of Page IDs, return a PageArray
	 *
	 * Optionally specify an $options array rather than a template for argument 2. When present, the 'template' and 'parent_id' arguments may be provided
	 * in the given $options array. These options may be specified:
	 *
	 * LOAD OPTIONS (argument 2 array):
	 * - cache: boolean, default=true. place loaded pages in memory cache?
	 * - getFromCache: boolean, default=true. Allow use of previously cached pages in memory (rather than re-loading it from DB)?
	 * - template: instance of Template (see $template argument)
	 * - parent_id: integer (see $parent_id argument)
	 * - getNumChildren: boolean, default=true. Specify false to disable retrieval and population of 'numChildren' Page property.
	 * - getOne: boolean, default=false. Specify true to return just one Page object, rather than a PageArray.
	 * - autojoin: boolean, default=true. Allow use of autojoin option?
	 * - joinFields: array, default=empty. Autojoin the field names specified in this array, regardless of field settings (requires autojoin=true).
	 * - joinSortfield: boolean, default=true. Whether the 'sortfield' property will be joined to the page.
	 * - findTemplates: boolean, default=true. Determine which templates will be used (when no template specified) for more specific autojoins.
	 * - pageClass: string, default=auto-detect. Class to instantiate Page objects with. Leave blank to determine from template.
	 * - pageArrayClass: string, default=PageArray. PageArray-derived class to store pages in (when 'getOne' is false).
	 * - pageArray: PageArray, default=null. Optional predefined PageArray to populate to. 
	 * - page (Page|null): Existing Page object to populate (also requires the getOne option to be true). (default=null)
	 * - caller (string): Name of calling function, for debugging purposes (default=blank).
	 *
	 * Use the $options array for potential speed optimizations:
	 * - Specify a 'template' with your call, when possible, so that this method doesn't have to determine it separately.
	 * - Specify false for 'getNumChildren' for potential speed optimization when you know for certain pages will not have children.
	 * - Specify false for 'autojoin' for potential speed optimization in certain scenarios (can also be a bottleneck, so be sure to test).
	 * - Specify false for 'joinSortfield' for potential speed optimization when you know the Page will not have children or won't need to know the order.
	 * - Specify false for 'findTemplates' so this method doesn't have to look them up. Potential speed optimization if you have few autojoin fields globally.
	 * - Note that if you specify false for 'findTemplates' the pageClass is assumed to be 'Page' unless you specify something different for the 'pageClass' option.
	 *
	 * @param array|WireArray|string|int $_ids Array of page IDs, comma or pipe-separated string of IDs, or single page ID (string or int)
	 *  or in 3.0.156+ array of associative arrays where each in format: [ 'id' => 123, 'templates_id' => 456 ]
	 * @param Template|array|null $template Specify a template to make the load faster, because it won't have to attempt to join all possible fields... just those used by the template.
	 *	Optionally specify an $options array instead, see the method notes above.
	 * @param int|null $parent_id Specify a parent to make the load faster, as it reduces the possibility for full table scans.
	 *	This argument is ignored when an options array is supplied for the $template.
	 * @return PageArray|Page Returns Page only if the 'getOne' option is specified, otherwise always returns a PageArray.
	 * @throws WireException
	 *
	 */
	public function getById($_ids, $template = null, $parent_id = null) {

		$options = array(
			'cache' => true,
			'getFromCache' => true,
			'template' => null,
			'parent_id' => null,
			'getNumChildren' => true,
			'getOne' => false,
			'autojoin' => true,
			'findTemplates' => true,
			'joinSortfield' => true,
			'joinFields' => array(),
			'page' => null, 
			'pageClass' => '',  // blank = auto detect
			'pageArray' => null, // PageArray to populate to
			'pageArrayClass' => 'PageArray',
			'caller' => '', 
		);
	
		/** @var Templates $templates */
		$templates = $this->wire('templates');
		/** @var WireDatabasePDO $database */
		$database = $this->wire('database');
		$idsByTemplate = array();

		if(is_array($template)) {
			// $template property specifies an array of options
			$options = array_merge($options, $template);
			$template = $options['template'];
			$parent_id = $options['parent_id'];
		} else if(!is_null($template) && !$template instanceof Template) {
			throw new WireException('getById argument 2 must be Template or $options array');
		}

		if(!is_null($parent_id) && !is_int($parent_id)) {
			// convert Page object or string to integer id
			$parent_id = (int) ((string) $parent_id);
		}

		if(!is_null($template) && !is_object($template)) {
			// convert template string or id to Template object
			$template = $templates->get($template);
		}

		if(is_string($_ids)) {
			// convert string of IDs to array
			$_ids = trim($_ids, '|, ');
			if(ctype_digit($_ids)) {
				$_ids = array((int) $_ids); // single ID: "123"
			} else if(strpos($_ids, '|')) {
				$_ids = explode('|', $_ids); // pipe-separated IDs: "123|456|789"
			} else if(strpos($_ids, ',')) {
				$_ids = explode(',', $_ids); // comma-separated IDs: "123,456,789"
			} else {
				$_ids = array(); // unrecognized ID string: fail
			}
		} else if(is_int($_ids)) {
			$_ids = array($_ids);
		}

		if(!WireArray::iterable($_ids) || !count($_ids)) {
			// return blank if $_ids isn't iterable or is empty
			return $options['getOne'] ? $this->pages->newNullPage() : $this->pages->newPageArray($options);
		}

		if(is_object($_ids)) $_ids = $_ids->getArray(); // ArrayObject or the like

		$loaded = array(); // array of id => Page objects that have been loaded
		$ids = array(); // sanitized version of $_ids

		// sanitize ids and determine which pages we can pull from cache
		foreach($_ids as $key => $id) {
			
			if(!is_int($id)) {
				if(is_array($id)) {
					if(!isset($id['id'])) continue;
					$tid = isset($id['templates_id']) ? (int) $id['templates_id'] : 0;
					$id = (int) $id['id'];
					if($tid) {
						if(!isset($idsByTemplate[$tid])) $idsByTemplate[$tid] = array();
						$idsByTemplate[$tid][] = $id;
					}
				} else {
					$id = trim($id);
					if(!ctype_digit($id)) continue;
					$id = (int) $id;
				}
			}
			
			if($id < 1) continue;
			
			$key = (int) $key;
			
			if($options['getOne'] && is_object($options['page'])) {
				// single page that will be populated directly
				$loaded[$id] = ''; 
				$ids[$key] = $id;

			} else if($options['getFromCache'] && $page = $this->pages->getCache($id)) {
				// page is already available in the cache	
				if($template && $page->template->id != $template->id) {
					// do not load: does not match specified template
				} else if($parent_id && $page->parent_id != $parent_id) {
					// do not load: does not match specified parent_id
				} else {
					$loaded[$id] = $page;
				}

			} else if(isset(Page::$loadingStack[$id])) {
				// if the page is already in the process of being loaded, point to it rather than attempting to load again.
				// the point of this is to avoid a possible infinite loop with autojoin fields referencing each other.
				$p = Page::$loadingStack[$id];
				if($p) {
					$loaded[$id] = $p;
					// cache the pre-loaded version so that other pages referencing it point to this instance rather than loading again
					$this->pages->cache($loaded[$id]);
				}

			} else {
				$loaded[$id] = ''; // reserve the spot, in this order
				$ids[$key] = $id; // queue id to be loaded
			}
		}

		$idCnt = count($ids); // idCnt contains quantity of remaining page ids to load
		if(!$idCnt) {
			// if there are no more pages left to load, we can return what we've got
			if($options['getOne']) return count($loaded) ? reset($loaded) : $this->pages->newNullPage();
			$pages = $this->pages->newPageArray($options);
			$pages->setDuplicateChecking(false);
			$pages->import($loaded);
			$pages->setDuplicateChecking(true);
			return $pages;
		}


		if(count($idsByTemplate)) {
			// ok
		} else if($template === null && $options['findTemplates']) {

			// template was not defined with the function call, so we determine
			// which templates are used by each of the pages we have to load

			$sql = 'SELECT id, templates_id FROM pages';
			if($idCnt == 1) {
				$query = $database->prepare("$sql WHERE id=:id");
				$query->bindValue(':id', (int) reset($ids), \PDO::PARAM_INT); 
			} else {
				$ids = array_map('intval', $ids);
				$sql = "$sql WHERE id IN(" . implode(',', $ids) . ")";
				$query = $database->prepare($sql);
			}

			$result = $database->execute($query);
			if($result) {
				/** @noinspection PhpAssignmentInConditionInspection */
				while($row = $query->fetch(\PDO::FETCH_NUM)) {
					list($id, $templates_id) = $row;
					$id = (int) $id;
					$templates_id = (int) $templates_id;
					if(!isset($idsByTemplate[$templates_id])) $idsByTemplate[$templates_id] = array();
					$idsByTemplate[$templates_id][] = $id;
				}
			}
			$query->closeCursor();

		} else if($template === null) {
			// no template provided, and autojoin not needed (so we don't need to know template)
			$idsByTemplate = array(0 => $ids);

		} else {
			// template was provided
			$idsByTemplate = array($template->id => $ids);
		}

		foreach($idsByTemplate as $templates_id => $ids) {

			if($templates_id && (!$template || $template->id != $templates_id)) {
				$template = $templates->get($templates_id);
			}

			if($template) {
				$fields = $template->fieldgroup;
			} else {
				$fields = $this->wire('fields');
			}

			/** @var DatabaseQuerySelect $query */
			$query = $this->wire(new DatabaseQuerySelect());
			$sortfield = $template ? $template->sortfield : '';
			$joinSortfield = empty($sortfield) && $options['joinSortfield'];
			
			// note that "false AS isLoaded" triggers the setIsLoaded() function in Page intentionally
			$select = 'false AS isLoaded, pages.templates_id AS templates_id, pages.*, ';
			if($joinSortfield) {
				$select .= 'pages_sortfields.sortfield, ';
			}
			if($options['getNumChildren']) {
				$select .= "\n(SELECT COUNT(*) FROM pages AS children WHERE children.parent_id=pages.id) AS numChildren";
			}

			$query->select(rtrim($select, ', '));
			$query->from('pages');
			if($joinSortfield) $query->leftjoin('pages_sortfields ON pages_sortfields.pages_id=pages.id');

			if($options['autojoin'] && $this->autojoin) {
				foreach($fields as $field) {
					if(!empty($options['joinFields']) && in_array($field->name, $options['joinFields'])) {
						// joinFields option specified to force autojoin this field
					} else {
						// check if autojoin not enabled for field
						if(!($field->flags & Field::flagAutojoin)) continue; 
						// non-fieldgroup, autojoin only if global flag is set
						if($fields instanceof Fields && !($field->flags & Field::flagGlobal)) continue; 
					}
					$table = $database->escapeTable($field->table);
					// check autojoin not allowed, otherwise merge in the autojoin query
					if(!$field->type || !$field->type->getLoadQueryAutojoin($field, $query)) continue; 
					// complete autojoin
					$query->leftjoin("$table ON $table.pages_id=pages.id"); // QA
				}
			}
			
			if(count($ids) > 1) {
				$ids = array_map('intval', $ids);
				$query->where('pages.id IN(' . implode(',', $ids) . ')');
			} else {
				$id = reset($ids);
				$query->where('pages.id=:id');
				$query->bindValue(':id', (int) $id, \PDO::PARAM_INT);
			}

			if(!is_null($parent_id)) {
				$query->where('pages.parent_id=:parent_id');
				$query->bindValue(':parent_id', (int) $parent_id, \PDO::PARAM_INT);
			}
			
			if($template) {
				$query->where('pages.templates_id=:templates_id');
				$query->bindValue(':templates_id', (int) $template->id, \PDO::PARAM_INT);
			}

			$query->groupby('pages.id');
			$stmt = $query->prepare();
			$database->execute($stmt);

			$class = $options['pageClass'];
			if(empty($class)) $class = $template ? $template->getPageClass() : __NAMESPACE__ . "\\Page";

			// page to populate, if provided in 'getOne' mode
			/** @var Page|null $_page */
			$_page = $options['getOne'] && $options['page'] && $options['page'] instanceof Page ? $options['page'] : null;

			try {
				// while($page = $stmt->fetchObject($_class, array($template))) {
				/** @noinspection PhpAssignmentInConditionInspection */
				while($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					if($_page) {
						// populate provided Page object
						$page = $_page;
						$page->set('template', $template ? $template : $row['templates_id']);
					} else {
						// create new Page object
						$pageTemplate = $template ? $template : $templates->get((int) $row['templates_id']); 
						$pageClass = empty($options['pageClass']) && $pageTemplate ? $pageTemplate->getPageClass() : $class; 
						$page = $this->pages->newPage(array(
							'pageClass' => $pageClass,
							'template' => $pageTemplate ? $pageTemplate : $row['templates_id'],
						));
					}
					unset($row['templates_id']);
					foreach($row as $key => $value) $page->set($key, $value);
					if($options['cache'] === false) $page->loaderCache = false;
					$page->instanceID = ++self::$pageInstanceID;
					$page->setIsLoaded(true);
					$page->setIsNew(false);
					$page->resetTrackChanges(true);
					$page->setOutputFormatting($this->outputFormatting);
					$loaded[$page->id] = $page;
					if($options['cache']) $this->pages->cache($page);
					$this->totalPagesLoaded++;
				}
			} catch(\Exception $e) {
				$error = $e->getMessage() . " [pageClass=$class, template=$template]";
				$user = $this->wire('user');
				if($user && $user->isSuperuser()) $this->error($error);
				$this->wire('log')->error($error);
				$this->trackException($e, false);
			}

			$stmt->closeCursor();
			$template = null;
		}

		if($options['getOne']) return count($loaded) ? reset($loaded) : $this->pages->newNullPage();
		$pages = $this->pages->newPageArray($options);
		$pages->setDuplicateChecking(false);
		$pages->import($loaded);
		$pages->setDuplicateChecking(true);

		// debug mode only
		if($this->debug) {
			$page = $this->wire('page');
			if($page && $page->template == 'admin') {
				if(empty($options['caller'])) {
					$_template = is_null($template) ? '' : ", $template";
					$_parent_id = is_null($parent_id) ? '' : ", $parent_id";
					if(count($_ids) > 10) {
						$_ids = '[' . reset($_ids) . '…' . end($_ids) . ', ' . count($_ids) . ' pages]';
					} else {
						$_ids = count($_ids) > 1 ? "[" . implode(',', $_ids) . "]" : implode('', $_ids);
					}
					$options['caller'] = "pages.getById($_ids$_template$_parent_id)";
				}
				foreach($pages as $item) {
					$item->setQuietly('_debug_loader', $options['caller']);
				}
			}
		}

		return $pages;
	}

	/**
	 * Given an ID return a path to a page, without loading the actual page
	 *
	 * Please note
	 * ===========
	 * 1) Always returns path in default language, unless a language argument/option is specified.
	 * 2) Path may be different from 'url' as it doesn't include $config->urls->root at the beginning.
	 * 3) In most cases, it's preferable to use $page->path() rather than this method. This method is
	 *    here just for cases where a path is needed without loading the page.
	 * 4) It's possible for there to be Page::path() hooks, and this method completely bypasses them,
	 *    which is another reason not to use it unless you know such hooks aren't applicable to you.
	 *
	 * @param int|Page $id ID of the page you want the path to
	 * @param null|array|Language|int|string $options Specify $options array or Language object, id or name. Allowed options:
	 *  - language (int|string|anguage): To retrieve in non-default language, specify language object, ID or name (default=null)
	 *  - useCache (bool): Allow pulling paths from already loaded pages? (default=true)
	 *  - usePagePaths (bool): Allow pulling paths from PagePaths module, if installed? (default=true)
	 * @return string Path to page or blank on error/not-found
	 *
	 */
	public function getPath($id, $options = array()) {

		$defaults = array(
			'language' => null,
			'useCache' => true,
			'usePagePaths' => true
		);

		if(!is_array($options)) {
			// language was specified rather than $options
			$defaults['language'] = $options;
			$options = array();
		}

		$options = array_merge($defaults, $options);

		if(is_object($id) && $id instanceof Page) {
			if($options['useCache']) return $id->path();
			$id = $id->id;
		}

		$id = (int) $id;
		if(!$id || $id < 0) return '';

		$languages = $this->wire('languages');
		if($languages && !$this->wire('modules')->isInstalled('LanguageSupportPageNames')) $languages = null;
		$language = $options['language'];
		$languageID = 0;
		$homepageID = (int) $this->wire('config')->rootPageID;

		if(!empty($language) && $languages) {
			if(is_string($language) || is_int($language)) $language = $languages->get($language);
			if(!$language->isDefault()) $languageID = (int) $language->id;
		}

		// if page is already loaded and cache allowed, then get the path from it
		if($options['useCache'] && $page = $this->pages->getCache($id)) {
			/** @var Page $page */
			if($languageID) $languages->setLanguage($language);
			$path = $page->path();
			if($languageID) $languages->unsetLanguage();
			return $path;

		} else if($id === $homepageID && $languages && !$languageID) {
			// default language in multi-language environment, let $page handle it since there is additional 
			// hooked logic there provided by LanguageSupportPageNames
			$page = $this->pages->get($homepageID);
			$languages->setDefault();
			$path = $page->path();
			$languages->unsetDefault();
			return $path;
		}

		// if PagePaths module is installed, and not in multi-language environment, attempt to get from PagePaths module
		if(!$languages && !$languageID && $options['usePagePaths'] && $this->wire('modules')->isInstalled('PagePaths')) {
			/** @var PagePaths $pagePaths */
			$pagePaths = $this->modules->get('PagePaths');
			$path = $pagePaths->getPath($id);
			if($path) return $path;
		} else {
			$pagePaths = null;
		}

		$path = '';
		$templatesID = 0;
		$parentID = $id;
		$database = $this->wire('database');
		$maxParentID = $language ? 0 : 1;
		$cols = 'parent_id, templates_id, name';
		if($languageID) $cols .= ", name$languageID"; // col=3
		$query = $database->prepare("SELECT $cols FROM pages WHERE id=:parent_id");

		do {
			$query->bindValue(":parent_id", (int) $parentID, \PDO::PARAM_INT);
			$database->execute($query);
			$row = $query->fetch(\PDO::FETCH_NUM);
			if(!$row) {
				$path = '';
				break;
			}
			$parentID = (int) $row[0];
			$templatesID = (int) $row[1];
			$name = empty($row[3]) ? $row[2] : $row[3];

			if($parentID) {
				// non-homepage
				$path = $name . '/' . $path;
			} else {
				// homepage
				if($name !== Pages::defaultRootName && !empty($name)) {
					$path = $name . '/' . $path;
				}
			}

		} while($parentID > $maxParentID);

		if(!strlen($path) || $path === '/') return $path;
		$path = trim($path, '/');

		if($templatesID) {
			$template = $this->wire('templates')->get($templatesID);
			if($template->slashUrls) $path .= '/';
		}

		return '/' . ltrim($path, '/');
	}

	/**
	 * Get a page by its path, similar to $pages->get('/path/to/page/') but with more options
	 *
	 * Please note
	 * ===========
	 * 1) There are no exclusions for page status or access. If needed, you should validate access
	 *    on any page returned from this method.
	 * 2) In a multi-language environment, you must specify the $useLanguages option to be true, if you
	 *    want a result for a $path that is (or might be) a multi-language path. Otherwise, multi-language
	 *    paths will make this method return a NullPage (or 0 if getID option is true).
	 * 3) Partial paths may also match, so long as the partial path is completely unique in the site. 
	 *    If you don't want that behavior, double check the path of the returned page. 
	 *
	 * @param $path
	 * @param array|bool $options array of options (below), or specify boolean for $useLanguages option only.
	 *  - getID: Specify true to just return the page ID (default=false)
	 *  - useLanguages: Specify true to allow retrieval by language-specific paths (default=false)
	 *  - useHistory: Allow use of previous paths used by the page, if PagePathHistory module is installed (default=false)
	 * @return Page|int
	 *
	 */
	public function getByPath($path, $options = array()) {

		$defaults = array(
			'getID' => false,
			'useLanguages' => false,
			'useHistory' => false,
		);

		if(!is_array($options)) {
			$defaults['useLanguages'] = (bool) $options;
			$options = array();
		}

		$options = array_merge($defaults, $options);
		if(isset($options['getId'])) $options['getID'] = $options['getId']; // case alternate
		$homepageID = (int) $this->wire('config')->rootPageID;

		if($path === '/') {
			// this can only be homepage
			return $options['getID'] ? $homepageID : $this->getById($homepageID, array('getOne' => true));
		} else if(empty($path)) {
			return $options['getID'] ? 0 : $this->pages->newNullPage();
		}

		$_path = $path;
		$path = $this->wire('sanitizer')->pagePathName($path, Sanitizer::toAscii);
		$pathParts = explode('/', trim($path, '/'));
		$languages = $options['useLanguages'] ? $this->wire('languages') : null;
		if($languages && !$this->wire('modules')->isInstalled('LanguageSupportPageNames')) $languages = null;

		$langKeys = array(':name' => 'name');
		if($languages) foreach($languages as $language) {
			if($language->isDefault()) continue;
			$languageID = (int) $language->id;
			$langKeys[":name$languageID"] = "name$languageID";
		}

		// first see if we can find a single page just having the name that's the last path part
		// this is an optimization if the page name happens to be globally unique in the system, which is often the case
		$pageID = 0;
		$templatesID = 0;
		$parentID = 0;
		$name = end($pathParts);
		$binds = array(':name' => $name);
		$wheres = array();
		$numParts = count($pathParts);

		// can match 'name' or 'name123' cols where 123 is language ID
		foreach($langKeys as $bindKey => $colName) {
			$wheres[] = "$colName=$bindKey";
			$binds[$bindKey] = $name;
		}
		$sql = 'SELECT id, templates_id, parent_id FROM pages WHERE (' . implode(' OR ', $wheres) . ') ';

		if($numParts == 1) {
			$sql .= ' AND (parent_id=:parent_id ';
			$binds[':parent_id'] = $homepageID;
			if($languages) {
				$sql .= 'OR id=:homepage_id ';
				$binds[':homepage_id'] = $homepageID;
			}
			$sql .= ') ';
		}

		$sql .= 'LIMIT 2';
		$database = $this->wire('database');
		$query = $database->prepare($sql);
		foreach($binds as $key => $value) $query->bindValue($key, $value);
		$database->execute($query);
		$numRows = $query->rowCount();

		if(!$numRows) {
			// no matches - no page in the system can possibly match
			$query->closeCursor();

		} else if($numRows == 1) {
			// just one page has this name - we can stop now, avoiding further checks
			list($pageID, $templatesID, $parentID) = $query->fetch(\PDO::FETCH_NUM);
			$query->closeCursor();

		} else {
			// multiple pages have the name - go back and query again, joining all the path parts
			$query->closeCursor();
			$sql = "SELECT pages.id, pages.templates_id, pages.parent_id FROM pages ";
			$n = 0;
			$binds = array();
			$lastAlias = "pages";
			$lastPart = array_pop($pathParts);

			while(count($pathParts)) {
				$n++;
				$alias = "_pages$n";
				$part = array_pop($pathParts);
				$wheres = array();
				foreach($langKeys as $bindKey => $colName) {
					$bindKey .= "_$n";
					$wheres[] = "$alias.$colName=$bindKey";
					$binds[$bindKey] = $part;
				}
				$sql .= "JOIN pages AS $alias ON $lastAlias.parent_id=$alias.id AND (" . implode(' OR ', $wheres) . ') ';
				$lastAlias = $alias;
			}

			$wheres = array();
			foreach($langKeys as $bindKey => $colName) {
				$wheres[] = "pages.$colName=$bindKey";
				$binds[$bindKey] = $lastPart;
			}

			$sql .= 'WHERE (' . implode(' OR ', $wheres) . ') ';
			$query = $database->prepare($sql);
			foreach($binds as $key => $value) $query->bindValue($key, $value);
			$database->execute($query);
			if($query->rowCount()) {
				list($pageID, $templatesID, $parentID) = $query->fetch(\PDO::FETCH_NUM);
			}
			$query->closeCursor();
		}

		if(!$pageID && $options['useHistory'] && $this->wire('modules')->isInstalled('PagePathHistory')) {
			// if finding failed, check if there is a previous path it lived at, if history module available 
			$page = $this->wire('modules')->get('PagePathHistory')->getPage($this->wire('sanitizer')->pagePathNameUTF8($_path));
			return $options['getID'] ? $page->id : $page;
		}

		if($options['getID']) return (int) $pageID;
		if(!$pageID) return $this->pages->newNullPage();

		return $this->getById((int) $pageID, array(
			'template' => $templatesID ? $this->wire('templates')->get((int) $templatesID) : null,
			'parent_id' => (int) $parentID,
			'getOne' => true
		));
	}

	/**
	 * Get a fresh, non-cached copy of a Page from the database
	 *
	 * This method is the same as `$pages->get()` except that it skips over all memory caches when loading a Page.
	 * Meaning, if the Page is already in memory, it doesn’t use the one in memory and instead reloads from the DB.
	 * Nor does it place the Page it loads in any memory cache. Use this method to load a fresh copy of a page
	 * that you might need to compare to an existing loaded copy, or to load a copy that won’t be seen or touched
	 * by anything in ProcessWire other than your own code.
	 *
	 * ~~~~~
	 * $p1 = $pages->get(1234);
	 * $p2 = $pages->get($p1->path);
	 * $p1 === $p2; // true: same Page instance
	 *
	 * $p3 = $pages->getFresh($p1);
	 * $p1 === $p3; // false: same Page but different instance
	 * ~~~~~
	 *
	 * #pw-advanced
	 *
	 * @param Page|string|array|Selectors|int $selectorOrPage Specify Page to get copy of, selector or ID
	 * @param array $options Options to modify behavior
	 * @return Page|NullPage
	 * @since 3.0.172
	 *
	 */
	public function getFresh($selectorOrPage, $options = array()) {
		if(!isset($options['cache'])) $options['cache'] = false;
		if(!isset($options['loadOptions'])) $options['loadOptions'] = array();
		if(!isset($options['caller'])) $options['caller'] = 'pages.loader.getFresh';
		$options['loadOptions']['getFromCache'] = false;
		if(!isset($options['loadOptions']['cache'])) $options['loadOptions']['cache'] = false;
		$selector = $selectorOrPage instanceof Page ? $selectorOrPage->id : $selectorOrPage;
		return $this->get($selector, $options);
	}

	/**
	 * Load total number of children from DB for given page
	 * 
	 * @param int|Page $page Page or Page ID
	 * @return int
	 * @throws WireException
	 * @since 3.0.172
	 * 
	 */
	public function getNumChildren($page) {
		$pageId = $page instanceof Page ? $page->id : (int) $page;
		$sql = 'SELECT COUNT(*) FROM pages WHERE parent_id=:id';
		$query = $this->wire()->database->prepare($sql);
		$query->bindValue(':id', $pageId, \PDO::PARAM_INT);
		$query->execute();
		$numChildren = (int) $query->fetchColumn(); 
		$query->closeCursor();
		return $numChildren;
	}
	
	/**
	 * Count and return how many pages will match the given selector string
	 *
	 * @param string|array $selector Specify selector, or omit to retrieve a site-wide count.
	 * @param array|string $options See $options in Pages::find
	 * @return int
	 *
	 */
	public function count($selector = '', $options = array()) {
		if(is_string($options)) $options = Selectors::keyValueStringToArray($options);
		if(empty($selector)) {
			if(empty($options)) {
				// optimize away a simple site-wide total count
				return (int) $this->wire('database')->query("SELECT COUNT(*) FROM pages")->fetch(\PDO::FETCH_COLUMN);
			} else {
				// no selector string, but options specified
				$selector = "id>0";
			}
		}
		$options['loadPages'] = false;
		$options['getTotal'] = true;
		$options['caller'] = 'pages.count';
		$options['returnVerbose'] = false;
		//if($this->wire('config')->debug) $options['getTotalType'] = 'count'; // test count method when in debug mode
		if(is_string($selector)) {
			$selector .= ", limit=1";
		} else if(is_array($selector)) {
			$selector['limit'] = 1;
		}
		return $this->pages->find($selector, $options)->getTotal();
	}

	/**
	 * Remove pages from already-loaded PageArray aren't visible or accessible
	 *
	 * @param PageArray $items
	 * @param string $includeMode Optional inclusion mode:
	 * 	- 'hidden': Allow pages with 'hidden' status'
	 * 	- 'unpublished': Allow pages with 'unpublished' or 'hidden' status
	 * 	- 'all': Allow all pages (not much point in calling this method)
	 * @param array $options loadOptions
	 * @return PageArray
	 *
	 */
	protected function filterListable(PageArray $items, $includeMode = '', array $options = array()) {
		if($includeMode === 'all') return $items;
		$itemsAllowed = $this->pages->newPageArray($options);
		foreach($items as $item) {
			if($includeMode === 'unpublished') {
				$allow = $item->status < Page::statusTrash;
			} else if($includeMode === 'hidden') {
				$allow = $item->status < Page::statusUnpublished;
			} else {
				$allow = $item->status < Page::statusHidden;
			}
			if($allow) $allow = $item->listable(); // confirm access
			if($allow) $itemsAllowed->add($item);
		}
		$itemsAllowed->resetTrackChanges(true);
		return $itemsAllowed;
	}

	/**
	 * Returns an array of all columns native to the pages table
	 * 
	 * @return array of column names, also indexed by column name
	 * 
	 */
	public function getNativeColumns() {
		if(empty($this->nativeColumns)) {
			$query = $this->wire('database')->prepare("SELECT * FROM pages WHERE id=:id");
			$query->bindValue(':id', $this->wire('config')->rootPageID, \PDO::PARAM_INT);
			$query->execute();
			$row = $query->fetch(\PDO::FETCH_ASSOC);
			foreach(array_keys($row) as $colName) {
				$this->nativeColumns[$colName] = $colName;
			}
			$query->closeCursor();
		}
		return $this->nativeColumns;	
	}

	/**
	 * Get value of of a native column in pages table for given page ID
	 *
	 * @param int|Page $id Page ID
	 * @param string $column
	 * @return int|string|bool Returns int/string value on success or boolean false if no matching row
	 * @since 3.0.156
	 * @throws \PDOException|WireException
	 *
	 */
	public function getNativeColumnValue($id, $column) {
		$id = (is_object($id) ? (int) "$id" : (int) $id);
		if($id < 1) return false;
		$database = $this->wire('database');
		if($database->escapeCol($column) !== $column) throw new WireException("Invalid column name: $column");
		$query = $database->prepare("SELECT `$column` FROM pages WHERE id=:id");
		$query->bindValue(':id', $id, \PDO::PARAM_INT);
		$query->execute();
		$value = $query->fetchColumn();
		$query->closeCursor();
		return $value;
	}

	/**
	 * Is the given column name native to the pages table?
	 * 
	 * @param $columnName
	 * @return bool
	 * 
	 */
	public function isNativeColumn($columnName) {
		$nativeColumns = $this->getNativeColumns();
		return isset($nativeColumns[$columnName]);
	}

	/**
	 * Get or set debug state
	 * 
	 * @param bool|null $debug
	 * @return bool
	 * 
	 */
	public function debug($debug = null) {
		$value = $this->debug;
		if(!is_null($debug)) $this->debug = (bool) $debug;
		return $value;
	}

	/**
	 * Return the total quantity of pages loaded by getById()
	 * 
	 * @return int
	 * 
	 */
	public function getTotalPagesLoaded() {
		return $this->totalPagesLoaded;
	}

	/**
	 * Get last used instance of PageFinder (for debugging purposes)
	 * 
	 * @return PageFinder|null
	 * @since 3.0.146
	 * 
	 */
	public function getLastPageFinder() {
		return $this->lastPageFinder;
	}
	
}