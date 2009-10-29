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
 * @version    NavigationBuilder 2.0, 2009-10-25
 */

/**
 * Navigation control
 *
 * @author     Karel Klima
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @package    Nette Extras
 */
class Navigation extends Control
{
	/**#@+ Navigation sorting constants (alias of NavigationNode constants) */
	const SORT_NONE = 0, // leaves items in order they are added
		SORT_PRIORITY = 1, // sorts by priority
		SORT_PRIORITY_INTEGER = 2, // converts priority to float and then sorts
		SORT_PRIORITY_STRING = 3, // converts priority to string and then sorts
		SORT_LABEL = 4; // sorts by label
	/**#@-*/
	/** @var NavigationNode */
	protected $root;
	/** @var NavigationNode */
	protected $currentItem;
	/** @var ITranslator */
	protected $translator;
	/** @var string */
	protected $templateFile;
	
	/**
	 * Creates root NavigationNode
	 * @param IComponentContainer $parent
	 * @param string $name
	 */
	public function __construct(IComponentContainer $parent = NULL, $name = NULL)
	{
		$this->root = new NavigationNode($this, 'root');
		$this->root->label = 'Home';
		$this->currentItem = $this->root;
		parent::__construct($parent, $name);
	}
	
	/**
	 * Gets the root item
	 * @return NavigationNode
	 */
	public function getRoot()
	{
		return $this->root;
	}
	
	/**
	 * Adds a child to the item
	 * @param string $label
	 * @param string $url
	 * @param mixed $priority
	 * @param int $sortBy
	 */
	public function add($label, $url = '#', $priority = NULL, $sortBy = NULL)
	{
		return $this->root->add($label, $url, $priority, $sortBy);
	}
	
	/**
	 * Gets an item from the navigation tree
	 * @param string $label
	 * @return NavigationNode|FALSE false if item not found
	 */
	public function get($label)
	{
		return $this->root->get($label);
	}
	
	public function setCurrent(NavigationNode $node)
	{
		if (isset($this->currentItem)) {
			$this->currentItem->isCurrent = FALSE;
		}
		$this->currentItem = $node;
		$node->isCurrent = TRUE;
		return $this;
	}
	
	/**
	 * Assigns a template to render the navigation
	 * @param string $file
	 * @return Navigation
	 */
	public function setTemplate($file)
	{
		$this->templateFile = $file;
		return $this;
	}
	
