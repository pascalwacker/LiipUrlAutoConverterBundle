<?php

namespace Liip\UrlAutoConverterBundle\Extension;

class UrlAutoConverterTwigExtension extends \Twig_Extension
{
    protected $linkClass;
    protected $target;
    protected $debugMode;
    protected $debugColor = '#00ff00';
    protected $shortenUrl;
    protected $shortenUrlThreshold;

    // @codeCoverageIgnoreStart

    public function getName()
    {
        return 'liip_urlautoconverter';
    }

    public function setLinkClass($class)
    {
        $this->linkClass = $class;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function setDebugMode($debug)
    {
        $this->debugMode = $debug;
    }

    public function setDebugColor($color)
    {
        $this->debugColor = $color;
    }

    public function setShortenUrl($shortenUrl)
    {
        $this->shortenUrl = $shortenUrl;
    }

    public function setShortenUrlThreshold($shortenUrlThreshold)
    {
        $this->shortenUrlThreshold = $shortenUrlThreshold;
    }

    // @codeCoverageIgnoreEnd

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'converturls',
                array($this, 'autoConvertUrls'),
                array(
                    'pre_escape' => 'html',
                    'is_safe' => array('html'),
                )
            ),
        );
    }

    /**
     * method that finds different occurrences of urls or email addresses in a string.
     *
     * @param string $string input string
     *
     * @return string with replaced links
     */
    public function autoConvertUrls($string)
    {
        $pattern = '/(href="|src=")?([-a-zA-Zа-яёА-ЯЁ0-9@:%_\+.~#?&\*\/\/=]{2,256}\.[a-zа-яё]{2,4}\b(\/?[-\p{L}0-9@:%_\+.~#?&\*\/\/=\(\),;]*)?)/u';
        $stringFiltered = preg_replace_callback($pattern, array($this, 'callbackReplace'), $string);

        return $stringFiltered;
    }

    public function callbackReplace($matches)
    {
        if ($matches[1] !== '') {
            return $matches[0]; // don't modify existing <a href="">links</a> and <img src="">
        }

        $url = $matches[2];
        $urlWithPrefix = $matches[2];

        if (strpos($url, '@') !== false) {
            $urlWithPrefix = 'mailto:'.$url;
        } elseif (strpos($url, 'https://') === 0) {
            $urlWithPrefix = $url;
        } elseif (strpos($url, 'http://') !== 0) {
            $urlWithPrefix = 'http://'.$url;
        }

        $style = ($this->debugMode) ? ' style="color:'.$this->debugColor.'"' : '';

        // ignore tailing special characters
        // TODO: likely this could be skipped entirely with some more tweakes to the regular expression
        if (preg_match("/^(.*)(\.|\,|\?)$/", $urlWithPrefix, $matches)) {
            $urlWithPrefix = $matches[1];
            $url = substr($url, 0, -1);
            $punctuation = $matches[2];
        } else {
            $punctuation = '';
        }

        // shorten link if option is set
        if ($this->shortenUrl && filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            $url = preg_replace('/^(https{0,1}:\/\/|mailto:)(.+)$/', '$2', $urlWithPrefix); // remove schema
            if (strpos($url, '@') === false && strlen($url) > $this->shortenUrlThreshold) {
                $parts = explode('/', rtrim($url, '/'));
                if (count($parts) > 2) {
                    $url = array_shift($parts) . '/.../' . array_pop($parts);
                }
            }
        }

        return '<a href="'.$urlWithPrefix.'" class="'.$this->linkClass.'" target="'.$this->target.'"'.$style.'>'.$url.'</a>'.$punctuation;
    }
}
