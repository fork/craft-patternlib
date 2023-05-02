<?php

/**
 * Patternlib plugin for Craft CMS 3.x
 *
 * Fork frontend craft-patternlib support including twig extensions and more
 *
 * @link      https://www.fork.de/
 * @copyright Copyright (c) 2021 Fork Unstable Media GmbH
 */

namespace fork\patternlib\twigextensions;

use fork\patternlib\Patternlib;

use Craft;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use yii\base\BaseObject;
use yii\web\Cookie;

/**
 * @author    Fork Unstable Media GmbH
 * @package   Patternlib
 * @since     1.0.0
 */
class PatternlibTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Patternlib';
    }

    /**
     * Returns an array of Twig filters, used in Twig templates via:
     *
     *      {{ 'something' | someFilter }}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('withModifier', [$this, 'withModifier'], ['is_safe' => ['html']]),
            new TwigFilter('buildAttributeString', [$this, 'buildAttributeString'], ['is_safe' => ['html']]),
            new TwigFilter('buildImageSrc', [$this, 'buildImageSrc'], ['is_safe' => ['html']]),
            new TwigFilter('buildImageSrcset', [$this, 'buildImageSrcset'], ['is_safe' => ['html']]),
            new TwigFilter('includes', [$this, 'includes'], ['is_safe' => ['html']]),
            new TwigFilter('youtubeId', [$this, 'youtubeId'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('setCookie', [$this, 'setCookie']),
            new TwigFunction('getCookie', [$this, 'getCookie']),
        ];
    }

    /**
     * Creates a class-set with BEM modifiers for a component
     *
     * @see {@link http://getbem.com/introduction/}
     *
     * @param string $class Classname (example: "my-class")
     * @param array $modifier Modifier (example: ["foo", "bar"])
     *
     * @return string Classnames (example: "my-class--foo my-class--bar")
     */
    public function withModifier($class, $modifier = []): string
    {
        $res = $class;

        if (is_array(($modifier)) && !empty($modifier)) {
            $res = $class . ' ' . $class . '--' . join(' ' . $class . '--', $modifier);
        }

        return $res;
    }

    /**
     * Creates an attribute string from an array with key value pair objects
     *
     * @param array $input Input (example: { "foo": "bar"})
     *
     * @return string
     */
    public function buildAttributeString($input): string
    {
        $res = '';

        if (!is_array($input) || !count($input)) {
            return $res;
        }

        foreach ($input as $key => $value) {
            if (!empty($key) && !empty($value)) {
                $res = $res . $key . '="' . $value . '" ';
            } elseif (!empty($key)) {
                $res = $res . $key . ' ';
            }
        }

        return $res;
    }

    /**
     *
     * @param {object} asset Asset object
     * @param {object} config Transform config
     * @returns {string} Image srcset string
     */

    /**
     * Build the srcset string for an image
     *
     * @param object $asset The Craft CMS Image asset
     * @param array $config The configuration from the twig template
     * @return string The srcset String
     */
    public function buildImageSrcset($asset, $config)
    {
        extract($config);
        if (!$srcset || count($srcset) == 0) {
            return '';
        }
        $validatedConfig = self::validateTransforms($config);

        $r = $ratio ?? $asset->width / $asset->height;

        $srcsetString = join(', ', array_map(function ($size) use ($width, $height, $r, $asset, $validatedConfig) {
            $tmpWidth = $width ? $size : round($size * $r);
            $tmpHeight = $height ? $size : round($size / $r);

            $validatedConfig['width'] = $tmpWidth;
            $validatedConfig['height'] = $tmpHeight;

            return $asset->getUrl($validatedConfig) . ' ' . $tmpWidth . 'w';
        }, $srcset));

        return $srcsetString;
    }

    /**
     * Build the src for an image
     *
     * @param object $asset The Craft CMS Image asset
     * @param  array $asset The Craft CMS Image asset
     * @return string The image src attribute
     */
    public function buildImageSrc($asset, $config)
    {
        extract($config);
        $r = $ratio ?? $asset->width / $asset->height;

        $validatedConfig = self::validateTransforms($config);

        $tmpWidth = isset($width) ? $width : round($height * $r);
        $tmpHeight = isset($height) ? $height : round($width / $r);

        $validatedConfig['width'] = $tmpWidth;
        $validatedConfig['height'] = $tmpHeight;

        return $asset->getUrl($validatedConfig);
    }

    /**
     * Checks if a value is included in an array
     *
     * @param array $array
     * @param string $value
     * @return bool
     */
    public function includes($array, $value)
    {
        return in_array($value, $array);
    }

    /**
     * Returns the youtube video id from a youtube url
     *
     * @param $url
     * @return string|null
     */
    public function youtubeId($url): ?string
    {
        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $url, $matches);
        return $matches[0] ?? null;
    }

    /**
     * Sets a cookie
     *
     * @param string $name
     * @param string $value
     * @param int $expire expiration unix timestamp (default 0 only for current browser session)
     */
    public function setCookie(string $name, string $value, int $expire = 0)
    {
        $cookie = new Cookie();
        $cookie->name = $name;
        $cookie->value = $value;
        $cookie->expire = $expire;

        Craft::$app->getResponse()->getCookies()->add($cookie);
    }

    /**
     * Gets a cookie
     *
     * @param string $name
     * @return mixed
     */
    public function getCookie(string $name)
    {
        return Craft::$app->getRequest()->getCookies()->getValue($name);
    }

    /**
     * Validates and formats a craft transform config array
     *
     * @param array $config Configuration from Twig template
     * @return array Validated Configuration
     */
    protected static function validateTransforms($config)
    {
        $allowed = ['mode', 'width', 'height', 'quality', 'format', 'position'];

        return array_filter(array_intersect_key($config, array_flip($allowed)));
    }
}
