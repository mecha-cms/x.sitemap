<?php namespace _\lot\x\sitemap;

function xml($any = null) {
    extract($GLOBALS, \EXTR_SKIP);
    $d = \LOT . \DS . 'page' . \DS . ($any ?? \trim(\State::get('path'), '/'));
    $page = new \Page(\File::exist([
        $d . '.page',
        $d . '.archive'
    ]) ?: null);
    $out = "";
    // `./foo/sitemap.xml`
    // `./foo/bar/sitemap.xml`
    if (isset($any) && $page->exist) {
        $t = (new \Time(\time()))->format('r');
        $out .= '<?xml version="1.0" encoding="UTF-8"?>';
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (\g(\Path::F($page->path), 0, true) as $k => $v) {
            $out .= '<url>';
            $out .= '<loc>' . $url . '/' . ($r = \Path::R($k, \LOT . \DS . 'page', '/')) . '</loc>';
            $priority = \b(1 - (\substr_count($r, '/') * .1), [.5, 1]); // `0.5` to `1.0`
            $exist = \File::exist([
                $k . '.page',
                $k . '.archive'
            ]);
            $out .= '<lastmod>' . (new \Time($exist ? \filemtime($exist) : null))->ISO8601 . '</lastmod>';
            $out .= '<changefreq>monthly</changefreq>';
            $out .= '<priority>' . $priority . '</priority>';
            $out .= '</url>';
        }
        $out .= '</urlset>';
    // `./sitemap.xml`
    } else {
        $out .= '<?xml version="1.0" encoding="UTF-8"?>';
        $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (\g(\LOT . \DS . 'page', 0, true) as $k => $v) {
            if (0 === \q(\g($k, 'archive,page'))) {
                // Ignore empty folder(s)
                continue;
            }
            $exist = \File::exist([
                $k . '.page',
                $k . '.archive'
            ]);
            $out .= '<sitemap>';
            $out .= '<loc>' . $url . '/' . \Path::R($k, \LOT . \DS . 'page', '/') . '/sitemap.xml</loc>';
            $out .= '<lastmod>' . (new \Time($exist ? \filemtime($exist) : \time()))->ISO8601 . '</lastmod>';
            $out .= '</sitemap>';
        }
        $out .= '</sitemapindex>';
    }
    $i = 60 * 60 * 24; // Cache output for a day
    $this->lot([
        'Cache-Control' => 'private, max-age=' . $i,
        'Expires' => \gmdate('D, d M Y H:i:s', \time() + $i) . ' GMT',
        'Pragma' => 'private'
    ]);
    $this->type('application/xml');
    $this->content($out);
}

\Route::set(['sitemap.xml', '*/sitemap.xml'], __NAMESPACE__ . "\\xml", 10);
