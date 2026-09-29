<?php

return [

    'class_namespace' => 'App\\Livewire',

    'view_path' => resource_path('views/livewire'),

    'layout' => 'components.layout.app',

    'lazy_placeholder' => null,

    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['file', 'max:5120'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:uploads',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    'render_on_redirect' => false,

    'legacy_model_binding' => false,

    'inject_assets' => true,

    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#1f4b4a',
    ],

    'inject_morph_markers' => true,

    'pagination_theme' => 'tailwind',

];
