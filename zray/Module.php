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
                'mlogs'=>false,
				'mevents'=>false
             ),
	        'panels' => array(
				'settings' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Settings',
	                'panelTitle'	=> 'Settings',
				),
				'logs' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Logs',
	                'panelTitle'	=> 'Logs',
				    'searchId'      => 'magento-logs-search',
				    'pagerId'       => 'magento-logs-pager',
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
				    'searchId'      => 'magento-rblocks-search',
				    'pagerId'       => 'magento-rblocks-pager',
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
				'overview' => array(
					'display'       => true,
	                'logo'          => 'logo.png',
	                'menuTitle' 	=> 'Overview',
	                'panelTitle'	=> 'Overview',
				)
	         )
	    );
	}	
}