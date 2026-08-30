<?php

namespace Tests\Feature;

use Tests\TestCase;

class GlobalTypographyTest extends TestCase
{
    public function test_the_website_uses_times_new_roman_globally(): void
    {
        $stylesheet = file_get_contents(public_path('assets/css/theme.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString(
            'font-family: "Times New Roman", Times, serif !important;',
            $stylesheet,
        );
    }

    public function test_the_global_theme_uses_bangladesh_flag_red_and_responsive_sidebar_states(): void
    {
        $stylesheet = file_get_contents(public_path('assets/css/theme.css'));
        $script = file_get_contents(public_path('assets/js/app.js'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString('--seal-red: #f42a41;', $stylesheet);
        $this->assertStringContainsString('body.sidebar-collapsed .sidebar', $stylesheet);
        $this->assertStringContainsString('body.sidebar-open .sidebar', $stylesheet);

        $this->assertIsString($script);
        $this->assertStringContainsString("window.matchMedia('(min-width: 992px)')", $script);
        $this->assertStringContainsString("document.body.classList.toggle('sidebar-collapsed')", $script);
        $this->assertStringContainsString("document.body.classList.toggle('sidebar-open')", $script);
    }
}
