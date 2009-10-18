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
class NavigationBuilder extends NavigationNode
{
	public function __construct()
	{
		$this->items = new ArrayList();
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
	 * Shortcut for Template::setTranslator();
	 * @param ITranslator $translator
	 * @return NavigationBuilder
	 */
	public function setTranslator(ITranslator $translator)
	{
		$this->template->setTranslator($translator);
		return $this;
	}
	
	/**
	 * Renders navigation
	 * @return void
	 */
	public function render()
	{
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
class NavigationNode extends Control
{
	/** @var string */
	public $label;
	/** @var string */
	public $url;
	/** @var ArrayList */
	public $items;
	
	/**
	 * Navigation item setup
	 * @param string $label
	 * @param string $url
	 */
	public function __construct($label, $url)
	{
		$this->url = $url;
		$this->label = $label;
		$this->items = new ArrayList();	
	}
	
	/**
	 * Adds an item to the navigation tree
	 * @param string $label
	 * @param string $url
	 * @return NavigationNode
	 */
	public function add($label, $url)
	{
		$this->items[] = new NavigationNode($label, $url);
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
}