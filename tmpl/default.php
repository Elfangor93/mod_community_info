<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_community_info
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Module\CommunityInfo\Administrator\Helper\CommunityInfoHelper;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->useStyle('communityinfo.leaflet');
$wa->useScript('communityinfo.leaflet');
$wa->useScript('bootstrap.modal');
$wa->useScript('bootstrap.collapse');
$wa->useStyle('communityinfo.style');
$wa->useScript('communityinfo.script');
$wa->addInlineStyle('#map * + * {margin: 0;}');

$lang         = $app->getLanguage();
$extension    = $app->getInput()->get('option');
$currentURL   = Uri::getInstance()->toString();
?>

<div id="CommunityInfo<?php echo strval($module->id); ?>" class="mod-community-info px-3">
  <p><?php echo Text::_('MOD_COMMUNITY_INFO_JOOMLA_DESC'); ?></p>
  <hr />
  <div class="info-block contact">
    <h3><?php echo Text::_('MOD_COMMUNITY_INFO_CONTACT_TITLE'); ?></h3>
    <p><?php echo CommunityInfoHelper::replaceText(Text::_('MOD_COMMUNITY_INFO_CONTACT_TEXT'), $links); ?></p>
  </div>
  <hr />
  <div class="info-block news">
    <div class="intro-txt">
      <div>
        <h3><?php echo Text::_('MOD_COMMUNITY_INFO_NEWS_TITLE'); ?></h3>
        <p><?php echo Text::_('MOD_COMMUNITY_INFO_NEWS_INTRO'); ?></p>
      </div>
      <a class="btn btn-primary"><?php echo Text::_('MOD_COMMUNITY_INFO_NEWS_SUBSCRIBE'); ?></a>
      <button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNews" aria-expanded="false" aria-controls="collapseNews"><i class="icon-arrow-down"></i></button>
    </div>
    <table id="collapseNews" class="table community-info-news collapse">
      <tbody>
        <tr>
          <td scope="row"><a href="https://www.joomla.de/news/joomla/joomla-5-1-0-und-joomla-4-4-4-veroeffentlicht" target="_blank">Hurra, Joomla 5.1.0 und Joomla! 4.4.4 sind da</a></td>
        </tr>
        <tr>
          <td><a href="https://www.joomla.de/news/joomla/joomla-5-1-release-candidate-veroeffentlicht" target="_blank">Joomla! 5.1 Release Candidate veröffentlicht</a></td>
        </tr>
      </tbody>
    </table>
  </div>  
  <hr />
  <div class="info-block events">
    <div class="intro-txt">
      <div>
        <h3><?php echo Text::_('MOD_COMMUNITY_INFO_EVENTS_TITLE'); ?></h3>
        <p><?php echo Text::_('MOD_COMMUNITY_INFO_EVENTS_INTRO'); ?></p>
      </div>
      <button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEvents" aria-expanded="false" aria-controls="collapseEvents"><i class="icon-arrow-down"></i></button>
    </div>
    <table id="collapseEvents" class="table community-info-news collapse">
      <tbody>
        <tr>
          <td scope="row">JUG Trimbach<br />Meetup - Trimbach, Schweiz</td>
          <td style="text-align: right">Montag, Jun 3, 2024<br />19:00 GMT+2</td>
        </tr>
      </tbody>
    </table>
  </div>
  <hr />
  <div class="info-block contribute">
    <h3><?php echo Text::_('MOD_COMMUNITY_INFO_CONTRIBUTE_TITLE'); ?></h3>
    <p><?php echo CommunityInfoHelper::replaceText(Text::_('MOD_COMMUNITY_INFO_CONTRIBUTE_TEXT'), $links); ?></p>
    <p><?php echo CommunityInfoHelper::replaceText(Text::_('MOD_COMMUNITY_INFO_CONTRIBUTE_CONTACT'), $links); ?></p>
  </div>
</div>

<template id="template-location-picker">
  <div class="select-location">
    <a href="" onclick="openModal('location-modal', <?php echo CommunityInfoHelper::getLocation($params, 'geolocation'); ?>)">
      <i class="icon-location"></i>
      <?php echo Text::_('MOD_COMMUNITY_INFO_CHOOSE_LOCATION'); ?>
    </a><span> (<?php echo Text::_('JCURRENT'); ?>: <?php echo CommunityInfoHelper::getLocation($params, 'label'); ?>)</span>
  </div>
</template>

<template id="template-location-modal-body">
  <form action="<?php echo $currentURL; ?>" method="post" enctype="multipart/form-data" name="adminForm" id="location-form" class="form-validate p-3" aria-label="<?php echo Text::_('MOD_COMMUNITY_INFO_CHOOSE_LOCATION'); ?>">
    <div class="row">
      <div class="col-12">
        <div id="map" class="mb-3" style="height:40vh;width:100%;"></div>
        <div class="control-group">
          <div class="control-label"><label for="jform_lat" id="jfrom_lat-lbl">Latitude</label></div>
          <div class="controls"><input id="jform_lat" class="from-control" type="text" name="jform[lat]" value="<?php echo \trim($currentLoc[0]); ?>"></div>
        </div>
        <div class="control-group">
          <div class="control-label"><label for="jform_lng" id="jfrom_lng-lbl">Longitude</label></div>
          <div class="controls"><input id="jform_lng" class="from-control" type="text" name="jform[lng]" value="<?php echo \trim($currentLoc[1]); ?>"></div>
        </div>
      </div>
    </div>
  </form>
</template>

<?php
// Location form modal
$options = array('modal-dialog-scrollable' => true,
                  'title'  => Text::_('MOD_COMMUNITY_INFO_CHOOSE_LOCATION'),
                  'footer' => '<a class="btn" href="">'.Text::_('MOD_COMMUNITY_INFO_SAVE_LOCATION').'</a>',
                );
echo HTMLHelper::_('bootstrap.renderModal', 'location-modal', $options, '<p>Loading...</p>');
?>

<script>
  async function callback(){
    // prepare location picker module
    let moduleBody   = document.getElementById('CommunityInfo<?php echo strval($module->id); ?>');
    let moduleHeader = moduleBody.parentNode.previousElementSibling;

    moduleHeader.appendChild(document.getElementById('template-location-picker').content);

    // Send browsers current geolocation to com_ajax
    try {
      let location = await getCurrentLocation();
      console.log('Current Location:', location);
      
      let response = await ajaxLocation(location, 'setLocation');
      console.log('Ajax Response:', response);
    } catch (error) {
      console.error('Error:', error);
    }
  }; //end callback

  if(document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
    callback();
  } else {
    document.addEventListener('DOMContentLoaded', callback);
  }
</script>
