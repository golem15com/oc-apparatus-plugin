<?php namespace Golem15\Apparatus\Middleware;

use Closure;
use Illuminate\Http\Request;
use System\Classes\PluginManager;

/**
 * Rewrites relative image src attributes in plugin README pages rendered in
 * the backend (Settings -> Updates -> Plugin Details) to point at the publicly
 * mirrored plugin assets, so hero images display correctly.
 */
class PluginReadmeImageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        error_log('PLUGIN_README_MW: ' . $request->getRequestUri());

        $uri = $request->getRequestUri();
        if (!preg_match('@/system/updates/details/([^/?]+)@', $uri, $match)) {
            return $response;
        }

        if (!method_exists($response, 'getContent')) {
            return $response;
        }

        $code = str_replace('-', '.', $match[1]);
        $pluginManager = PluginManager::instance();
        $plugin = $pluginManager->findByIdentifier($code);
        if (!$plugin) {
            return $response;
        }

        $pluginPath = $pluginManager->getPluginPath($plugin);
        $basePath = plugins_path();
        if (strpos($pluginPath, $basePath) !== 0) {
            return $response;
        }

        $relativePath = ltrim(str_replace($basePath, '', $pluginPath), '/');
        $publicBase = url('/plugins/' . $relativePath);

        $content = $response->getContent();
        $content = preg_replace_callback(
            '@(<div class="plugin-details-content">\s*)(.*?)(</div>)@s',
            function ($sections) use ($publicBase) {
                $inner = preg_replace_callback(
                    '@(<img[^>]*?\bsrc=["\'])(?!https?://|//|/)([^"\']+)(["\'])@i',
                    function ($m) use ($publicBase) {
                        return $m[1] . $publicBase . '/' . $m[2] . $m[3];
                    },
                    $sections[2]
                );
                return $sections[1] . $inner . $sections[3];
            },
            $content
        );

        $response->setContent($content);
        return $response;
    }
}
