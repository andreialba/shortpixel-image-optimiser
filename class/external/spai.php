<?php
namespace ShortPixel;
use ShortPixel\ShortpixelLogger\ShortPixelLogger as Log;

class Spai
{
		public function __construct()
		{
			 add_action('plugins_loaded', array($this, 'addHooks'));
		}

		public function addHooks()
		{
			  if (\wpSPIO()->env()->plugin_active('spai'))
				{
					 // Put this a high prio before any hooks
					 Log::addTemp('Hooking');
					 add_action('wp_ajax_shortpixel_image_processing', array($this, 'preventCache'), 2);
					 add_action('wp_ajax_shortpixel_ajaxRequest', array($this, 'preventCache'), 2);
				}
				else {
					Log::addTemp('Spai not active');
				}
		}

		public function preventCache()
		{
				Log::addTemp('PreventC');
			  if (! defined('DONOTCDN'))
				{
					 Log::addTemp('Defined DONOTCACHE');
					 define('DONOTCDN', true);
				}
				else {
					Log::addTemp('Isdefined'. DONOTCDN);
				}
		}
}

$s = new Spai();
