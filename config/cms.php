<?php

return [

    /*
     * Used only by database/seeders/AdminUserSeeder.php. See that file and
     * .env.example for the full explanation of how the seeded admin
     * password is chosen depending on APP_ENV.
     */
    'admin_seed_email' => env('ADMIN_SEED_EMAIL', 'admin@example.com'),
    'admin_seed_password' => env('ADMIN_SEED_PASSWORD'),

    /*
     * How many posts are shown per page on the public blog index and in
     * the admin posts list.
     */
    'posts_per_page' => (int) env('CMS_POSTS_PER_PAGE', 9),
    'admin_posts_per_page' => (int) env('CMS_ADMIN_POSTS_PER_PAGE', 15),

];
