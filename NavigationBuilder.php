<?php

/**
 * NavigationBuilder
 * 
 * Works best with Nette Framework
 *
 * This source file is subject to the New BSD License.
 *
 * @copyright  Copyright (c) 2009 Karel Klima
 * @license    New BSD License
 * @package    Nette Extras
 * @version    NavigationBuilder 1.0, 2009-10-18
 */


/**
 * NavigationBuilder control
 *
 * @author     Karel Klima
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @package    Nette Extras
 */
class XNavigationBuilder extends XNavigationNode
{
	protected $translator;
	
	public function __construct()
	{
		$this->items = new ArrayList();
		$this->builder = $this;
	}
	
	/**
	 * Shortcut for Template::setFile();
	 * @param string $file
	 * @return NavigationBuilder
	 */
	public function setTemplate($file)
	{
		$this->template->setFile($file);
		return $this;
	}
	
	/**
	 * Translates labels of added items
	 * @param ITranslator $translator
	 * @return NavigationBuilder
	 */
	public function setTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
		// Puts the translator to the template
		$this->template->setTranslator($translator);
		return $this;
	}
	
	/**
	 * Returns current translator
	 * @return FALSE|ITranslator
	 */
	public function getTranslator()
	{
		return ($this->translator instanceof ITranslator) ? $this->translator : FALSE;
	}
	
	/**
	 * Renders navigation
	 * @return void
	 */
	public function render()
	{
		// Sorts navigation items
		$this->sort();
		
		// Puts navigation items into the template
		$this->template->items = $this->items;
		
		if (!is_file($this->template->getFile())) {
			$helpers = $this->template->getHelpers();
			// Sets default template according to availability of ITranslator
			if (isset($helpers['translate'])) {
				$this->template->setFile(dirname(__FILE__) . '/template_translate.phtml');
			} else {
				$this->template->setFile(dirname(__FILE__) . '/template.phtml');
			}
		}
			
		$this->template->render();
	}
}

/**
 * NavigationNode control, base of NavigationBuilder
 *
 * @author     Karel Klima
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @package    Nette Extras
 */
class XNavigationNode extends Component
{
	const SORT_NONE = 0, // leaves items in order they are added
		SORT_PRIORITY = 1, // sorts by priority
		SORT_PRIORITY_INTEGER = 2, // converts priority to float and then sorts
		SORT_PRIORITY_STRING = 3, // converts priority to string and then sorts
		SORT_LABEL = 4; // sorts by label
	/** @var string */
	public $label;
	/** @var string */
	public $url;
	/** @var mixed */
	protected $priority;
	/** @var mixed */
	protected $defaultPriority = NULL;
	/** @var string */
	public $attributes = array();
	/** @var int */
	protected $sortBy = 0;
	/** @var ArrayList */
	public $items;
	/** @var NavigationBuilder */
	protected $builder;
	
	/**
	 * Navigation item setup
	 * @param string $label
	 * @param string $url
	 */
	public function __construct(NavigationBuilder $builder, $label, $url = '#', $priority = NULL, $attributes = array(), $sortBy = 0, $defaultPriority = NULL)
	{
		// checks if given label is valid
		if (!is_string($label) || empty($label)) {
			$type = gettype($label);
			throw new InvalidArgumentException("Label parameter must be be a string and must not be empty, '$label' of type $type given.");
		}
		// checks if given attributes are valid
		if (!is_array($attributes)) {
			$type = gettype($attributes);
			throw new InvalidArgumentException("Attributes parameter must be an array, $type given");
		}
		
		// initiate the children container
		$this->items = new ArrayList();
		$this->builder = $builder;
		
		$this->url = $url;
		$this->label = $label;
		$this->priority = $priority;
		$this->defaultPriority = $defaultPriority;
		$this->attributes = $attributes;
		$this->sortBy($sortBy);	
	}
	
	/**
	 * Adds an item to the navigation tree
	 * @param string $label name of item or NavigationNode object
	 * @param string $url
	 * @return NavigationNode
	 */
	public function add($label, $url = '#', $priority = NULL, $attributes = array(), $sortBy = NULL, $defaultPriority = NULL)
	{
		// sets default priority if not given
		if ($priority == NULL) {
			$priority = $this->defaultPriority;
		}
		// sets default priority for item's children if not given
		if ($defaultPriority == NULL) {
			$defaultPriority = $this->defaultPriority;
		}
		// sets default sorting policy for item's children if not given
		if ($sortBy == NULL) {
			$sortBy = $this->sortBy;
		}
		// creates the item
		$this->items[] = new XNavigationNode($this->builder, $label, $url, $priority, $attributes, $sortBy, $defaultPriority);
		return $this;
	}
	
