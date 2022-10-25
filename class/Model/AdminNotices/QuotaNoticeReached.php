<?php
namespace ShortPixel\Model\AdminNotices;
use ShortPixel\Controller\StatsController as StatsController;
use ShortPixel\Controller\ApiKeyController as ApiKeyController;
use ShortPixel\Controller\AdminNoticesController as AdminNoticesController;
use ShortPixel\Controller\QuotaController as QuotaController;


class QuotaNoticeReached extends \ShortPixel\Model\AdminNoticeModel
{
	protected $key = 'MSG_QUOTA_REACHED';
	protected $errorLevel = 'error';


	public function __construct()
	{
		 $this->callback = array(AdminNoticesController::getInstance(), 'proposeUpgradePopup');
		 parent::__construct();
	}

	protected function checkTrigger()
	{
			$quotaController = QuotaController::getInstance();

			if ($quotaController->hasQuota() === true)
				return false;

//			$quotaData = $quotaController->getQuota();

		  $this->reset('MSG_UPGRADE_MONTH');
			$this->reset('MSG_UPGRADE_BULK');
	    return true;

	}

	protected function getMessage()
	{
		$statsControl = StatsController::getInstance();
		$averageCompression = $statsControl->getAverageCompression();
		$quotaController = QuotaController::getInstance();

		$keyControl = ApiKeyController::getInstance();

		//$keyModel->loadKey();

		$login_url = 'https://shortpixel.com/login/';
		$friend_url = $login_url;

		if ($keyControl->getKeyForDisplay())
		{
			$login_url .= $keyControl->getKeyForDisplay() . '/';
			$friend_url = $login_url . 'tell-a-friend';
		}

	 $message = '<div class="sp-quota-exceeded-alert"  id="short-pixel-notice-exceed">';

	 if($averageCompression) {

				$message .= '<div style="float:right;">
						<div class="bulk-progress-indicator" style="height: 110px">
								<div style="margin-bottom:5px">' . __('Average image<br>reduction so far:','shortpixel-image-optimiser') . '</div>
								<div id="sp-avg-optimization"><input type="text" id="sp-avg-optimization-dial" value="' . round($averageCompression) . '" class="dial percentDial" data-dialsize="60"></div>
								<script>
										jQuery(function() {
												if (ShortPixel)
												{
													ShortPixel.percentDial("#sp-avg-optimization-dial", 60);
												}
										});
								</script>
						</div>
				</div>';

		}

			$message .= '<h3>' . __('Quota Exceeded','shortpixel-image-optimiser') . '</h3>';

			$quota = $quotaController->getQuota();

			$creditsUsed = number_format($quota->monthly->consumed + $quota->onetime->consumed);
			$totalOptimized = $statsControl->find('total', 'images');
			$totalImagesToOptimize = number_format($statsControl->totalImagesToOptimize());

			$message .= '<p>' . sprintf(__('The plugin has optimized <strong>%s images</strong> and stopped because it reached the available quota limit.','shortpixel-image-optimiser'),
						$creditsUsed);

			if($totalImagesToOptimize > 0) {

						$message .= sprintf(__('<strong> %s images and thumbnails</strong> are not yet optimized by ShortPixel.','shortpixel-image-optimiser'), $totalImagesToOptimize  );
				}

			 $message .= '</p>
					<div>
						<button class="button button-primary" type="button" id="shortpixel-upgrade-advice" onclick="ShortPixel.proposeUpgrade()" style="margin-right:10px;"><strong>' .  __('Show me the best available options', 'shortpixel-image-optimiser') . '</strong></button>
						<a class="button button-primary" href="' . $login_url . '"
							 title="' . __('Go to my account and select a plan','shortpixel-image-optimiser') . '" target="_blank" style="margin-right:10px;">
								<strong>' . __('Upgrade','shortpixel-image-optimiser') . '</strong>
						</a>
						<button type="button" name="checkQuota" class="button" onclick="ShortPixel.checkQuota()">'.  __('Confirm New Credits','shortpixel-image-optimiser') . '</button>
				</div>';

			$message .= '</div>'; /// closing div
			return $message;
	}

}
