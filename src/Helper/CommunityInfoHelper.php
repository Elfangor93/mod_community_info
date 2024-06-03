<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_community_info
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\CommunityInfo\Administrator\Helper;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Uri\Uri;

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
      require_once JPATH_ADMINISTRATOR.'/modules/mod_community_info/includes/country_list.php';

      // Strategy 1: Geolocation from browser

      // Startegy 2: Location stored in parameters
      if(\is_null($location) && !empty($params->get('location', 0))) {
        if(\key_exists($params->get('location'), $country_list_array)) {
          // Location based on language code
          $location = $country_list_array[$params->get('location')][$key];
        } else {
          // Location based on coordinates
          $location = $params->get('location');
        }
      }

      // Strategy 3: Location based on current language
      if(\is_null($location) && \key_exists($lang, $country_list_array)) {
        $location = $country_list_array[$lang][$key];
      }

      // Strategy 4: Fallback location
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
      // set code here
    }
}