	/**
	 * Gets an item from the navigation tree
	 * @param string $label
	 * @return NavigationNode|FALSE false if item not found
	 */
	public function get($label)
	{
		foreach ($this->items as $item) {
			if ($item->label == $label) return $item;
		}
		return FALSE;
	}
	
	/**
	 * Removes an item (or items) from the navigation tree
	 * @param string $label
	 * @param string $url
	 * @return NavigationNode
	 */
	public function remove($label)
	{
		foreach ($this->items as $item) {
			if ($item->label == $label) $this->items->remove($item);
		}
		return $this;
	}
	
	/**
	 * Defines how to sort item's childs
	 * @param int $flag
	 * @param bool $multilevel whether or not to apply sort to item's children
	 * @return NavigationNode
	 */
	public function sortBy($flag = self::SORT_NONE, $multiLevel = TRUE)
	{
		$possibleFlags = array(
			self::SORT_NONE,
			self::SORT_PRIORITY,
			self::SORT_PRIORITY_INTEGER,
			self::SORT_PRIORITY_STRING,
			self::SORT_LABEL
		);
		if (!in_array($flag, $possibleFlags)) {
			throw new InvalidArgumentException("Invalid sorting flag, '$flag' given");
		}
		$this->sortBy = $flag;
		if ($multiLevel) {
			foreach ($this->items as $item)
				$item->sortBy($flag, $multiLevel);
		}
		return $this;
	}
	
	/**
	 * Sorts item's children right before rendering
	 */
	public function sort()
	{
		$items = (array) $this->items;
		switch ($this->sortBy)
		{
			case self::SORT_NONE:
				break;
			case self::SORT_PRIORITY:
				usort($items, array($this, 'comparePriority'));
				break;
			case self::SORT_PRIORITY_INTEGER:
				usort($items, array($this, 'comparePriorityInteger'));
				break;
			case self::SORT_PRIORITY_STRING:
				usort($items, array($this, 'comparePriorityString'));
				break;
			case self::SORT_LABEL:
				usort($items, array($this, 'compareLabel'));
				break;
		}
		$this->items->import($items);
		
		foreach ($this->items as $item) {
			$item->sort();
		}
	}
	
	/**
	 * Helper for usort, compares priority values
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @param int $sortingMethod
	 * @return int;
	 */
	public function comparePriority(NavigationNode $item1, NavigationNode $item2, $sortingMethod = self::SORT_PRIORITY)
	{
		switch ($sortingMethod)
		{
			case self::SORT_PRIORITY:
				$sortable = array($item1->priority, $item2->priority);
				break;
			case self::SORT_PRIORITY_INTEGER:
				$sortable = array((float) $item1->priority, (float) $item2->priority);
				break;
			case self::SORT_PRIORITY_STRING:
				$sortable = array(String::lower((string) $item1->priority), String::lower((string) $item2->priority));
				break;
			default:
				throw new InvalidStateException("No match for sorting method");
		}
		if ($sortable[0] == $sortable[1]) return 0;
		$sorted = $sortable;
		sort($sorted);
		return ($sorted[0] == $sortable[0]) ? -1 : 1;
	}
	
	/**
	 * Helper for usort, compares priority values numerically
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @return int
	 */
	public function comparePriorityInteger($item1, $item2)
	{
		return $this->comparePriority($item1, $item2, self::SORT_PRIORITY_INTEGER);
	}
	
	/**
	 * Helper for usort, compares priority values alphabetically
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @return int
	 */
	public function comparePriorityString($item1, $item2)
	{
		return $this->comparePriority($item1, $item2, self::SORT_PRIORITY_STRING);
	}
	
	/**
	 * Helper for usort, compares labels alphabetically
	 * Compares translations of labels if a translator is set
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @return int
	 */
	public function compareLabel(NavigationNode $item1, NavigationNode $item2)
	{
		$translator = $this->getBuilder()->getTranslator();
		if ($translator instanceof ITranslator) {
			$label1 = $translator->translate($item1->label);
			$label2 = $translator->translate($item2->label);
		} else {
			$label1 = $item1->label;
			$label2 = $item2->label;
		} 
		$sortable = array(String::lower((string) $label1), String::lower((string) $label2));
		if ($sortable[0] == $sortable[1]) return 0;
		$sorted = $sortable;
		sort($sorted);
		return ($sorted[0] == $sortable[0]) ? -1 : 1;
	}
	
	/**
	 * Sets default priority
	 * @param mixed $value
	 * @return NavigationNode
	 */
	function setDefaultPriority($value)
	{
		$this->defaultPriority = $value;
		return $this;
	}
	
	/**
	 * Gets current priority of an item
	 * @return mixed priority
	 */
	public function getPriority()
	{
		return $this->priority;
	}
	
	/**
	 * Gets the original NavigationBuilder instance
	 * @return NavigationBuilder
	 */
	public function getBuilder()
	{
		return $this->builder;
	}
	
}