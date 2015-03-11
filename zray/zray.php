<?php

function array_copy($arr) {
    $newArray = array();
    foreach($arr as $key => $value) {
        if(is_array($value)) $newArray[$key] = array_copy($value);
        else if(is_object($value)) $newArray[$key] = clone $value;
        else $newArray[$key] = $value;
    }
    return $newArray;
}

/*********************************
	Magento Z-Ray Extension
	Version: 1.02
**********************************/
class Magento {
	
	/**
	 * @var array
	 */
	private $eventTargets = array();
	private $requests = array();
	private $zray = null;
	private $blocks = array();
	private $events = array();
	private $_eventsCount = 0;
	private $registeredEvents = array();
	private $observersProfiles = array();
    
    public function setZRay($zray) {
        $this->zray = $zray;
    }
    
    /**
     * @return \ZRayExtension
     */
    public function getZRay() {
        return $this->zray;
    }
    
	/**
	 * @param array $context
	 * @param array $storage
	 */
	public function mageAppExit($context, &$storage){
        $this->requests = (array)Mage::app()->getRequest();
		
        // Now that we got our requests, we can untrace 'Mage::app' (for performance reasons)
        $this->getZRay()->untraceFunction("Mage::app");
	}
	
	/**
	 * @param array $context
	 * @param array $storage
	 */
	public function mageRunExit($context, &$storage){
		$storage['modules'] = array();
		$this->storeModules($storage['modules']);
		
		//Observers / Events
		$storage['observers'] = array();
		$this->storeObservers($storage['observers'][]);
		
		
		
		//Overview
		$storage['overview'][] = $this->getOverview();
		
		//Logs
		$storage['mlogs'] = $this->getLogs();
		
		//Layout
		$storage['layouts'][] = $this->getLayoutview();
		
		//Events
		$eventsAreas=array('global', 'adminhtml', 'frontend');
		$events=array();
		$counter = 1;
		foreach($eventsAreas as $area){
			foreach($this->registeredEvents[$area] as $eventName => $eventData){
				$observers = array();
				foreach($eventData['observers'] as $name => &$observer){
					if($this->observersProfiles[$eventName.'_'.$observer['method']]){
						$observer['action']=$this->observersProfiles[$eventName.'_'.$observer['method']]['action'];
						$observer['duration']=number_format($this->observersProfiles[$eventName.'_'.$observer['method']]['duration'],3);
					}
					if($observer['action']){
						try{
							$reflector = new ReflectionClass($observer['action']);
							$actionFile=$reflector->getFileName();
							unset($reflector);
						}catch(Exception $e){
							$actionFile=false;
						}
					}
					if($observer['model']){
						try{
							//Convert model to class
							if(preg_match('/\//',$observer['model'])){
								$observer['model']=get_class(Mage::getModel($observer['model']));
							}
						}catch(Exception $e){}
						try{
							$methodReflector = new ReflectionMethod($observer['model'], $observer['method']);
							$methodLine = $methodReflector->getStartLine();
						}catch(Exception $e){
							$methodLine=0;
						}
						unset($methodReflector);
						try{
							$reflector = new ReflectionClass($observer['model']);
							$classFile=$reflector->getFileName();
							unset($reflector);
						}catch(Exception $e){
							$classFile=false;
						}
					}
					$observers[]=array(
						'name'=>$name,
						'class'=>$observer['model'],
						'classFile'=>$classFile,
						'method'=>$observer['method'],
						'methodLine'=>$methodLine,
						'action'=>$observer['action'],
						'actionFile'=>$actionFile,
						'args'=>$observer['args'],
						'area'=>$area,
						'duration'=>$observer['duration']
					);
				}
				$events[]=array(
					'id'=>$counter,
					'name'=>$eventName,
					'area'=>$area,
					'observers' => $observers,
				);
				$counter++;
				
			}
		}
		
		$storage['mevents'] = $events;
	}
	
	private function getLogs(){
		$files = array();
		$base = Mage::getBaseDir('log');
		foreach(scandir($base) as $file){
			if($file=='.'||$file=='..'){ continue; }
			$files[]=array(
				'file'=>$file,
				'logFile'=>Mage::getBaseDir('log') . DIRECTORY_SEPARATOR . $file
			);
		}
		return $files;
	}
	
