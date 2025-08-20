<?php
namespace App\Providers;

class View
{
    static public function redirect(string $uri)
    {
        if (strpos($uri, '/') === 0) {
                    header("Location: /projetweb1/public" . $uri);
    } else {
        header("Location: /projetweb1/public/" . $uri);
        }
        exit;
    }

    static public function render(string $template, array $data = [])
    {
        global $twig;
        echo $twig->render($template, $data);
        exit;
    }
}
