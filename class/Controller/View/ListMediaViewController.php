<?php

namespace ShortPixel\Controller\View;
use ShortPixel\ShortpixelLogger\ShortPixelLogger as Log;

use ShortPixel\Helper\UiHelper as UiHelper;
use ShortPixel\Helper\UtilHelper as UtilHelper;


use ShortPixel\Controller\ApiKeyController as ApiKeyController;
use ShortPixel\Controller\QuotaController as QuotaController;
use ShortPixel\Controller\OptimizeController as OptimizeController;
use ShortPixel\Notices\NoticeController as Notice;
use ShortPixel\Model\Image\ImageModel as ImageModel;
use ShortPixel\Model\Image\MediaLibraryModel as MediaLibraryModel;


// Controller for the MediaLibraryView
class ListMediaViewController extends \ShortPixel\ViewController
{

  protected $template = 'view-list-media';
//  protected $model = 'image';

  public function __construct()
  {
    parent::__construct();
  }

  public function load()
  {
			$this->checkAction(); // bulk action checkboxes, y'all
      $this->loadHooks();
  }

	/** Check if a bulk action (checkboxes) was requested
	*/
	protected function checkAction()
	{
	   $wp_list_table = _get_list_table('WP_Media_List_Table');
     $action = $wp_list_table->current_action();


		 if (! $action)
		 		return;

		if(strpos($action, 'shortpixel') === 0 ) {
		 		check_admin_referer('bulk-media');

				// Nothing selected, nothing doin'
				if (! isset($_GET['media']) || ! is_array($_GET['media']))
					return;

		}

		 $fs = \wpSPIO()->filesystem();
		 $optimizeController = new OptimizeController();
		 $items = array_filter($_GET['media'], 'intval');

		 $numItems = count($items);
	   $plugin_action = str_replace('shortpixel-', '', $action);

		 $targetCompressionType = null;

		 switch ($plugin_action)
		 {
			  case "glossy":
					 $targetCompressionType = ImageModel::COMPRESSION_GLOSSY;
				break;
				case "lossy":
					 $targetCompressionType = ImageModel::COMPRESSION_LOSSY;
				break;
				case "lossless":
					  $targetCompressionType = ImageModel::COMPRESSION_LOSSLESS;
				break;
		 }

		 foreach($items as $item_id)
		 {
			 	 $mediaItem = $fs->getMediaImage($item_id);
			   switch($plugin_action)
				 {
					 	case 'optimize':
							 $res = $optimizeController->addItemToQueue($mediaItem);
						break;
						case 'glossy':
						case 'lossy':
						case 'lossless':
							 	$res = $optimizeController->reOptimizeItem($mediaItem, $targetCompressionType);
						break;
						case 'restore';
								$res = $optimizeController->restoreItem($mediaItem);
						break;
				 }

		 }

	}


  /** Hooks for the MediaLibrary View */
  protected function loadHooks()
  {

    add_filter( 'manage_media_columns', array( $this, 'headerColumns' ) );//add media library column header
    add_action( 'manage_media_custom_column', array( $this, 'doColumn' ), 10, 2 );//generate the media library column
    //Sort and filter on ShortPixel Compression column
    add_filter( 'manage_upload_sortable_columns', array( $this, 'registerSortable') );

		// Keep noses out of the rest.
		if (\wpSPIO()->env()->is_screen_to_use)
		{
			add_filter( 'request', array( $this, 'filterBy') );
			add_action('posts_request', array($this, 'parseQuery'), 10, 2);
		}
    add_action('restrict_manage_posts', array( $this, 'mediaAddFilterDropdown'));

    add_action('loop_end', array($this, 'loadComparer'));

  }

  public function headerColumns($defaults)
  {

    $defaults['wp-shortPixel'] = __('ShortPixel Compression', 'shortpixel-image-optimiser');
    if(current_user_can( 'manage_options' )) {
        $defaults['wp-shortPixel'] .=
                  '&nbsp;<a href="options-general.php?page=wp-shortpixel-settings&part=stats" title="'
                . __('ShortPixel Statistics','shortpixel-image-optimiser')
                . '"><span class="dashicons dashicons-dashboard"></span></a>';
    }
    return $defaults;
  }

  public function doColumn($column_name, $id)
  {
     if($column_name == 'wp-shortPixel')
     {
       $this->view = new \stdClass; // reset every row
       $this->loadItem($id);
			 if (property_exists($this->view, 'mediaItem') && is_object($this->view->mediaItem)) // can not be if not exists
			 {
       		$this->loadView(null, false);
			 }
     }
  }

  public function loadItem($id)
  {
     $fs = \wpSPIO()->filesystem();
     $mediaItem = $fs->getMediaImage($id);
     $keyControl = ApiKeyController::getInstance();
     $quotaControl = QuotaController::getInstance();

		 // Asking for something non-existing.
		 if ($mediaItem === false)
		 	 return;

     $this->view->mediaItem = $mediaItem;

     $actions = array();
     $list_actions = array();

    $this->view->text = UiHelper::getStatusText($mediaItem);
    $this->view->list_actions = UiHelper::getListActions($mediaItem);

    if ( count($this->view->list_actions) > 0)
		{
      $this->view->list_actions = UiHelper::renderBurgerList($this->view->list_actions, $mediaItem);
		}
    else
		{
      $this->view->list_actions = '';
		}

    $this->view->actions = UiHelper::getActions($mediaItem);
    //$this->view->actions = $actions;

    if (! $this->userIsAllowed)
    {
      $this->view->actions = array();
      $this->view->list_actions = '';
    }
  }