	private function getLayoutview(){
		require_once 'layoutViewer.php';
		$layoutViewer = new Magento_Layoutviewer();
		
		$layoutFilesArr=array();
		foreach($layoutViewer->getLayoutFiles() as $layout){
			$layoutFilesArr[]=array('filename'=>$layout,'file'=>Mage::getBaseDir('design') . DIRECTORY_SEPARATOR .$layout);
		}
		
		$layoutview = array(
				'page'=>array('layout'=>'Page','data'=> $layoutViewer->getPageLayout()),
				'package'=>array('layout'=>'Package','data'    => $layoutViewer->getPackageLayout()),
				'layouts'=>array('layout'=>'Layouts','data'    => $layoutFilesArr),
		);
		return $layoutview;
	}	
	
	private function getOverview(){
		$_website = Mage::app()->getWebsite();
		$_store = Mage::app()->getStore();
        $cacheMethod = explode('_',get_class(Mage::app()->getCache()->getBackend()));
        $cacheMethod = end($cacheMethod);
        $controllerClassReflection = new ReflectionClass(get_class(Mage::app()->getFrontController()->getAction()));
        
		//Requests
		$finalRequests = (array)Mage::app()->getRequest();
		
		$request = array();
		
		$request[] = array('property' => 'Controller Name', 'init'=>Mage::app()->getRequest()->getControllerName(), 'final'=>Mage::app()->getRequest()->getControllerName());
		
		$request[] = array('property' => 'Action Name', 'init'=>Mage::app()->getRequest()->getActionName(), 'final'=>Mage::app()->getRequest()->getActionName());
		
		$request[] = array('property' => 'Route Name', 'init'=>Mage::app()->getRequest()->getRouteName(), 'final'=>Mage::app()->getRequest()->getRouteName());
		
		$request[] = array('property' => 'Module Name', 'init'=>Mage::app()->getRequest()->getModuleName(), 'final'=>Mage::app()->getRequest()->getModuleName());
		
		foreach($this->requests as $key=>$value) {
			$finalVal = !array_key_exists($key,$finalRequests) ? '[NULL]' : $finalRequests[$key];
			$request[] = array('property' => $key, 
                                          'init' => is_array($value) ? print_r($value,true) : $value, 'final'=>is_array($finalVal) ? print_r($finalVal,true) : $finalVal);
		}

		//Handles
		$handles = array_map(function($handle){
			return array('name' => $handle);
		}, Mage::app()->getLayout()->getUpdate()->getHandles());
		
		
		$collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes()) //add the attributes visible in product list to the collection
            ->addMinimalPrice() //add the prices to the collection
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite(); //add the url rewrites to the collection
			
		$activeProductsCount = Mage::getModel('catalog/product')
		->getCollection()
        ->addAttributeToSelect('entity_id')
        ->addAttributeToFilter('status', 1)
        ->addAttributeToFilter('visibility', 4)
		->count();
		
		$disabledProductsCount = Mage::getModel('catalog/product')
		->getCollection()
        ->addAttributeToSelect('entity_id')
        ->addAttributeToFilter('status', 0)
		->count();
		
		$categoryCount = Mage::getModel('catalog/category')
		->getCollection()
		->count();
		
		$ordersCount = Mage::getModel('sales/order')
		->getCollection()
		->count();
		
		$productAttrsCount=0;
		$productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
		foreach ($productAttrs as $productAttr) { 
		/** $productAttr Mage_Catalog_Model_Resource_Eav_Attribute */
			$productAttrsCount++;
		}
		
		$urlRewritesCount = Mage::getModel('core/url_rewrite')
		->getCollection()
		->count();
		
