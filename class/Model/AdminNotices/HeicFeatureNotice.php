<?php
namespace ShortPixel\Model\AdminNotices;

class HeicFeatureNotice extends \ShortPixel\Model\AdminNoticeModel
{
	protected $key = 'MSG_FEATURE_HEIC';

	public function __construct()
	{
	//	 $this->exclude_screens[] = 'settings_page_wp-shortpixel-settings';
		 parent::__construct();
	}

	protected function checkTrigger()
	{
// always fire(?)
		return true;
	}

	protected function checkReset()
	{
		$settings = \wpSPIO()->settings();
		 if ($settings->useSmartcrop == true)
		 {
			  return true;
		 }
		 return false;
	}

	protected function getMessage()
	{
		$link = 'https://shortpixel.com/knowledge-base/article/566-heic-apple-images-support-in-shortpixel-image-optimizer';

	//	$message =
		$message = sprintf(__('Do you have an iPhone? Now you can upload native iPhone HEIC images directly to your WordPress. %s ShortPixel takes care of automagically converting HEIC images to JPEGs and optimizes them as well.  %sRead more here%s.', 'shortpixel-image-optimiser'),
		 '<br><br>' ,
		 '<a href="' . $link . '" target="_blank">', '</a>'

	 );
		return $message;

	}
}