	/**
	 * Translates labels of added items
	 * @param ITranslator $translator
	 * @return Navigation
	 */
	public function setTranslator(ITranslator $translator)
	{
		$this->translator = $translator;
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
	 * Returns sorted children right before rendering
	 * @return array
	 */
	public function getItems()
	{
		$this->root->getItems();
	}
	
	/**
	 * Sorts item's children right before rendering
	 * @return array
	 */
	public static function sortItems(NavigationNode $component)
	{
		$items = (array) $component->getComponents();
		switch ($component->getSortBy())
		{
			case self::SORT_NONE:
				break;
			case self::SORT_PRIORITY:
				usort($items, array(get_class(), 'comparePriority'));
				break;
			case self::SORT_PRIORITY_INTEGER:
				usort($items, array(get_class(), 'comparePriorityInteger'));
				break;
			case self::SORT_PRIORITY_STRING:
				usort($items, array(get_class(), 'comparePriorityString'));
				break;
			case self::SORT_LABEL:
				usort($items, array(get_class(), 'compareLabel'));
				break;
		}
		return $items;
	}
	
	/**
	 * Helper for usort, compares priority values
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @param int $sortingMethod
	 * @return int;
	 */
	public static function comparePriority(NavigationNode $item1, NavigationNode $item2, $sortingMethod = self::SORT_PRIORITY)
	{
		switch ($sortingMethod)
		{
			case self::SORT_PRIORITY:
				$sortable = array($item1->getPriority(), $item2->getPriority());
				break;
			case self::SORT_PRIORITY_INTEGER:
				$sortable = array((float) $item1->getPriority(), (float) $item2->getPriority());
				break;
			case self::SORT_PRIORITY_STRING:
				$sortable = array(String::lower((string) $item1->getPriority()), String::lower((string) $item2->getPriority()));
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
	public static function comparePriorityInteger($item1, $item2)
	{
		return self::comparePriority($item1, $item2, self::SORT_PRIORITY_INTEGER);
	}
	
	/**
	 * Helper for usort, compares priority values alphabetically
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @return int
	 */
	public static function comparePriorityString($item1, $item2)
	{
		return self::comparePriority($item1, $item2, self::SORT_PRIORITY_STRING);
	}
	
	/**
	 * Helper for usort, compares labels alphabetically
	 * Compares translations of labels if a translator is set
	 * @param NavigationNode $item1
	 * @param NavigationNode $item2
	 * @return int
	 */
	public static function compareLabel(NavigationNode $item1, NavigationNode $item2)
	{
		$navigation = $item1->lookup('Navigation', TRUE);
		$translator = $navigation->getTranslator();
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
	 * Defines how to sort item's childs
	 * @param int $flag
	 * @param bool $deep whether or not to apply sort to item's children
	 * @return NavigationNode
	 */
	public function sortBy($flag, $deep = TRUE)
	{
		return $this->root->sortBy($flag, $deep);
	}
	
	/**
	 * Sets default templates if not already set and renders the component
	 */
	public function render()
	{
		$this->createTemplate();
		
		if ($this->translator instanceof ITranslator) {
			// Puts the translator to the template
			$this->template->setTranslator($this->translator);
		}
		
		$file = '';
		if (!empty($this->templateFile)) {
			$file = $this->templateFile;
		} elseif ($this->template->getFile() == '') {
			if ($this->translator instanceof ITranslator) {
				$file = dirname(__FILE__) . '/template_translate.phtml';
			} else {
				$file = dirname(__FILE__) . '/template.phtml';
			}	 
		}
		
		$this->template->setFile($file);
		
		$this->template->render();
	}
}

/**
 * NavigationNode component
 *
 * @author     Karel Klima
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @package    Nette Extras
 */
class NavigationNode extends ComponentContainer
{
	/**#@+ NavigationNode sorting constants */
	const SORT_NONE = 0, // leaves items in order they are added
		SORT_PRIORITY = 1, // sorts by priority
		SORT_PRIORITY_INTEGER = 2, // converts priority to float and then sorts
		SORT_PRIORITY_STRING = 3, // converts priority to string and then sorts
		SORT_LABEL = 4; // sorts by label
	/**#@-*/
	/** @var string */
	public $label;
	/** @var string */
	public $url;
	/** @var bool */
	public $isCurrent = FALSE;
	/** @var int */
	protected $sortBy = 0;
	/** @var mixed */
	protected $priority;
	/** @var mixed */
	protected $defaultPriority;
	/** @var int */
	private $counter = 0;
	
	/**
	 * Adds a child to the item
	 * @param string $label
	 * @param string $url
	 * @param mixed $priority
	 * @param int $sortBy
	 */
	public function add($label, $url = '#', $priority = NULL, $sortBy = NULL)
	{
		// checks if given label is valid
		if (!is_string($label) || empty($label)) {
			$type = gettype($label);
			throw new InvalidArgumentException("Label parameter must be be a string and must not be empty, '$label' of type $type given.");
		}
		$node = new self;
		$node->label = $label;
		$node->url = $url;
		$node->sortBy(($sortBy <> NULL) ? $sortBy : $this->sortBy);
		$node->priority = ($priority <> NULL) ? $priority : $this->defaultPriority;
		$node->defaultPriority = $this->defaultPriority;
		
		$this->addComponent($node, ++$this->counter);
		
		return $this;
	}
	
	/**
	 * Gets an item from the navigation tree
	 * @param string $label
	 * @return NavigationNode|FALSE false if item not found
	 */
	public function get($label)
	{
		foreach ($this->getComponents() as $item) {
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
		foreach ($this->getComponents() as $item) {
			if ($item->label == $label) $this->removeComponent($item);
		}
		return $this;
	}
	
	/**
	 * Checks whether or not is the item current/active
	 * @return bool
	 */
	public function isCurrent()
	{
		return $this->isCurrent;
	}
	
	/**
	 * Defines whether or not is the item current/active
	 * @param bool $flag
	 * @return NavigationNode
	 */
	public function setCurrent()
	{
		$this->lookup('Navigation')->setCurrent($this);
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
	 * Defines how to sort item's childs
	 * @param int $flag
	 * @param bool $deep whether or not to apply sort to item's children
	 * @return NavigationNode
	 */
	public function sortBy($flag = self::SORT_NONE, $deep = TRUE)
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
		if ($deep) {
			foreach ($this->getComponents() as $item)
				$item->sortBy($flag, $deep);
		}
		return $this;
	}
	
	/**
	 * Returns a method of sorting
	 * @return int
	 */
	public function getSortBy()
	{
		return $this->sortBy;
	}
	
	/**
	 * Returns sorted children right before rendering
	 * @return array
	 */
	public function getItems()
	{
		return Navigation::sortItems($this);
	}
	
}