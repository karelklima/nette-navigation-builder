<?php

class MyPresenter extends Presenter
{
	public function createComponentNavigation($name)
	{
		$navigation = new Navigation;
		// nastavení překladače (nepovinné)
        $navigation->setTranslator(new MyTranslator);

        // nastavení šablony (nepovinné)
        $navigation->setTemplate('/cesta/k/sablone.phtml');
	
        $navigation->getRoot()->label = 'Homepage';
        
        $navigation->add('Articles', $this->link('Articles:default'));
	}
}