		$overview = array(
	            'Website ID'      => (method_exists($_website,'getId')) ? $_website->getId() : '',
	            'Website Name'    => (method_exists($_website,'getName')) ? $_website->getName() : '',
	            'Store Id'        => (method_exists($_store,'getGroupId')) ? $_store->getGroupId() : '',
	            'Store Name'      => (method_exists($_store,'getGroup') && method_exists($_store->getGroup(),'getName')) ? $_store->getGroup()->getName() : '',
	            'Store View Id'   => (method_exists($_store,'getId')) ? $_store->getId() : '',
	            'Store View Code' => (method_exists($_store,'getCode')) ? $_store->getCode() : '',
	            'Store View Name' => (method_exists($_store,'getName')) ? $_store->getName() : '',
				'Active Products'	=> $activeProductsCount,
				'Disabled Products'	=> $disabledProductsCount,
				'Categories'		=> $categoryCount,
				'Orders'		=> $ordersCount,
				'Product Attributes'		=> $productAttrsCount,
				'URL Rewrites'		=> $urlRewritesCount,
	            'Cache Backend'    => $cacheMethod,
				'Magento Version'   => Mage::getVersion(),
				'Edition'              => Mage::helper('core')->isModuleEnabled('Enterprise_Enterprise') ? 'enterprise' : 'community',
				'Controller Class Name' => get_class(Mage::app()->getFrontController()->getAction()),
				'Controller Class Path' => str_replace(Mage::getBaseDir(),'',str_replace("'",'',$controllerClassReflection->getFileName())),
				'Module Name'           => Mage::app()->getRequest()->getRouteName(),
				'Controller Name'       => Mage::app()->getRequest()->getControllerName(),
				'Action Name'           => Mage::app()->getRequest()->getActionName(),
				'Path Info'		=> Mage::app()->getRequest()->getPathInfo(),
				'Current Package'       => Mage::getDesign()->getPackageName(),
				'Current Theme'         => Mage::getDesign()->getTheme(''),
				'Template Path'         => str_replace(Mage::getBaseDir(),'',Mage::getDesign()->getTemplateFilename('')),
				'Layout Path'           => str_replace(Mage::getBaseDir(),'',Mage::getDesign()->getLayoutFilename('')),
				'Translation Path'      => str_replace(Mage::getBaseDir(),'',Mage::getDesign()->getLocaleBaseDir(array())),
				'Skin Path'             => str_replace(Mage::getBaseDir(),'',Mage::getDesign()->getSkinBaseDir(array()))			
				
	        );
		
