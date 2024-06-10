<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_community_info
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\CommunityInfo\Administrator\Helper;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Helper for mod_community_info
 *
 * @since  4.5.0
 */
class CommunityInfoHelper
{
    /**
     * Get a list of links from the endpoint given in the module params.
     * 
     * Endpoint:
     * https://www.codexworld.com/radius-based-location-search-by-distance-php-mysql/
     *
     * @param   Registry                  $params  Object holding the module parameters
     * @param   AdministratorApplication  $app     The application
     *
     * @return  Registry                  Object with community links
     *
     * @since   4.5.0
     */
    static public function getLinks(Registry $params, AdministratorApplication $app)
    {
        // Load the default values
        require_once JPATH_ADMINISTRATOR.'/modules/mod_community_info/includes/default_links.php';
        $links = new Registry($default_links_array);

        return $links;
    }

    /**
     * Replace placeholders in a text string
     *
     * @param   Registry                  $params  Object holding the module parameters
     * @param   AdministratorApplication  $app     The application
     *
     * @return  Registry                  Object with community links
     *
     * @since   4.5.0
     */
    static public function replaceText(string $text, Registry $links)
    {
      if(\preg_match_all('/{(.*?)}/', $text, $matches, PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          if($links->exists(\strtolower($match[1])))
          {
            // replace with link
            $output = '<a href="'.$links->get(\strtolower($match[1])).'" target="_blank">'.Text::_('MOD_COMMUNITY_INFO_TERMS_'.\strtoupper($match[1])).'</a>';
          }
          else
          {
            // replace without link
            $output = Text::_('MOD_COMMUNITY_INFO_TERMS_'.\strtoupper($match[1]));
          }
          
          $text   = \str_replace($match[0], $output, $text);
        }
      }

      return $text;
    }

    /**
     * Get location info
     *
     * @param   Registry   $params  Object holding the module parameters
     * @param   string     $key     The key for the location info 
     *
     * @return  string     Location info string
     *
     * @since   4.5.0
     */
    static public function getLocation(Registry $params, string $key='geolocation')
    {
      $location = null;

      // Get the list of countries
      require JPATH_ADMINISTRATOR.'/modules/mod_community_info/includes/country_list.php';

      $matches = [];

      // Startegy 1: Location stored in parameters
      if(\is_null($location) && !empty($params->get('location', 0))) {
        if(\key_exists($params->get('location'), $country_list_array)) {
          // Location based on language code
          $location = $country_list_array[$params->get('location')][$key];
        } elseif(\preg_match('/\d{1,4}\.\d{1,4}\,[ ]*\d{1,4}\.\d{1,4}/m', $params->get('location'), $matches)) {
          // Location based on coordinates
          $location = $params->get('location');

          if($key == 'label') {
            // We are asking for a location name. Turn coordinates into location name.
            $coor_arr = \explode(',', $matches[0], 2);
            $location = self::resolveLocation($coor_arr[0], $coor_arr[1]);
          }
        } else {
          $location = $params->get('location');
        }
      }

      // Strategy 2: Location based on current language
      if(\is_null($location) && \key_exists($lang, $country_list_array)) {
        $location = $country_list_array[$lang][$key];
      }

      // Strategy 3: Fallback location
      if(\is_null($location)) {
        $location = $country_list_array[$params->get('fallback-location', 'en-UK')][$key];
      }

      return $location;
    }

    /**
     * Set location string to module params
     *
     * @param   string    $location   The location string to be stored
     *
     * @return  void
     * @throws  Exception
     *
     * @since   4.5.0
     */
    static public function setLocation(string $location, AdministratorApplication $app)
    {
      return 'CommunityInfoHelper::setLocation()';
    }

    /**
     * Get adress based on coordinates
     *
     * @param   string     $lat     Latitude
     * @param   string     $lng     Longitude
     *
     * @return  string|false   Adress on success, false otherwise
     *
     * @since   4.5.0
     */
    static public function resolveLocation($lat, $lng)
    {
      $domain  = \str_replace(Uri::base(true), '', Uri::base());
      $email   = self::getSuperUserMails()[0];
      $url     = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='.\trim($lat).'&lon='.\trim($lng);
      $options = ['http' => ['method' => 'GET', 'header' => 'User-Agent: '.\trim($email).'\r\n' .
                                                            'Referer: '.\trim($domain).'\r\n']];
      $context = \stream_context_create($options);

      // Fetch address from openstreetmap
      try {
        $json = file_get_contents($url, false, $context);
      } catch (\Exception $e) {        
        Factory::getApplication()->enqueueMessage(Text::sprintf('MOD_COMMUNITY_ERROR_OPENSTREATMAP_NOMINATIM', $domain, $email), 'warning');

        return $lat.', '.$lng;
      }
      
      $data = \json_decode($json);

      if($data && isset($data->address->city))
      {
        return $data->address->city.', '.$data->address->state.', '.\strtoupper($data->address->country_code);
      }
      else
      {
        Factory::getApplication()->enqueueMessage(Text::sprintf('MOD_COMMUNITY_ERROR_OPENSTREATMAP_NOMINATIM', $domain, $email), 'warning');

        return $lat.', '.$lng;
      }
    }

    /**
     * Returns the Super Users email information
     *
     * @return  array  The list of Super User emails
     *
     * @since   4.5.0
     */
    static public function getSuperUserMails()
    {
      $db     = Factory::getContainer()->get(DatabaseInterface::class);

      // Get a list of groups which have Super User privileges
      $ret = ['info@example.org'];

      try {
          $rootId    = Table::getInstance('Asset')->getRootId();
          $rules     = Access::getAssetRules($rootId)->getData();
          $rawGroups = $rules['core.admin']->getData();
          $groups    = [];

          if (empty($rawGroups)) {
              return $ret;
          }

          foreach ($rawGroups as $g => $enabled) {
              if ($enabled) {
                  $groups[] = $g;
              }
          }

          if (empty($groups)) {
              return $ret;
          }
      } catch (\Exception $exc) {
          return $ret;
      }

      // Get the user IDs of users belonging to the SA groups
      try {
          $query = $db->getQuery(true)
              ->select($db->quoteName('user_id'))
              ->from($db->quoteName('#__user_usergroup_map'))
              ->whereIn($db->quoteName('group_id'), $groups);

          $db->setQuery($query);
          $userIDs = $db->loadColumn(0);

          if (empty($userIDs)) {
              return $ret;
          }
      } catch (\Exception $exc) {
          return $ret;
      }

      // Get the user information for the Super Administrator users
      try {
          $query = $db->getQuery(true)
              ->select('email')
              ->from($db->quoteName('#__users'))
              ->whereIn($db->quoteName('id'), $userIDs);

          $db->setQuery($query);
          $ret = $db->loadColumn();
      } catch (\Exception $exc) {
          return $ret;
      }

      return $ret;
    }
}
