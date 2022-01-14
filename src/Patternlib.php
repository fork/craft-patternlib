<?php
/**
 * Patternlib plugin for Craft CMS 3.x
 *
 * Fork frontend craft-patternlib support including twig extensions and more
 *
 * @link      https://www.fork.de/
 * @copyright Copyright (c) 2021 Fork Unstable Media GmbH
 */

namespace fork\patternlib;

use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use fork\patternlib\twigextensions\PatternlibTwigExtension;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

/**
 * Class Patternlib
 *
 * @author    Fork Unstable Media GmbH
 * @package   Patternlib
 * @since     1.0.0
 *
 */
class Patternlib extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Patternlib
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Craft::$app->view->registerTwigExtension(new PatternlibTwigExtension());

        // Register path to frontend templates
        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['@components'] = CRAFT_BASE_PATH . '/../frontend/src/components';
                $event->roots['@templates'] = CRAFT_BASE_PATH . '/../frontend/src/templates';
                $event->roots['@assets'] = CRAFT_BASE_PATH . '/web/assets';
            }
        );
    }

    // Protected Methods
    // =========================================================================

}
