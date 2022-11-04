<?php
namespace ShortPixel\Model\AdminNotices;

class ListviewNotice extends \ShortPixel\Model\AdminNoticeModel
{
	protected $key = 'MSG_LISTVIEW_ACTIVE';

	public function __construct()
	{
		 $this->include_screens[] = 'upload';
		 parent::__construct();
	}

	protected function checkTrigger()
	{
		// Don't check for this, when not on this screen.
		$screen_id = \wpSPIO()->env()->screen_id;
		if ($screen_id !== 'upload')
		{
			return false;
		}

		if (! function_exists('wp_get_current_user') )
		{
			return false;

		}

			$current_user = wp_get_current_user();
			$currentUserID = $current_user->ID;
			$viewMode = get_user_meta($currentUserID, "wp_media_library_mode", true);

			if ($viewMode === "" || strlen($viewMode) == 0)
			{
					// If nothing is set, set it for them.
					update_user_meta($currentUserID, 'wp_media_library_mode', 'list');
					return false;
			}
			elseif ($viewMode !== "list")
			{
					return true;
			}
			else
			{
				if (is_object($this->getNoticeObj()))
					$this->reset();
			}

	}


	protected function getMessage()
	{

		$message = sprintf(__('You can see ShortPixel Image Optimiser actions and data only via the list view. Switch to the list view to use the plugin via the media library.  Click to %s switch to the list view %s now. ', 'shortpixel-image-optimiser'), '<a href="' . admin_url('upload.php?mode=list') . '">','</a>');

		return $message;

	}
}
