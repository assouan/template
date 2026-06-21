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
