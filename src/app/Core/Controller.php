<?php

namespace App\Core;

abstract class Controller
{
    /**
     * Render a view file with optional data.
     *
     * @param string $view
     * @param array $data
     * @param bool $returnAsValue
     * @return array|null
     */
    protected function view(string $view, array $data = [])
    {
        $viewFile = VIEW_PATH. '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: " . $viewFile);
        }
        // Extract variables to make them available in the view
        extract($data);
        // Start output buffering
        ob_start();
        // Include the layout file, which in turn includes the view file
        include VIEW_PATH. '/layout.php';
        // Get the output buffer content
        $content = ob_get_clean();
        echo $content;
        return null;
    }
}
