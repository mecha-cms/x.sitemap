<?php namespace x;

function sitemap($content) {
    global $state, $url;
    return \str_replace('</head>', '<link href="' . $url->clean . '/sitemap.xml" rel="sitemap" type="application/xml" title="' . \i('Sitemap') . ' | ' . \w($state->title) . '"></head>', $content);
}

// Insert some HTML `<link>` that maps to the sitemap resource
if ('sitemap.xml' !== \basename($url->path)) {
    // Make sure to run the hook before `x\minify`
    \Hook::set('content', __NAMESPACE__ . "\\sitemap", 1.9);
}