  public function loadComparer()
  {
    $this->loadView('snippets/part-comparer');
  }

  public function registerSortable($columns)
  {
      $columns['wp-shortPixel'] = 'ShortPixel Compression';
      return $columns;
  }

  public function filterBy($vars)
  {
//return false;
    if ( isset( $vars['orderby'] ) && 'ShortPixel Compression' == $vars['orderby'] ) {

			 //$vars['shortpixel-order'] = $vars['order'];
			 //$vars['orderby'] = 'sum';
			// @todo This one basically can also move as a query hiijack .
        /*$vars = array_merge( $vars, array(
          'meta_key' => '_shortpixel_optimized',
          'orderby' => 'meta_value_num',

        ) ); */
    }

		// Must return postID's  as ID
    if ( 'upload.php' == $GLOBALS['pagenow'] && isset( $_GET['shortpixel_status'] ) ) {

      $status = sanitize_text_field($_GET['shortpixel_status']);

			if ($status == 'all')
			{
				 return $vars; // nono
			}
			switch ($status)
			{
				 case 'opt':
				 	$filter = 'optimized';
				 break;
				 case 'unopt':
				 default:
				 	$filter = 'unoptimized';
				 break;
			}

			$vars['shortpixel-filter']  = $filter;

			if (isset($vars['post_type']))
			{
				 unset($vars['post_type']); // no need to query this when going custom.
			}

    }

    return $vars;
  }

	public function parseQuery($request, $wpquery)
	{
		global $wpdb;
	//	echo "<PRE style='margin-left: 400px; '>"; var_dump($wpquery->query_vars); var_dump($request); echo "</PRE>";

		 // @todo The order is not working. Can be made to work but already is not scaling in performance ( very heavy )
		 // @todo2 Unoptimized can only work in case of restore but not new files, because those are not in the database yet! 
		 if (isset($wpquery->query_vars['shortpixel-filter']) || isset($wpquery->query_vars['shortpixel-order']) )
		 {
			  $filter = isset($wpquery->query_vars['shortpixel-filter']) ? $wpquery->query_vars['shortpixel-filter'] : false ;
				$order =  isset($wpquery->query_vars['shortpixel-order']) ? $wpquery->query_vars['shortpixel-order'] : false;

				if ($filter == 'optimized')
				{
					 $fileStatus = ImageModel::FILE_STATUS_SUCCESS;
				}
				elseif ($filter == 'unoptimized') {
						$fileStatus = ImageModel::FILE_STATUS_UNPROCESSED;
				}

			  $tableName = UtilHelper::getPostMetaTable();
			  $post_where = substr($request, strpos($request, '1=1'));


				if ($filter !== false)
				{
					$sql = ' SELECT attach_id AS ID FROM ' . $tableName;
					$sql .= ' INNER JOIN ' . $wpdb->posts . ' ON ' . $wpdb->posts . '.ID = ' . $tableName . '.attach_id ';

					$sql .= 'WHERE image_type = %d AND status =  %d';
					$sql = $wpdb->prepare($sql, MediaLibraryModel::IMAGE_TYPE_MAIN,  $fileStatus);
					$sql .= ' AND ' . $post_where; // glue back the orders, and the all.
				}

				if ($order !== false)
				{
					$sql = ' SELECT attach_id AS ID, (100.0 * (1.0 - compressed_size/original_size)) as SUM FROM ' . $tableName;
					$sql .= ' INNER JOIN ' . $wpdb->posts . ' ON ' . $wpdb->posts . '.ID = ' . $tableName . '.attach_id ';

				//	$orderstart = strpos($post_where, 'ORDER BY');
				//	$orderend = strpos
				//	$post_where = substr_replace($post_where, 'SUM', , strpos($post_where, $wpquery->query_vars['order']) -1);
//var_dump($post_where);
					$sql .= 'WHERE ' . $post_where; // glue back the orders, and the all.

				}
				return $sql;
		 }

	/*	 if ()
		 {

		 }
*/

		 return $request;
	}



  /*
  * @hook restrict_manage_posts
  */
  public function mediaAddFilterDropdown() {
      $scr = get_current_screen();
      if ( $scr->base !== 'upload' ) return;

      $status   = filter_input(INPUT_GET, 'shortpixel_status', FILTER_UNSAFE_RAW );
  //    $selected = (int)$status > 0 ? $status : 0;
    /*  $args = array(
          'show_option_none'   => 'ShortPixel',
          'name'               => 'shortpixel_status',
          'selected'           => $selected
      ); */
//        wp_dropdown_users( $args );
      $options = array(
          'all' => __('All Images', 'shortpixel-image-optimiser'),
          'opt' => __('Optimized', 'shortpixel-image-optimiser'),
          'unopt' => __('Unoptimized', 'shortpixel-image-optimiser'),
        //  'pending' => __('Pending', 'shortpixel-image-optimiser'),
        //  'error' => __('Errors', 'shortpixel-image-optimiser'),
      );

      echo "<select name='shortpixel_status' id='shortpixel_status'>\n";
      foreach($options as $optname => $optval)
      {
          $selected = ($status == $optname) ? 'selected' : '';
          echo "<option value='". $optname . "' $selected>" . $optval . "</option>\n";
      }
      echo "</select>";

  }

}
