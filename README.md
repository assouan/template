# Assouan Template

Template attribute renderer for HTTP controllers.

```bash
composer require assouan/template
```

Requires PHP 8.5 or later.

Inside a `.phtml` template, three small helpers are available:

```php
<?=$html($title)?>      // HTML text
<?=$lines($body)?>      // HTML text with newlines
<?=$url($slug)?>        // URL path segment
```

A template can opt into a layout file by setting `$layout`:

```php
<?php
$layout = ['file' => 'Layout.phtml', 'title' => 'Home'];
$status = 200;
?>

<h1><?=$html($title)?></h1>
```

The layout receives `$body`, the layout array values, and the same helpers.

Templates may set `$status` when the rendered response should use a specific HTTP code, for example a layout-backed 404 page.