		return array(
			'overview'=>$overview,
			'request'=>$request,
			'handles'=>$handles
		);
	}

	
	public function collectBlockRender($context, & $storage){
		if(!$context['functionArgs'][1]['block']){ 
			return; 
		}
		$block = $context['functionArgs'][1]['block'];
		
		$blockStruct = array();
		$blockStruct['class'] = get_class($block);
		try{
			$reflector = new ReflectionClass($blockStruct['class']);
			$blockStruct['classFile']=$reflector->getFileName();
			unset($reflector);
		}catch(Exception $e){}
		$blockStruct['layout'] = $block->getNameInLayout();
		$blockStruct['render_time'] = microtime(true);

		if (method_exists($block, 'getTemplateFile')) {
			$blockStruct['template'] = $block->getTemplateFile();
			$blockStruct['templateFile'] = Mage::getBaseDir('design') . DIRECTORY_SEPARATOR . $block->getTemplateFile();
			if(!is_file($blockStruct['templateFile'])){
					unset($blockStruct['templateFile']);
				}
		} else {
			$blockStruct['template'] = '';
		}
		if (method_exists($block, 'getViewVars')) {
			$blockStruct['view_variables'] = $block->getViewVars();
		} else {
			$blockStruct['view_variables'] = NULL;
		}
		$blockStruct['inCache'] = Mage::app()->loadCache($block->getCacheKey()) ? true : false;
		
		$this->blocks[$block->getNameInLayout()]=$blockStruct;
	}
	
	public function processBlockRender($context, & $storage){
		if(!$context['functionArgs'][1]['block']) { 
			return; 
		}
		$block = $context['functionArgs'][1]['block'];
		$this->blocks[$block->getNameInLayout()]['render_time'] = number_format(microtime(true) - $this->blocks[$block->getNameInLayout()]['render_time'], 3);
		$storage['renderedBlocks'][]=$this->blocks[$block->getNameInLayout()];
		unset($this->blocks[$block->getNameInLayout()]);
	}
	
	public function collectLayoutBlocks($context, & $storage){
		$blocks_count=0;
		$blocks=array();
		$ptrs=array();
		$layoutBlocks = $context['functionArgs'][1]['layout']->getAllBlocks();
		foreach ($layoutBlocks as $key => $block) {
            $parentBlock = $block->getParentBlock();
		
			$blockStruct = array();
            $blockStruct['class'] = get_class($block);
			try{
				$reflector = new ReflectionClass($blockStruct['class']);
				$blockStruct['classFile']=$reflector->getFileName();
				unset($reflector);
			}catch(Exception $e){}
			
            $blockStruct['layout_name'] = $block->getNameInLayout();
			$blockStruct['blocks'] = array();
            if( method_exists($block, 'getTemplateFile') ) {
                $blockStruct['template'] = $block->getTemplateFile();
                $blockStruct['templateFile'] = Mage::getBaseDir('design') . DIRECTORY_SEPARATOR . $block->getTemplateFile();
				if(!is_file($blockStruct['templateFile'])){
					unset($blockStruct['templateFile']);
				}
            } else {
                $blockStruct['template'] = '';
            }
            if( method_exists($block, 'getViewVars') ) {
                $blockStruct['context'] = $block->getViewVars();
            } else {
                $blockStruct['context'] = NULL;
            }
			if(!$block->getParentBlock()){
				$blocks[] = $blockStruct;
				end($blocks);
				$key = key($blocks);
				$ptrs[$blockStruct['layout_name']] = &$blocks[$key];
			}else{
				$parentKey=$block->getParentBlock()->getNameInLayout();
				$ptrs[$parentKey]['blocks'][] = $blockStruct;
				end($ptrs[$parentKey]['blocks']);
				$key = key($ptrs[$parentKey]['blocks']);
				$ptrs[$blockStruct['layout_name']] = &$ptrs[$parentKey]['blocks'][$key];
			}
			$blocks_count++;
        }
		/*
		$blocks=json_decode(json_encode($blocks));
		print_r($blocks);die;*/
		$storage['blocks'][]=array('blocks'=>$blocks,'count'=>$blocks_count);
		
		//$storage['blocks'][] = array_copy(array('blocks' => $blocks,'count' => $blocks_count));
		$storage['blocks'][] = json_decode(json_encode(array('blocks' => $blocks,'count' => $blocks_count)), true);
	}
	
	/**
	 * @param array $context
	 */
	public function mageDispatchEventStart($context, & $storage) {
		/// collect event targets for events collector
		$event = $context['functionArgs'][0];
		switch($event){
			case 'controller_action_layout_generate_blocks_after':
				$this->collectLayoutBlocks($context,$storage);
				break;
			case 'core_block_abstract_prepare_layout_before':
				$this->collectBlockRender($context,$storage);
				break;
			case 'core_block_abstract_prepare_layout_after':
				$this->processBlockRender($context, $storage);
				break;
		}
		$this->events[$event]=array(
			'id'=>++$this->_eventsCount,
			'name'=>$event,
			'action'=>is_object($context['functionArgs'][1]) ? get_class($context['functionArgs'][1]) : false,
		);
		/* EOF Block render */
		$args = isset($context['functionArgs'][1]) ? $context['functionArgs'][1] : array();
		$intersection = array_intersect(array('object', 'resource', 'collection', 'front', 'controller_action'), array_keys($args));
		$key = array_shift($intersection);
		if(isset($args[$key])){
			$this->eventTargets[$event] = $args[$key];
		}
	}
	
	/**
	 * @param array $context
	 * @param array $storage
	 */
	public function appCallObserverMethodStart($context, & $storage){		
		
		$method = $context['functionArgs'][1];
		$observerData = $context['functionArgs'][2]->getData();
		$eventArgs = $observerData['event']->getData();
		$event = $observerData['event']->getName();
		$object = get_class($context['functionArgs'][0]);
		unset($context['functionArgs'][2]);
		unset($context['this']);
		//var_dump($context);die;
		$this->observersProfiles[$event.'_'.$method]=array(
			'duration'=>microtime(time)
		);
		if(isset($this->eventTargets[$event])){
			$this->observersProfiles[$event.'_'.$method]['action']=get_class($this->eventTargets[$event]);
		}
	}
	public function appCallObserverMethodEnd($context, & $storage){
		$method = $context['functionArgs'][1];
		$observerData = $context['functionArgs'][2]->getData();
		$event = $observerData['event']->getName();
		$object = get_class($context['functionArgs'][0]);
		
		$this->observersProfiles[$event.'_'.$method]['duration']=microtime(true)-$this->observersProfiles[$event.'_'.$method]['duration'];
	}
	
	/**
	 * @param array $storage
	 */
	private function storeModules(& $storage) {
		$modules = Mage::getConfig()->getNode('modules')->children();
		foreach($modules as $moduleName => $module){
			$storage[] = array(
				'Name'=>$moduleName,
				'Active'=>$module->active == 'false' ? 'false' : 'true',
				'Code Pool'=>(string)$module->codePool,
				'Version'=>(string)$module->version,
			);
		}
	}
	
	/**
	 * @param array $storage
	 */
	private function storeObservers(& $storage) {
		$count=0;
		foreach (array('global', 'adminhtml', 'frontend') as $eventArea) {
			$eventConfig = $this->getEventAreaEventConfigs($eventArea);
			if (! ($eventConfig instanceof Mage_Core_Model_Config_Element)) {
				continue;
			}
			
			$events = $eventConfig->children();
			$count+=count($events);
			$this->processEventObservers($events, $eventArea, $storage['observers']);
		}
		$storage['count']=$count;
	}
	
	/**
	 * @param string $eventArea
	 * @return Mage_Core_Model_Config_Element|null
	 */
	private function getEventAreaEventConfigs($eventArea) {
		return Mage::app()->getConfig()->getNode(sprintf('%s/events', $eventArea));
	}
	
	/**
	 * @param array $areaEvents
	 * @param string $eventArea
	 * @param array $storage
	 */
	private function processEventObservers($areaEvents, $eventArea, & $storage) {
		foreach ($areaEvents as $eventName => $event) {
			foreach ($event->observers->children() as $observerName => $observer) {
				$class = Mage::app()->getConfig()->getModelClassName($observer->class);
				$method = (string)$observer->method;
				try{
					$methodReflector = new ReflectionMethod($class, $method);
					$methodLine = $methodReflector->getStartLine();
				}catch(Exception $e){
					$methodLine=0;
				}
				unset($methodReflector);
				$reflector = new ReflectionClass($class);
				$classFile=$reflector->getFileName();
				unset($reflector);
				$observerData = array(
						'area' => $eventArea,
						'event' => $eventName,
						'observer' => $observerName,
						'class' => $class,
						'classFile' => $classFile,
						'methodLine' => $methodLine,
						'method' => $method,
						'type' => (string)$observer->type ? (string)$observer->type : 'singleton'
				);
				$storage[] = $observerData;
			}
		}
	}
    
    public function createEvents($context, & $storage){
		$mageReflect = new ReflectionClass($context['this']);
		$property = $mageReflect->getProperty('_events');
		$property->setAccessible(true);
		$events = $property->getValue($context['this']);
		$this->registeredEvents=$events;
		unset($mageReflect);
	}
}


$zrayMagento = new Magento();
$zrayMagento->setZRay(new ZRayExtension('magento'));

$zrayMagento->getZRay()->setMetadata(array(
    'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));

$zrayMagento->getZRay()->setEnabledAfter('Mage::run');
$zrayMagento->getZRay()->traceFunction('Mage::app', function(){}, array($zrayMagento, 'mageAppExit'));
$zrayMagento->getZRay()->traceFunction('Mage::run', function(){}, array($zrayMagento, 'mageRunExit'));
$zrayMagento->getZRay()->traceFunction('Mage_Core_Model_App::_callObserverMethod', array($zrayMagento, 'appCallObserverMethodStart'), array($zrayMagento, 'appCallObserverMethodEnd'));
$zrayMagento->getZRay()->traceFunction('Mage::dispatchEvent', array($zrayMagento, 'mageDispatchEventStart'), function(){});	
$zrayMagento->getZRay()->traceFunction('Mage_Core_Model_App::run', function(){},array($zrayMagento, 'createEvents'));	
