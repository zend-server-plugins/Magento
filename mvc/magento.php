<?php

	class MagentoPlugin extends ZAppsPlugin {
		
		public function resolveMVCEnter($context) {
			
		}
		
		public function resolveMVCLeave($context) {
			if (!$this->resolved) {
				$this->resolved = true;	
				$mvc = array(					
					"module" => Mage::app()->getFrontController()->getRequest()->getModuleName(),
					"controller" => Mage::app()->getFrontController()->getRequest()->getControllerName(),
					"action" => Mage::app()->getFrontController()->getRequest()->getActionName()
					);
										
				$this->setRequestMVC($mvc);		
			}
		}		
		
		private $resolved = false;
	}
	
	$magentoPlugin = new MagentoPlugin();
	$magentoPlugin->setWatchedFunction("Mage_Core_Controller_Varien_Front::dispatch", array($magentoPlugin, "resolveMVCEnter"), array($magentoPlugin, "resolveMVCLeave"));