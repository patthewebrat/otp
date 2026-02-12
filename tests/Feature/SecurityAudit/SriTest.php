<?php

namespace Tests\Feature\SecurityAudit;

use Tests\TestCase;

class SriTest extends TestCase
{
    public function test_blade_templates_have_sri_on_cdn_links(): void
    {
        $templates = [
            'app.blade.php',
            'otp.blade.php',
            'file.blade.php',
        ];

        foreach ($templates as $template) {
            $path = resource_path('views/' . $template);
            if (!file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);

            // Check that any CDN link tags have integrity attributes
            if (preg_match_all('/<link[^>]+href=["\']https?:\/\/[^"\']+["\'][^>]*>/i', $content, $matches)) {
                foreach ($matches[0] as $linkTag) {
                    $this->assertStringContainsString(
                        'integrity=',
                        $linkTag,
                        "CDN link in $template missing SRI integrity attribute: $linkTag"
                    );
                    $this->assertStringContainsString(
                        'crossorigin=',
                        $linkTag,
                        "CDN link in $template missing crossorigin attribute: $linkTag"
                    );
                }
            }
        }
    }
}
