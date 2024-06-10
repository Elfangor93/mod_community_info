<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  mod_community_info
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\CommunityInfo\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for mod_community_info
 *
 * @since  4.5.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Runs the dispatcher.
     *
     * @return  void
     *
     * @since   4.5.0
     */
    public function dispatch()
    {
        // The guided tour will not show if no user is logged in.
        $user = $this->getApplication()->getIdentity();
        if ($user === null || $user->id === 0) {
            return;
        }

        /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wr = $wa->getRegistry();
        $wr->addRegistryFile('media/mod_community_info/joomla.asset.json');

        parent::dispatch();
    }

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   4.5.0
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        // Initialize the helper class
        $this->getHelperFactory()->getHelper('CommunityInfoHelper')->initialize($this->module->id, $data['params']);

        // Get links and location
        $data['links']      = $this->getHelperFactory()->getHelper('CommunityInfoHelper')->getLinks($data['params'], $this->getApplication());
        $data['currentLoc'] = \explode(',', $this->getHelperFactory()->getHelper('CommunityInfoHelper')->getLocation($data['params'], 'geolocation'), 2);

        return $data;
    }
}
