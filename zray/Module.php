<?php

namespace Magento;

class Module extends \ZRay\ZRayModule {
	
	public function config() {
	    return array(
	        'extension' => array(
				'name' => 'magento',
			),
	        // configure  custom panels
            'defaultPanels' => array(
                'mlogs'     => false,
				'mevents'   => false,
                'moverview' => false,
                'mrequest'  => false,
             ),
	        'panels' => array(
				'logs' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Logs',
	                'panelTitle'	=> 'Logs',
				),
				'blocks' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Blocks',
	                'panelTitle'	=> 'Blocks',
				),
				'renderedBlocks' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Rendered Blocks',
	                'panelTitle'	=> 'Rendered Blocks',
				),
				'layouts' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Layouts',
	                'panelTitle'	=> 'Layouts',
				),
				'observers' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Observers',
	                'panelTitle'	=> 'Observers',
				    'searchId'      => 'magento-observers-search',
				    'pagerId'       => 'magento-observers-pager',
				),
				'events' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Events',
	                'panelTitle'	=> 'Events',
				    'searchId'      => 'magento-events-search',
				    'pagerId'       => 'magento-events-pager',
				),
	            'request' => array(
	                'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Request',
	                'panelTitle'	=> 'Request',
	                'searchId'      => 'magento-request-search',
	                'pagerId'       => 'magento-request-pager',
	            ),
	            'overview' => array(
	                'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Overview',
	                'panelTitle'	=> 'Overview',
	                'searchId'      => 'magento-overview-search',
	                'pagerId'       => 'magento-overview-pager',
	            ),
	         )
	    );
	}	
}