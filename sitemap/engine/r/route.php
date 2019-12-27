<?php namespace _\lot\x\sitemap;

function xml($any = null) {
    extract($GLOBALS, \EXTR_SKIP);
    $t = $_SERVER['REQUEST_TIME'];
    $f = \LOT . \DS . 'page' . \DS . ($any ?? \trim(\State::get('path'), '/'));
    $page = new \Page(\File::exist([
        $f . '.page',
        $f . '.archive'
    ]) ?: null);
    $exist = $page->exist;
    $out = "";
    // `./foo/sitemap.xml`
    // `./foo/bar/sitemap.xml`
    if (isset($any)) {
        $out .= '<?xml version="1.0" encoding="UTF-8"?>';
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        if ($exist) {
            foreach (\g($f, 0, true) as $k => $v) {
                $out .= '<url>';
                $out .= '<loc>' . $url . '/' . ($r = \Path::R($k, \LOT . \DS . 'page', '/')) . '</loc>';
                $priority = \b(1 - (\substr_count($r, '/') * .1), [.5, 1]); // `0.5` to `1.0`
                $kk = \File::exist([
                    $k . '.page',
                    $k . '.archive'
                ]);
                $out .= '<lastmod>' . \date('c', $kk ? \filemtime($kk) : $t) . '</lastmod>';
                $out .= '<changefreq>monthly</changefreq>';
                $out .= '<priority>' . $priority . '</priority>';
                $out .= '</url>';
            }
        } else {
            $this->status(404);
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
            $kk = \File::exist([
                $k . '.page',
                $k . '.archive'
            ]);
            $out .= '<sitemap>';
            $out .= '<loc>' . $url . '/' . \Path::R($k, \LOT . \DS . 'page', '/') . '/sitemap.xml</loc>';
            $out .= '<lastmod>' . \date('c', $kk ? \filemtime($kk) : $t) . '</lastmod>';
            $out .= '</sitemap>';
        }
        $out .= '</sitemapindex>';
    }
    $i = 60 * 60 * 24; // Cache output for a day
    $this->lot($exist ? [
        'Cache-Control' => 'max-age=' . $i . ', private',
        'Expires' => \gmdate('D, d M Y H:i:s', $t + $i) . ' GMT',
        'Pragma' => 'private'
    ] : [
        'Cache-Control' => 'max-age=0, must-revalidate, no-cache, no-store',
        'Expires' => '0',
        'Pragma' => 'no-cache'
    ]);
    $this->type('application/xml');
    $this->content($out);
}

\Route::set(['sitemap.xml', '*/sitemap.xml'], __NAMESPACE__ . "\\xml", 10);
