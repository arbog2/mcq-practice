<?php

namespace App\Helpers;

class HtmlHelper
{
    private static ?\HTMLPurifier $purifier = null;

    private static function purifier(): \HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,em,u,s,sub,sup,ol,ul,li,pre,code,h1,h2,h3,h4,h5,h6,table,thead,tbody,tr,th,td,caption,colgroup,col,blockquote,hr,span,div,a[href|target|rel],img[src|alt|width|height|class],abbr[title],code,pre');
            $config->set('HTML.TargetBlank', true);
            $config->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
            $config->set('Attr.AllowedClasses', []);
            $config->set('Cache.SerializerPath', storage_path('app/purifier'));
            $config->set('Attr.EnableID', true);
            $config->set('HTML.SafeIframe', false);
            $config->set('URI.DisableExternalResources', false);
            $config->set('URI.DisableResources', false);
            self::$purifier = new \HTMLPurifier($config);
        }

        return self::$purifier;
    }

    public static function purify(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return self::purifier()->purify($html);
    }
}
