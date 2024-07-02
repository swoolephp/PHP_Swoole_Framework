<?php
namespace App\Controllers;

class ErrorController {
    public function show404() {
        http_response_code(404);
        echo "404 Not Found: The page you are looking for could not be found.";
    }
}
